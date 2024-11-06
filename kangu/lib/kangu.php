<?php
    if (!defined('WPINC'))
        die;
	
    $woocommerce_file = 'woocommerce/woocommerce.php';

    if (!in_array($woocommerce_file, apply_filters('active_plugins', get_option('active_plugins'))))
        return;

    function kangu_shipping_method() {
        if (class_exists('Kangu_Shipping_Method'))
            return;

        class Kangu_Shipping_Method extends WC_Shipping_Method {
            public function __construct() {
                $this->availability = 'including';
                $this->countries = array('BR');

                $kangu_settings = get_option('rpship-calculator-setting');

                $this->enabled = !empty($kangu_settings['kangu_enabled']) ? 'yes' : 'no';

                if (!empty($kangu_settings['token_kangu'])) {
                    $this->settings['token'] = $kangu_settings['token_kangu'];
                }

                $this->settings['add_day']  = isset($kangu_settings['add_day']) ? $kangu_settings['add_day'] : 0;
                $this->settings['add_price'] = isset($kangu_settings['add_price']) ? $kangu_settings['add_price'] : 0;
            }

            public function calculate_shipping($package = array()) {
				if (!$this->enabled) {
					return;
				}
				
				if (!$this->settings['token']) {
					return;
				}
				
				$package['config'] = $this->settings;
				
                foreach ($package['contents'] as $values) {
                    $_product = $values['data'];

                    $package['produtos'][] = array(
                        'peso'          => $_product->get_weight(),
                        'altura'        => $_product->get_height(),
                        'largura'       => $_product->get_width(),
                        'comprimento'   => $_product->get_length(),
                        'produto'       => $_product->get_name(),
                        'valor'         => $_product->get_price(),
                        'quantidade'    => $values['quantity']
                    );
                }

                $result = wp_remote_post('https://portal.kangu.com.br/tms/transporte/woocommerce-simular', [
                    'body'      => json_encode($package),
                    'timeout'   => 120,
                    'headers'   => [
                        'Content-Type' => 'application/json; charset=utf-8',
                        'token' => $this->settings['token']
                    ]
                ]);

                wc_clear_notices();

                if ($result instanceof WP_Error) {
                    wc_add_notice($result->get_error_message(), 'error');
                } else {
                    if ($result['response']['code'] === 200) {
                        $fretes = json_decode($result['body'], true);
    
                        if ($fretes && is_array($fretes)) {
                            foreach ($fretes as $frete) {
                                $this->add_rate($frete);

                                if (isset($frete['alertas'])) {
                                    foreach ($frete['alertas'] as $alerta) {
                                        wc_add_notice($alerta);
                                    }
                                }

                                if (isset($frete['error']) && $frete['error']['message']) {
                                    foreach ($frete['error'] as $alerta) {
                                        wc_add_notice($frete['error']['message'], 'error');
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    add_action('woocommerce_shipping_init', 'kangu_shipping_method');

    function add_kangu_shipping_method($methods) {
        $methods[] = 'Kangu_Shipping_Method';

        return $methods;
    }
    add_filter('woocommerce_shipping_methods', 'add_kangu_shipping_method');

    function sv_wc_add_print_label_meta_box_action( $actions ) {
		$actions['wc_print_label_action'] = __( 'Imprimir Etiqueta Kangu', 'send-order-kangu' );

		return $actions;
    }
    add_action('woocommerce_order_actions', 'sv_wc_add_print_label_meta_box_action' );

    function sv_wc_print_label_meta_box_action($order) {
        wp_redirect(get_admin_url(null, 'admin.php?page=kangu&etiqueta=' .  esc_html($order->get_id())));
        exit;
    }
    add_action('woocommerce_order_action_wc_print_label_action', 'sv_wc_print_label_meta_box_action');

    add_action('before_woocommerce_init', function() {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    });

    add_action('rest_api_init', function () {
        register_rest_route('kangu/api', '/update-token', array(
            'methods' => 'POST',
            'callback' => 'kangu_update_token',
        ));
    });
    
    function kangu_update_token(WP_REST_Request $request)
    {
        try {
            $token = sanitize_text_field( $request->get_param('token') );
            $consumer_secret = sanitize_text_field( $request->get_param('cs') );
            $consumer_key = wc_api_hash( sanitize_text_field( $request->get_param('ck') ) );
        
            if (empty($token) || empty($consumer_secret) || empty($consumer_key)) {
                return new WP_REST_Response(array('message' => 'Bad Request'), 400);
            }
        
            global $wpdb;
        
            $key_data = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}woocommerce_api_keys WHERE consumer_secret = %s AND consumer_key = %s",
                    $consumer_secret,
                    $consumer_key
                )
            );
        
            if (!$key_data) {
                return new WP_REST_Response(array('message' => 'Unauthorized'), 401);
            }
        
            $settings = get_option('rpship-calculator-setting');

            if (!is_array($settings)) {
                return new WP_REST_Response(array('message' => 'Unprocessable Entity'), 422);
            }

            $settings['token_kangu'] = $token;
            update_option('rpship-calculator-setting', $settings);

            return new WP_REST_Response(array('message' => 'Token atualizado com sucesso.'), 200);

        } catch ( Exception $e ) {
            return new WP_Error( 'error', $e->getMessage() );
        }        
    }
?>
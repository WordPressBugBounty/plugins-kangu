<?php
if (!class_exists('KanguShipping')) {
    class KanguShipping {
        private $rpship_settings;
        private static $plugin_url;
        private static $plugin_dir;
        private static $plugin_title = 'Fretes Kangu';
        private static $plugin_slug = 'kangu';
        private static $rpship_option_key = 'rpship-calculator-setting';
        public static $calculator_metakey = '__calculator_hide';

        public function __construct()
        {
            global $rpship_plugin_dir, $rpship_plugin_url;
            
            /* plugin url and directory variable */
            self::$plugin_dir = $rpship_plugin_dir;
            self::$plugin_url = $rpship_plugin_url;

            /* load shipping calculator setting */
            $this->rpship_settings = get_option(self::$rpship_option_key);
            
            /* create admin menu for shipping calculator setting */
            add_action("admin_menu", array($this, "admin_menu"), 58);

            /* hook update shipping method */
            add_action('wp_ajax_nopriv_update_shipping_method', array($this, 'update_shipping_method'));
            add_action('wp_ajax_update_shipping_method', array($this, 'update_shipping_method'));

            /* wp_footer hook */
            add_action("wp_footer", array($this, "wp_footer"));

            /* wp_header hook used for include css */
            add_action("wp_head", array($this, "wp_head"));

            /* register admin css and js for shipping calculator */
            add_action('admin_enqueue_scripts', array($this, 'admin_script'));

            /* shipping calculato shortcode */
            add_shortcode("shipping-calculator", array($this, "srt_shipping_calculator"));
            add_action('woocommerce_product_options_general_product_data', array($this, 'add_custom_price_box'));
            add_action('woocommerce_process_product_meta', array($this, 'custom_woocommerce_process_product_meta'), 2);
            
            /* check shipping button display on product page */
            if ($this->get_setting('enable_on_productpage') == 1) {
                /* hook for display shipping button on product page */
                add_action('woocommerce_single_product_summary', array(&$this, 'display_shipping_calculator'), 8);
            }

            add_action('woocommerce_product_bulk_edit_save', array($this, 'save_bulk_shipping_fields'));
            add_action('manage_product_posts_custom_column', array($this, 'output_quick_shipping_values'));
            add_action('woocommerce_product_quick_edit_end', array($this, 'output_quick_shipping_fields'));
            add_action('woocommerce_product_quick_edit_save', array($this, 'save_quick_shipping_fields'));

            add_action('woocommerce_calculated_shipping', [$this, 'apply_fields_on_shipping']);

            if ($this->get_setting('hide_country_on_cart') == 1) {
                add_filter('woocommerce_shipping_calculator_enable_country', '__return_false');
            }

            if ($this->get_setting('hide_state_on_cart') == 1) {
                add_filter('woocommerce_shipping_calculator_enable_state', '__return_false');
            }

            if ($this->get_setting('hide_city_on_cart') == 1) {
                add_filter('woocommerce_shipping_calculator_enable_city', '__return_false');
            }

            if ($this->get_setting('kangu_enabled') == 1) {
                add_action('woocommerce_cart_totals_after_shipping', [$this, 'get_shippings_to_cart']);
                add_action('woocommerce_review_order_before_order_total', [$this, 'get_shippings_to_cart']);
            }

            add_action('add_meta_boxes', [$this, 'registerMetabox']);

            add_action('woocommerce_order_details_after_order_table', [$this, 'addKanguTrackingBoxToCustomer'], 0);

            add_filter('wp_ajax_cotation_product_page', '__return_false');

            if (isset($_GET['ajax_settings'])) {
                $this->saveSetting();

                exit;
            }

            if (isset($_GET['oauth_kangu'])) {
                add_action('init', [$this, 'oAuthKangu']);
            }
        }

        /**
         * Display the order tracking code in order details and the tracking history.
         *
         * @param WC_Order $order Order data.
         */
        public function addKanguTrackingBoxToCustomer($order)
        {
            $tracking_codes = $this->getKanguTrackingCodes($order);

            // Check if exist a tracking code for the order.
            if (empty($tracking_codes)) {
                return;
            }

            $code = array_pop($tracking_codes);

            $tracking = $this->getKanguTracking($code);

            if (empty($tracking)) {
                return;
            }
            
            self::include_view('kangu-box-tracking', $tracking);
        }

        /**
         * Register kangu metabox.
         */
        public function registerMetabox() {
            $order_id = get_the_ID();

            $tracking_codes = $this->getKanguTrackingCodes($order_id);

            if (empty($tracking_codes)) {
                add_meta_box('wc_kangu_orders', '<span>Fretes <b class="kangu-color">Kangu</b></span>', [$this, 'addKanguSideBox'], 'shop_order', 'side', 'high');
            } else {
                $this->addKanguTrackingBox($tracking_codes);
            }
        }

        /**
         * Kangu metabox content.
         */
        public function addKanguSideBox($post) {
            self::include_view('kangu-side-box-orders', $this->orderHasKangu($post->ID));
        }

        /**
         * Tracking code metabox content.
         *
         * @param array $tracking_codes.
         */
        public function addKanguTrackingBox($tracking_codes) {
            $code = array_pop($tracking_codes);

            if (!empty($this->getKanguTracking($code))) {
                add_meta_box('wc_kangu_tracking', '<span>Fretes <b class="kangu-color">Kangu</b></span>', [$this, 'addKanguTrackingSideBox'], 'shop_order', 'side', 'high');
            }
        }

        /**
         * Kangu metabox content.
         *
         * @param WC_Post $post Post data.
         */
        public function addKanguTrackingSideBox($post) {
            $tracking_codes = $this->getKanguTrackingCodes($post->ID);

            $code = array_pop($tracking_codes);
            
            self::include_view('kangu-side-box-tracking', $this->getKanguTracking($code));
        }

        public function getKanguTracking($code)
        {
            $tracking = wp_safe_remote_get(
                sprintf('https://portal.kangu.com.br/tms/transporte/rastrear/%s', $code),
                [
                    'codigo'    => $code,
                    'headers'   => [
                        'token'         => $this->rpship_settings['token_kangu'],
                        'Content-Type'  => 'application/json'
                    ]
                ]
            );

            if ($tracking instanceof WP_Error) {
                return false;
            }

            $tracking = json_decode($tracking['body'], true);

            if (empty($tracking) || empty($tracking['situacao'])) {
                return false;
            }

            return $tracking;
        }

        public function orderHasKangu($order)
        {
            if (is_numeric($order)) {
                $order = wc_get_order($order);
            }

            $shipping_methods = $order->get_items('shipping');

            $reference = false;

            if (is_array($shipping_methods) && count($shipping_methods) > 0) {
                foreach ($shipping_methods as $shipping) {
                    if (!empty($shipping->get_formatted_meta_data())) {
                        $reference = array_search('referencia_kangu', array_column($shipping->get_formatted_meta_data(), 'key'));
    
                        break;
                    }
                }
            }

            return [
                'order_id'  => $order->get_id(),
                'has_kangu' => $reference
            ];
        }

        public function getKanguTrackingCodes($order)
        {
            if (is_numeric($order)) {
                $order = wc_get_order($order);
            }
        
            if (is_object($order)) {
                if (method_exists($order, 'get_meta')) {
                    $codes = $order->get_meta('_kangu_tracking_code');
                } else {
                    $codes = isset($order->kangu_tracking_code) ? $order->kangu_tracking_code : [];
                }
            }
        
            return !empty($codes) ? array_filter(explode(',', $codes)) : [];
        }

        public function getKanguTrackingLinks($order)
        {
            if (is_numeric($order)) {
                $order = wc_get_order($order);
            }

            if (is_object($order)) {
                if (method_exists($order, 'get_meta')) {
                    $codes = $order->get_meta('_kangu_tracking_link');
                } else {
                    $codes = isset($order->kangu_tracking_link) ? $order->kangu_tracking_link : [];
                }
            }
        
            return !empty($codes) ? array_filter(explode(',', $codes)) : [];
        }

        public function apply_fields_on_shipping()
        {
            $dataPost = $this->sanitizeData($_POST);

            if ($this->get_setting('hide_country_on_cart') == 1 || $this->get_setting('hide_state_on_cart') == 1 || ($this->get_setting('hide_city_on_cart') == 1)) {
                $country = ($this->get_setting('hide_country_on_cart') == 1) ? 'BR' : $dataPost['calc_shipping_country'];
    
                $cep = isset($dataPost['calc_shipping_postcode']) ? $dataPost['calc_shipping_postcode'] : '';
                $cep = wc_format_postcode($cep, $country);

                $state = isset($dataPost['calc_shipping_state']) ? $dataPost['calc_shipping_state'] : wc_get_customer_default_location()['state'];
    
                $city = isset($dataPost['calc_shipping_city']) ? $dataPost['calc_shipping_city'] : '';
    
                if (!empty($cep)) {
                    WC()->shipping->reset_shipping();
                    WC()->customer->set_location($country, $state, $cep, $city);
                    WC()->customer->save();
                }
            }
        }

        public function save_quick_shipping_fields($product)
        {
            $product_id = $product->id;

            if ($product_id > 0) {
                $metavalue = isset($_REQUEST[self::$calculator_metakey]) ? "yes" : "no";
                update_post_meta($product_id, self::$calculator_metakey, $metavalue);
            }
        }

        public function output_quick_shipping_fields()
        {
            include self::$plugin_dir . "view/quick-settings.php";
        }

        public function output_quick_shipping_values($column)
        {
            global $post;

            $product_id = $post->ID;

            if ($column == 'name') {
                $estMeta = get_post_meta($product_id, self::$calculator_metakey, true);
                ?>
                <div class="hidden" id="rpwoo_shipping_inline_<?php echo esc_html($product_id); ?>">
                    <div class="_shipping_enable"><?php echo esc_html($estMeta); ?></div>
                </div>
                <?php
            }
        }

        public function save_bulk_shipping_fields($product)
        {
            $product_id = $product->id;

            if ($product_id > 0) {
                $metavalue = isset($_REQUEST[self::$calculator_metakey]) ? "yes" : "no";
                update_post_meta($product_id, self::$calculator_metakey, $metavalue);
            }
        }

        public function custom_woocommerce_process_product_meta($post_id)
        {
            $metavalue = isset($_POST[self::$calculator_metakey]) ? "yes" : "no";
            update_post_meta($post_id, self::$calculator_metakey, $metavalue);
        }

        public function add_custom_price_box()
        {
            $hide_calculator = "yes";
            if (isset($_GET["post"]))
                $hide_calculator = get_post_meta(sanitize_text_field(wp_unslash($_GET['post'])), self::$calculator_metakey, true);
            woocommerce_wp_checkbox(array('id' => self::$calculator_metakey, 'value' => $hide_calculator, 'label' => __('Hide Shipping Calculator?', 'rphpa_hide_calculator')));
        }

        public function update_shipping_method()
        {
            $dataPost = $this->sanitizeData($_POST);

        	$dataPost['calc_shipping_country'] = 'BR';

            WC_Shortcode_Cart::calculate_shipping();

            $cart_item_key = null;

            if (isset($dataPost["product_id"]) && $this->check_product_incart($dataPost["product_id"]) === false) {
                $qty = (isset($dataPost['current_qty']) && $dataPost['current_qty'] > 0) ? $dataPost['current_qty'] : 1;

                if (isset($dataPost['variation_id']) && $dataPost['variation_id'] != "" && $dataPost['variation_id'] > 0) {
                    $cart_item_key = WC()->cart->add_to_cart($dataPost["product_id"], $qty, $dataPost['variation_id']);
                } else {
                    $cart_item_key = WC()->cart->add_to_cart($dataPost["product_id"], $qty);
                }
            }

            self::get_shipping_methods();

            if (!empty($cart_item_key)) {
                WC()->cart->remove_cart_item($cart_item_key);
            }
			
            die();
        }

        public static function get_shipping_methods()
        {
            $packages = WC()->cart->get_shipping_packages();
            $packages = WC()->shipping->calculate_shipping($packages);
            $available_methods = WC()->shipping->get_packages();

            $methods = [];

            if (isset($available_methods[0]['rates']) && count($available_methods[0]['rates']) > 0) {
                foreach ($available_methods as $rates) {
                    foreach ($rates['rates'] as $key => $method) {
                        $data = [
                            'cost'          => $method->cost,
                            'label'         => $method->label,
                            'value'         => $key,
                            'checked'       => checked($key, WC()->session->chosen_shipping_method, true),
                            'deadline'          => self::get_delivery_deadline($method),
                            'point_address'     => !empty($method->get_meta_data()['point_address']) ? $method->meta_data['point_address'] : '',
                            'distance'          => !empty($method->get_meta_data()['point_distance']) ? $method->meta_data['point_distance'] : '',
                            'point_label'       => !empty($method->get_meta_data()['point_label']) ? $method->meta_data['point_label'] : '',
                            'shipping_label'    => !empty($method->get_meta_data()['shipping_label']) ? $method->meta_data['shipping_label'] : '',
                        ];

                        if (!empty($method->get_meta_data()['point_code'])) {
                            $methods['pickup'][] = $data;
                        } else {
                            $methods['delivery'][] = $data;
                        }
                    }
                }
            }

            self::include_view('shipping-methods', $methods);
        }

        public static function get_delivery_deadline($method)
        {
            $deadline = !empty($method->get_meta_data()['deadline']) ? $method->meta_data['deadline'] : '';

            if (!$deadline && !empty($method->get_meta_data()['_delivery_forecast'])) {
                $delivery_forecast = $method->meta_data['_delivery_forecast'] ? intval( $method->meta_data['_delivery_forecast'] ) : 0;
                $deadline = esc_html( sprintf( _n( 'Delivery within %d working day', 'Delivery within %d working days', $delivery_forecast, 'woocommerce-correios' ), $delivery_forecast ) );
            }

            return $deadline;
        }

        public static function get_shippings_to_cart()
        {
            $packages = WC()->shipping()->get_packages();
            $first    = true;

            foreach ( $packages as $i => $package ) {
                $chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
                $product_names = array();

                if ( count( $packages ) > 1 ) {
                    foreach ( $package['contents'] as $item_id => $values ) {
                        $product_names[ $item_id ] = $values['data']->get_name() . ' &times;' . $values['quantity'];
                    }
                    $product_names = apply_filters( 'woocommerce_shipping_package_details_array', $product_names, $package );
                }

                $available_methods = [];

                foreach ($package['rates'] as $rate) {
                    if (!empty($rate->get_meta_data()['point_code'])) {
                        $available_methods['pickup'][] = $rate;
                    } else {
                        $available_methods['delivery'][] = $rate;
                    }
                }

                self::include_view(
                    'cart-shipping',
                    array(
                        'package'                  => $package,
                        'available_methods'        => $available_methods,
                        'show_package_details'     => count( $packages ) > 1,
                        'show_shipping_calculator' => is_cart() && apply_filters( 'woocommerce_shipping_show_shipping_calculator', $first, $i, $package ),
                        'package_details'          => implode( ', ', $product_names ),
                        'package_name'             => apply_filters( 'woocommerce_shipping_package_name', ( ( $i + 1 ) > 1 ) ? sprintf( _x( 'Shipping %d', 'shipping packages', 'woocommerce' ), ( $i + 1 ) ) : _x( 'Shipping', 'shipping packages', 'woocommerce' ), $i, $package ),
                        'index'                    => $i,
                        'chosen_method'            => $chosen_method,
                        'formatted_destination'    => $package['destination']['postcode'],
                        'has_calculated_shipping'  => WC()->customer->has_calculated_shipping(),
                    )
                );

                $first = false;
            }
        }

        public static function include_view($view, $args = [])
        {
            if (!empty($args)) {
                extract($args);
            }

            include_once self::get_view_path($view);
        }

        public static function get_view_path($view)
        {
            return sprintf('%s/view/%s.php', rtrim(self::$plugin_dir, '/'), str_replace('.php', '', $view));
        }

        /* function for display shipping calculator on product page */
        public function display_shipping_calculator()
        {
            global $product;
            if (get_post_meta($product->get_id(), self::$calculator_metakey, true) != "yes")
                include_once self::$plugin_dir . 'view/shipping-calculator.php';
        }

        function srt_shipping_calculator()
        {
            ob_start();
            include_once self::$plugin_dir . 'view/shipping-calculator.php';
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }

        public function check_product_incart($product_id)
        {
            foreach (WC()->cart->get_cart() as $values) {
                $_product = $values['data'];

                if ($product_id == $_product->id) {
                    return true;
                }
            }

            return false;
        }

        /* function for calculate shiiping */
        public function get_shipping_text($shipping_method, $country)
        {
            global $woocommerce, $post;
            $returnResponse = array();

            $dataPost = $this->sanitizeData($_POST);

            WC_Shortcode_Cart::calculate_shipping();
            if (isset($dataPost["product_id"]) && $this->check_product_incart($dataPost["product_id"]) === false) {
                $qty = (isset($dataPost['current_qty']) && $dataPost['current_qty'] > 0) ? $dataPost['current_qty'] : 1;
                if (isset($dataPost['variation_id']) && $dataPost['variation_id'] != "" && $dataPost['variation_id'] > 0) {
                    $cart_item_key = WC()->cart->add_to_cart($dataPost["product_id"], $qty, $dataPost['variation_id']);
                } else {
                    $cart_item_key = WC()->cart->add_to_cart($dataPost["product_id"], $qty);
                }
                $packages = WC()->cart->get_shipping_packages();
                $packages = WC()->shipping->calculate_shipping($packages);
                $packages = WC()->shipping->get_packages();
                WC()->cart->remove_cart_item($cart_item_key);
            } else {
                $packages = WC()->cart->get_shipping_packages();
                $packages = WC()->shipping->calculate_shipping($packages);
                $packages = WC()->shipping->get_packages();
            }
            wc_clear_notices();
            if (isset($packages[0]["rates"][$shipping_method])) {
                $selectedShiiping = $packages[0]["rates"][$shipping_method];
                $finalCost=$selectedShiiping->cost;
                if(isset($selectedShiiping->taxes) && !empty($selectedShiiping->taxes)){
                    foreach($selectedShiiping->taxes as $taxes){
                        $finalCost=$finalCost+$taxes;
                    }
                }
                $returnResponse = array("label" => $selectedShiiping->label, "cost" => wc_price($finalCost));
            } else {
                $AllMethod = WC()->shipping->load_shipping_methods();
                $selectedMethod = $AllMethod[$shipping_method];
                $flag = 0;
                if ($selectedMethod->availability == "including"):
                    foreach ($selectedMethod->countries as $methodcountry) {
                        if ($country == $methodcountry) {
                            $flag = 1;
                        }
                    }
                    if ($flag == 0):
                        $message = $selectedMethod->method_title . " is not available in selected country.";
                        $returnResponse = array("code" => "error", "message" => $message);
                    endif;
                endif;
            }
            return $returnResponse;
        }

        public function admin_script()
        {
            if (is_admin()) {
                // Add the color picker css file       
                wp_enqueue_style('wp-color-picker');
                wp_enqueue_script('rpship-admin', self::$plugin_url . "assets/js/admin.js", array('wp-color-picker'), false, true);
                wp_enqueue_style('rpship-admin', self::$plugin_url . "assets/css/admin.css");
            }
        }

        public function wp_head()
        {
            wp_enqueue_style('shipping-calculator', self::$plugin_url . "assets/css/shipping-calculator.css");
            wp_enqueue_style('kangu-cart', self::$plugin_url . 'assets/css/kangu-cart.css');

            /* register jquery */
            wp_enqueue_script('jquery');

            $this->include_view('admin-ajax');
        }

        public function wp_footer()
        {
            wp_enqueue_script('wc-country-select');
            wp_enqueue_script('shipping-calculator', self::$plugin_url . "assets/js/shipping-calculator.js");
            wp_enqueue_script('kangu-cart', self::$plugin_url . "assets/js/kangu-cart.js");
        }

        /* register admin menu for shipping calculator setting */
        public function admin_menu()
        {
            $wc_page = 'woocommerce';
            add_submenu_page($wc_page, self::$plugin_title, self::$plugin_title, "install_plugins", self::$plugin_slug, array($this, "calculator_setting_page"));
        }

        /* admin setting page for shipping calculator  */
        public function calculator_setting_page()
        {
            /* save shipping calculator setting */
            if (isset($_POST[self::$plugin_slug])) {
                $this->saveSetting();

                exit;
            }

            if (isset($_GET['user_id'])) {
                $data = explode('-', sanitize_text_field(wp_unslash($_GET['user_id'])));

                update_option(
                    self::$rpship_option_key,
                    [
                        'token_kangu'           => $data[0],
                        'kangu_enabled'         => 1,
                        'enable_on_productpage' => 1,
                        'hide_country_on_cart'  => 1,
                        'hide_state_on_cart'    => 1,
                        'hide_city_on_cart'     => 1,
                        'kangu_version'         => 2
                    ]
                );

                if (isset($data[1])) {
                    wp_redirect('https://portal.kangu.com.br/integracoes/cotador/woocommerce');

                    exit;
                }

                wp_redirect(get_admin_url(null, 'admin.php?page=kangu'));

                exit;
            }

            if (isset($_GET['clear_token'])) {
                update_option(
                    self::$rpship_option_key,
                    [
                        'token_kangu'           => '',
                        'kangu_enabled'         => 0,
                        'enable_on_productpage' => 0,
                        'hide_country_on_cart'  => 0,
                        'hide_state_on_cart'    => 0,
                        'hide_city_on_cart'     => 0,
                        'kangu_version'         => null
                    ]
                );

                wp_redirect(get_admin_url(null, 'admin.php?page=kangu'));

                exit;
            }

            $auth_url = get_admin_url(null, 'admin.php?page=kangu&oauth_kangu=true');
            
            /* include admin  shipping calculator setting file */
            include_once self::$plugin_dir . "view/shipping-setting.php";
        }

        public function oAuthKangu()
        {
            $consumer_key = 'ck_' . wc_rand_hash();

            $data = array(
                'user_id'         => get_current_user_id(),
                'description'     => 'Kangu API',
                'permissions'     => 'read_write',
                'consumer_key'    => wc_api_hash( $consumer_key ),
                'consumer_secret' => 'cs_' . wc_rand_hash(),
                'truncated_key'   => substr( $consumer_key, -7 ),
            );

            global $wpdb;

            $key_id = $wpdb->insert(
                $wpdb->prefix . 'woocommerce_api_keys',
                $data,
                array(
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                )
            );

            if (!$key_id) {
                wp_redirect(get_admin_url(null, 'admin.php?page=kangu&error_oauth=true'));

                exit;
            }

            unset($data['user_id'], $data['description'], $data['permissions'], $data['truncated_key']);

            $data['base_url']       = urlencode(get_site_url());
            $data['consumer_key']   = $consumer_key;

            wp_redirect(sprintf('https://kangu.com.br/woocommerce-login?%s', rawurldecode(http_build_query($data))));

            exit;
        }

        /* function for save setting */
        public function saveSetting()
        {
            $arrayRemove = array(self::$plugin_slug, "btn-rpship-submit");
            $saveData = array();

            $dataPost = $this->sanitizeData($_POST);

            foreach ($dataPost as $key => $value):
                if (in_array($key, $arrayRemove))
                    continue;
                $saveData[$key] = $value;
            endforeach;

            if (isset($_GET['confirm_config']) && !empty((int)$_GET['confirm_config'])) {
                $saveData['confirm_config'] = true;
            } else {
                $saveData['confirm_config'] = false;
            }
            
            $this->rpship_settings = $saveData;
            update_option(self::$rpship_option_key, $saveData);
        }

        /* function for get setting */
        public function get_setting($key)
        {
            if (!$key || $key == "")
                return;
            if (!isset($this->rpship_settings[$key]))
                return;
                
            $value = $this->rpship_settings[$key];
                
            return $value;
        }

        public function sanitizeData($data)
        {
            $result = [];

            foreach ($data as $key => $value) {
                $key    = is_scalar($key) ? sanitize_text_field($key) : $key;
                $value  = is_scalar($value) ? sanitize_text_field($value) : $value;

                $result[$key] = $value;
            }

            return wp_unslash($result);
        }
    }
}
new KanguShipping();
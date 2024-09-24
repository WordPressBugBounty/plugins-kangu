<?php wp_enqueue_style('kangu-settings', self::$plugin_url . 'assets/css/kangu-settings.css'); ?>
<?php wp_enqueue_style('kangu-toast-css', self::$plugin_url . 'assets/css/toast.min.css'); ?>
<?php wp_enqueue_script('kangu-toast-js', self::$plugin_url . 'assets/js/toast.min.js'); ?>
<?php wp_enqueue_script('kangu-mask-js', self::$plugin_url . 'assets/js/mask.min.js'); ?>
<div id="root">
    <div class="woocommerce-layout">
        <div class="woocommerce-layout__header">
            <div class="woocommerce-layout__header-wrapper">
                <div class="woocommerce-layout__header-heading">
                    <div tabindex="0" role="button" data-testid="header-back-button" class="woocommerce-layout__header-back-button">
                        <img src="<?php echo esc_url(self::$plugin_url); ?>assets/images/logo-kangu-meli.png" alt="Logo Kangu" height="45px">
                    </div>
                </div>
            </div>
        </div>

        <div class="woocommerce-layout__primary" id="woocommerce-layout__primary">
            <div class="woocommerce-layout__main">
                <div class="woocommerce-homescreen">
                    <div class="woocommerce-task-dashboard__container">
                        <?php
                        if (isset($_GET['testar_comunicacao'])) {
                            function oAuthTestKangu2()
                            {
                                $consumer_key = 'ck_' . wc_rand_hash();
                                $data = array(
                                    'user_id'         => get_current_user_id(),
                                    'description'     => 'KanguAPITest',
                                    'permissions'     => 'read_write',
                                    'consumer_key'    => wc_api_hash( $consumer_key ),
                                    'consumer_secret' => 'cs_' . wc_rand_hash(),
                                    'truncated_key'   => substr( $consumer_key, -7 ),
                                );

                                global $wpdb;

                                $wpdb->insert(
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
                                $key_id = $wpdb->insert_id;
                                if (!$key_id) {
                                    return true;
                                }

                                unset($data['user_id'], $data['description'], $data['permissions'], $data['truncated_key']);

                                $data['base_url']       = urlencode(get_site_url());
                                $data['consumer_key']   = $consumer_key;
                                $kanguUrl = sprintf('https://portal.kangu.com.br/tms/woocommerce/verify-site-communication?%s', rawurldecode(http_build_query($data)));
                                                                
                                $result = wp_remote_post($kanguUrl, [
                                    'body'      => json_encode([]),
                                    'timeout'   => 120,
                                    'headers'   => [
                                        'Content-Type' => 'application/json; charset=utf-8'
                                    ]
                                ]);
                                $json = json_decode($result["body"]);
                                  
                                if($json->code != 200) {
                                    echo "<h2 style='text-align: center;'>Erro ao testar comunicação com a Kangu</h2>";
                                    echo "
                                        <h4 style='text-align: center;'>
                                                A Kangu tentou acessar o seu servidor no endereço: " . get_site_url() . "/wp-json e recebeu a mensagem:<br><br>
                                                " . $json->code . " - " . $json->message . "
                                        </h4>
                                   ";
                                } else {
                                    echo "<h2>Comunicação com a Kangu realizada com sucesso</h2>";
                                }
                                $wpdb->delete($wpdb->prefix . 'woocommerce_api_keys', array( 'key_id' => $key_id ) , array('%d'));
                            }
                            oAuthTestKangu2();
                            die();
                        }
                        ?>

                        <?php
                            $result = wp_remote_post('https://www.howsmyssl.com/a/check', [
                                'body'      => json_encode([]),
                                'timeout'   => 120,
                                'headers'   => [
                                    'Content-Type' => 'application/json; charset=utf-8'
                                ]
                            ]);
                            $json = json_decode($result["body"], true);
                            if((float)str_replace("TLS ", "", $json["tls_version"]) < 1.2) {
                        ?>
                            <div class="components-card is-size-medium woocommerce-task-card">
                                <div class="woocommerce-task__recommended-ribbon" style="background: red;font-weight: bold;">
                                    <span><?php echo __('ATENÇÃO', 'rpship') ?></span>
                                </div>
                                <div class="components-card__body is-size-medium">
                                    <p>
                                        Aparentemente seu servidor esta configurado com o Protocolo TLS na versão <?php echo $json["tls_version"]; ?> e a Kangu suporta a versão 1.2 ou superior.
                                    </p>
                                    <p>
                                        Para maiores informações,
                                        <a href="https://ajuda.kangu.com.br/hc/pt-br/articles/19837545722903-Atualiza%C3%A7%C3%A3o-de-Seguran%C3%A7a-Kangu-Transi%C3%A7%C3%A3o-para-TLS-v1-2-ou-Superior">clique aqui</a>
                                    </p>
                                </div>
                            </div>
                        <?php } ?>

                        <?php
                            $result = wp_remote_post('https://portal.kangu.com.br/tms/transporte/woocommerce-simular', [
                                'body'      => '',
                                'timeout'   => 120,
                                'headers'   => [
                                    'Content-Type' => 'application/json; charset=utf-8',
                                    'token' => ''
                                ]
                            ]);
                        
                        if($result['response']['code'] == 403) {
                            $result = wp_remote_post('https://meuip.com.br', [
                                'body'      => json_encode([]),
                                'timeout'   => 120,
                                'headers'   => [
                                    'Content-Type' => 'application/json; charset=utf-8'
                                ]
                            ]);
                            $meuIpParser = explode('Meu ip é ', $result['body']);
                            $serverIP = explode('</h3>', $meuIpParser[1]);
                        ?>
                            <div class="components-card is-size-medium woocommerce-task-card">
                                <div class="woocommerce-task__recommended-ribbon" style="background: red;font-weight: bold;">
                                    <span><?php echo __('ATENÇÃO', 'rpship') ?></span>
                                </div>
                                <div class="components-card__body is-size-medium">
                                    <p>
                                        Não é possível efetuar a comunicação do seu site com o Portal Kangu.
                                    </p>
                                    <p>
                                        Possivelmente você está hospedado em um servidor internacional que não faz parte dos paises liberados na Kangu.
                                    </p>
                                    <p>
                                        Por favor abra um chamado em nosso portal solicitando a liberação do acesso e informe o IP do seu servidor.
                                    </p>
                                    <p>
                                        Estamos identificando seu IP como: 
                                        <strong><?php echo esc_html(sanitize_text_field(wp_unslash($serverIP[0]))); ?></strong>
                                        , porém você deve verificar o IP com seu provedor de hospedagem se o IP está correto.
                                    </p>
                                </div>
                            </div>
                        <?php
                        }
                        ?>

                        <?php if (empty($this->get_setting('token_kangu')) || empty($this->get_setting('kangu_version'))) : ?>
                            <?php if (isset($_GET['error_oauth'])) : ?>
                                <div class="components-card is-size-medium woocommerce-task-card">
                                    <div class="components-card__body is-size-medium is-size-full">
                                        <div style="color: #d63638; font-size: 18px;"><?php echo __('Ocorreu um erro ao criar as chaves de autenticação. <br /> Por favor, tente novamente!', 'rpship') ?></div>
                                        <p></p>
                                        <div><?php echo __('Se o erro persistir, por favor, contate o <a href="https://www.kangu.com.br/contato" target="_blank">Suporte Kangu</a>', 'rpship'); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="components-card is-size-medium woocommerce-task-card">
                                <div class="components-card__body is-size-medium is-size-full">
                                    <div><?php echo __('Habilite a cotação de fretes, importação e rastreamento de pedidos <br /> autenticando o APP da Kangu!', 'rpship') ?></div>
                                    <div class="components-button-custom auto">
                                        <a href="<?php echo esc_url($auth_url); ?>" class="button button-primary"><?php echo __('Autenticar APP', 'rpship') ?></a>
                                    </div>
                                </div>
                            </div>
                        <?php elseif (isset($_GET['etiqueta'])) : ?>
                            <h1>ETIQUETA DO PEDIDO: <?php echo esc_html(sanitize_text_field(wp_unslash($_GET['etiqueta']))); ?></h1>
                            <?php
                            $kangu_settings = get_option('rpship-calculator-setting');
                            if (empty($kangu_settings['token_kangu'])) {
                                echo '<h3>Token Kangu não encontrado!</h3>';
                            } else {
                                $this->settings['token'] = $kangu_settings['token_kangu'];
                                $result = wp_remote_post(
                                    'https://portal.kangu.com.br/tms/transporte/woocommerce-imprimir-etiqueta/pedido/' . esc_html(sanitize_text_field(wp_unslash((int)$_GET['etiqueta']))),
                                    [
                                        'body'      => null,
                                        'timeout'   => 120,
                                        'headers'   => [
                                            'Content-Type' => 'application/json; charset=utf-8',
                                            'token' => $this->settings['token']
                                        ]
                                    ]
                                );

                                if ($result instanceof WP_Error) {
                                    // wc_add_notice($result->get_error_message(), 'error');
                                    echo '
                                        <br><br>
                                        <h3>
                                            Erro desconhecido na comunicação com a Kangu. 
                                            <br><br>
                                            Verificar o Log para identificação correta do Erro
                                            <br><br>
                                            ' . esc_html($result->get_error_message()) . '
                                        </h3>
                                        ';
                                } else {
                                    $data = json_decode($result['body'], true);
                                    if ($result['response']['code'] !== 200) {
                                        echo "<br><br><h3>" . esc_html($data['error']['mensagem']) . "</h3>";
                                    } else {
                                        echo '<iframe src="data:application/pdf;base64,' . esc_attr($data['pdf'])  . '" height="500" width="100%"></iframe>';
                                    }
                                }
                            }
                            ?>
                        <?php else : ?>
                            <div class="components-card is-size-medium woocommerce-task-card card-center">
                                <div>
                                    <?php if (empty($this->get_setting('confirm_config'))) : ?>
                                        <div class="woocommerce-list__item-before woocommerce-list__item-inner woocommerce-task-title">
                                            <div class="woocommerce-task__icon woocommerce-list__item-before woocommerce-task__info">
                                                <svg class="gridicon gridicons-info dops-notice__icon" height="20" width="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#fff">
                                                    <g>
                                                        <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"></path>
                                                    </g>
                                                </svg>
                                            </div>
                                            <div class="woocommerce-list__item-text"><b class="woocommerce-list__item-auth woocommerce-list__item-auth-info"><?php echo __('Estamos quase lá!', 'rpship'); ?></b></div>
                                        </div>
                                        <div><?php echo __('Finalize as configurações abaixo para começar a usar a Kangu', 'rpship'); ?></div>
                                    <?php else : ?>
                                        <div class="woocommerce-list__item-before woocommerce-list__item-inner woocommerce-task-title">
                                            <div class="woocommerce-task__icon woocommerce-list__item-before woocommerce-task__success">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="#fff">
                                                    <path d="M18.3 5.6L9.9 16.9l-4.6-3.4-.9 1.2 5.8 4.3 9.3-12.6z"></path>
                                                </svg>
                                            </div>
                                            <div class="woocommerce-list__item-text"><b class="woocommerce-list__item-auth woocommerce-list__item-auth-success"><?php echo __('Sua loja foi configurada com sucesso!', 'rpship'); ?></b></div>
                                        </div>
                                        <div><?php echo __('Você pode alterar as configurações abaixo a qualquer momento.', 'rpship'); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="components-card is-size-medium woocommerce-task-card">
                                <div class="woocommerce-task__recommended-ribbon"><span><?php echo __('Recomendado', 'rpship') ?></span></div>
                                <div class="components-card__body is-size-medium">
                                    <h2 class="woocommerce-task-title"><?php echo __('Definir configurações de Peso e Envio Kangu', 'rpship') ?></h2>
                                    <div><?php echo __('Configure as informações de unidade de peso (kg ou g), <br /> medidas padrões de envio e transportadoras a serem habilitadas', 'rpship') ?></div>
                                </div>
                                <div class="components-flex components-card__footer is-borderless is-size-medium">
                                    <div class="components-button-custom">
                                        <a href="https://portal.kangu.com.br/minhas-preferencias" class="button button-primary button-configure-kangu" target="_blank"><?php echo __('Acessar Configurações', 'rpship') ?></a>
                                    </div>
                                </div>
                            </div>
                            <form method="post" action="" name="<?php echo esc_attr(self::$plugin_slug); ?>" id="kangu-settings">
                                <input type="hidden" name="<?php echo esc_attr(self::$plugin_slug); ?>" value="1" />
                                <input type="hidden" name="token_kangu" value="<?php echo esc_attr($this->get_setting('token_kangu')); ?>" />
                                <input type="hidden" name="kangu_version" value="<?php echo esc_attr($this->get_setting('kangu_version')); ?>" />
                                <div class="components-card is-size-medium woocommerce-task-card">
                                    <div class="woocommerce-task__recommended-ribbon"><span><?php echo __('Recomendado', 'rpship') ?></span></div>
                                    <div class="components-card__body is-size-medium">
                                        <h2 class="woocommerce-task-title"><?php echo __('Habilitar o frete Kangu?', 'rpship') ?></h2>
                                        <div><?php echo __('Habilita as opções de envio/retira Kangu para você <br /> <b>economizar até 75% no frete!</b>', 'rpship') ?></div>
                                    </div>
                                    <div class="components-flex components-card__footer is-borderless is-size-medium">
                                        <label class="toggle-control">
                                            <input type="checkbox" name="kangu_enabled" <?php echo esc_attr(($this->get_setting('kangu_enabled') == 1) ? 'checked' : ''); ?> value="1">
                                            <span class="control"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="components-card is-size-medium woocommerce-task-card">
                                    <div class="woocommerce-task__recommended-ribbon"><span><?php echo __('Recomendado', 'rpship') ?></span></div>
                                    <div class="components-card__body is-size-medium">
                                        <h2 class="woocommerce-task-title"><?php echo __('Habilitar cálculo do frete na página dos Produtos?', 'rpship') ?></h2>
                                        <div><?php echo __('Se habilitado, a calculadora de fretes Kangu será exibida <br /> na página dos produtos.', 'rpship') ?></div>
                                    </div>
                                    <div class="components-flex components-card__footer is-borderless is-size-medium">
                                        <label class="toggle-control">
                                            <input type="checkbox" name="enable_on_productpage" <?php echo esc_attr(($this->get_setting('enable_on_productpage') == 1) ? 'checked' : ''); ?> value="1">
                                            <span class="control"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="components-card is-size-medium woocommerce-task-card">
                                    <div class="components-card__body is-size-medium">
                                        <h2 class="woocommerce-task-title"><?php echo __('Ocultar o campo País no carrinho?', 'rpship') ?></h2>
                                        <div><?php echo __('Se habilitado, o campo de seleção do país será ocultado <br /> e o Brasil será definido como país padrão.', 'rpship') ?></div>
                                    </div>
                                    <div class="components-flex components-card__footer is-borderless is-size-medium">
                                        <label class="toggle-control">
                                            <input type="checkbox" name="hide_country_on_cart" <?php echo esc_attr(($this->get_setting('hide_country_on_cart') == 1) ? 'checked' : ''); ?> value="1">
                                            <span class="control"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="components-card is-size-medium woocommerce-task-card">
                                    <div class="components-card__body is-size-medium">
                                        <h2 class="woocommerce-task-title"><?php echo __('Ocultar o campo Estado no carrinho?', 'rpship') ?></h2>
                                        <div><?php echo __('Se habilitado, o campo de seleção do estado será ocultado. <br /> Para o cálculo do frete, a Kangu utiliza o CEP, portanto, <br /> esse campo não é necessário.', 'rpship') ?></div>
                                    </div>
                                    <div class="components-flex components-card__footer is-borderless is-size-medium">
                                        <label class="toggle-control">
                                            <input type="checkbox" name="hide_state_on_cart" <?php echo esc_attr(($this->get_setting('hide_state_on_cart') == 1) ? 'checked' : ''); ?> value="1">
                                            <span class="control"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="components-card is-size-medium woocommerce-task-card">
                                    <div class="components-card__body is-size-medium">
                                        <h2 class="woocommerce-task-title"><?php echo __('Ocultar o campo Cidade no carrinho?', 'rpship') ?></h2>
                                        <div><?php echo __('Se habilitado, o campo cidade será ocultado. <br /> Para o cálculo do frete, a Kangu utiliza o CEP, portanto, <br /> esse campo não é necessário.', 'rpship') ?></div>
                                    </div>
                                    <div class="components-flex components-card__footer is-borderless is-size-medium">
                                        <label class="toggle-control">
                                            <input type="checkbox" name="hide_city_on_cart" <?php echo esc_attr(($this->get_setting('hide_city_on_cart') == 1) ? 'checked' : ''); ?> value="1">
                                            <span class="control"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="components-card is-size-medium woocommerce-task-card">
                                    <div class="components-card__body is-size-medium is-size-full">
                                        <h2 class="woocommerce-task-title"><?php echo __('Adicionar prazo sobre o frete?', 'rpship') ?></h2>
                                        <div><input type="number" name="add_day" value="<?php echo esc_attr($this->get_setting('add_day')); ?>" placeholder="Prazo adicional" /></div>
                                        <div><?php echo __('<small>O prazo informado será acrescentado sobre as opções de Envio/Retira Kangu</small>', 'rpship') ?></div>
                                    </div>
                                </div>

                                <div class="components-card is-size-medium woocommerce-task-card">
                                    <div class="components-card__body is-size-medium is-size-full">
                                        <h2 class="woocommerce-task-title"><?php echo __('Adicionar valor sobre o frete?', 'rpship') ?></h2>
                                        <div><input type="tel" name="add_price" class="add-price" value="<?php echo esc_attr($this->get_setting('add_price')); ?>" placeholder="Insira o valor e pressione enter" /></div>
                                        <div><?php echo __('<small>O valor informado será acrescentado sobre as opções de Envio/Retira Kangu</small>', 'rpship') ?></div>
                                    </div>
                                </div>
                            </form>

                            <div class="components-card is-size-medium woocommerce-task-card">
                                <div class="components-card__body is-size-medium">
                                    <h2 class="woocommerce-task-title"><?php echo __('Remover token Kangu', 'rpship') ?></h2>
                                    <div><?php echo __('Remova o token e reconfigure a integração com a Kangu', 'rpship') ?></div>
                                </div>
                                <div class="components-flex components-card__footer is-borderless is-size-medium">
                                    <div class="components-button-custom">
                                        <span class="button button-primary button-clear-token-kangu" target="_blank"><?php echo __('Remover Token', 'rpship') ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .wp-core-ui .notice.is-dismissible {
        display: none
    }
</style>
<script>
    jQuery('#kangu-settings input').on('change', function() {
        jQuery.ajax({
            url: window.location.href + '&ajax_settings=' + true,
            method: 'POST',
            data: jQuery('#kangu-settings').serialize(),
            success: function() {
                jQuery.toast({
                    heading: 'Sucesso',
                    icon: 'success',
                    text: 'Configurações salvas com sucesso!',
                    position: 'bottom-center',
                    loader: true,
                    loaderBg: '#fff'
                })
            },
            error: function() {
                jQuery.toast({
                    heading: 'Erro',
                    icon: 'warning',
                    text: 'Ops! Ocorreu um erro ao salvar as configurações. Por favor, Tente novamente',
                    position: 'bottom-center',
                    loader: true,
                    loaderBg: '#fff'
                })

                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            }
        });
    });

    jQuery('.button-configure-kangu').on('click', function() {
        jQuery.ajax({
            url: window.location.href + '&confirm_config=1&ajax_settings=' + true,
            method: 'POST',
            data: jQuery('#kangu-settings').serialize(),
            success: function() {
                window.location.reload();
            }
        });
    });

    jQuery('.button-clear-token-kangu').on('click', function() {
        window.location.replace(window.location.href + '&clear_token=1');
    });

    jQuery('#kangu-settings .add-price').on('keyup', function() {
        jQuery(this).mask("#.##0,00", {
            reverse: true
        });
    });
</script>
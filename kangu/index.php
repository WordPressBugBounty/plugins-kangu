<?php

/**
  * Plugin Name:          Kangu - Se vai de Kangu, vai bem!
  * Plugin URI:           https://www.kangu.com.br/woocommerce
  * Description:          Diversas transportadoras e pontos de retirada para você economize até 75% no frete!
  * Version:              2.3.2
  * Author:               Kangu
  * License:              GPLv3 or later
  * WC requires at least: 3.0
  * WC tested up to:      4.4
 */

global $rpship_plugin_url, $rpship_plugin_dir;

$rpship_plugin_dir = dirname(__FILE__) . "/";
$rpship_plugin_url = plugins_url()."/" . basename($rpship_plugin_dir) . "/";
include_once $rpship_plugin_dir.'lib/kangu.php';
include_once $rpship_plugin_dir.'lib/main.php';

add_action('before_woocommerce_init', function() {
  if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
      \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
  }
});

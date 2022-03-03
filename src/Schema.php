<?php

namespace Netzstrategen\WoocommerceDynamicShippingInfo;

/**
 * Generic plugin lifetime and maintenance functionality.
 */
class Schema {

  /**
   * Registers activation hook callback.
   */
  public static function activate() {
  }

  /**
   * Registers deactivation hook callback.
   */
  public static function deactivate() {
  }

  /**
   * Registers uninstall hook callback.
   */
  public static function uninstall() {
  }

  /**
   * Changes ACF field names, removes obsolete options.
   *
   * `wp eval 'Netzstrategen\WoocommerceDynamicShippingInfo\Schema::update20220303();'`
   */
  public static function update20220303() {
    global $wpdb;

    $wpdb->query("UPDATE wp_options SET option_name = REPLACE(REPLACE(option_name, '_dynamic_shipping_info_', '-'), 'shipping_class_inner_rules', 'conditions') WHERE option_name LIKE '%_woocommerce-dynamic-shipping-info_dynamic_shipping_info%'");
    $wpdb->query("DELETE FROM wp_options WHERE option_name LIKE '_woocommerce-dynamic-shipping-info_price%'");
  }

}

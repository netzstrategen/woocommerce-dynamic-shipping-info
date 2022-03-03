<?php

/**
 * @file
 * Contains \Netzstrategen\WoocommerceDynamicShippingInfo\Admin.
 */

namespace Netzstrategen\WoocommerceDynamicShippingInfo;

/**
 * Administrative back-end functionality.
 */
class Admin {

  /**
   * @implements admin_menu
   */
  public static function menu() {
    self::add_options_page();
  }

  /**
   * Adds plugin settings page.
   */
  public static function add_options_page() {
    acf_add_options_page([
      'page_title' => __('Dynamic Shipping Info', Plugin::L10N),
      'menu_title' => __('Dynamic Shipping Info', Plugin::L10N),
      'menu_slug' => 'dynamic-shipping-info',
      'capability' => 'manage_woocommerce',
      'redirect' => FALSE,
      'parent_slug' => 'woocommerce',
    ]);
  }

}

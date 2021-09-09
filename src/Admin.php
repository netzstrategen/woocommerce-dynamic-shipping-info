<?php

/**
 * @file
 * Contains \Netzstrategen\WoocommerceDynamicShippingInfo\Admin.
 */

namespace Netzstrategen\WoocommerceDynamicShippingInfo;

use WC_Admin_Settings;

/**
 * Administrative back-end functionality.
 */
class Admin {

  /**
   * Adds plugin settings tab and fields.
   *
   * @param array $settings
   *   The WooCommerce settings.
   *
   * @return array
   *   The extended WooCommerce settings.
   */
  public static function woocommerce_get_settings_pages(array $settings): array {
    add_action('woocommerce_settings_tabs_array', __CLASS__ . '::woocommerce_settings_tabs_array', 30);
    add_action('woocommerce_settings_dynamic_shipping_info', __CLASS__ . '::woocommerce_settings_dynamic_shipping_info');
    add_action('woocommerce_settings_save_dynamic_shipping_info', __CLASS__ . '::woocommerce_settings_save_dynamic_shipping_info');
    return $settings;
  }

  /**
   * Defines plugin configuration settings.
   */
  public static function getSettings(): array {
    return apply_filters('woocommerce_get_settings_dynamic_shipping_info', []);
  }

  /**
   * Adds a 'Dynamic Shipping Info' section tab.
   *
   * @implements woocommerce_settings_tabs_array
   */
  public static function woocommerce_settings_tabs_array(array $tabs): array {
    $tabs['dynamic_shipping_info'] = __('Dynamic Shipping Info', Plugin::L10N);
    return $tabs;
  }

  /**
   * Adds settings fields to corresponding WooCommerce settings section.
   *
   * @implements woocommerce_settings_<current_tab>
   */
  public static function woocommerce_settings_dynamic_shipping_info() {
    $settings = static::getSettings();
    WC_Admin_Settings::output_fields($settings);
  }

  /**
   * Triggers setting save.
   *
   * @implements woocommerce_settings_save_<current_tab>
   */
  public static function woocommerce_settings_save_dynamic_shipping_info() {
    $settings = static::getSettings();
    WC_Admin_Settings::save_fields($settings);
  }

  /**
   * Creates plugin settings fields.
   *
   * @param array $settings
   *   The WooCommerce settings.
   *
   * @return array
   *   Extended WooCommerce settings.
   *
   * @implements woocommerce_get_settings_dynamic_shipping
   */
  public static function woocommerce_get_settings_dynamic_shipping_info(array $settings): array {
    $settings[] = [
      'type' => 'title',
      'name' => __('Dynamic Shipping Info Settings', Plugin::L10N),
    ];
    for ($i = 0; $i <= Plugin::LIMITS; $i++) {
      if ($i === 0) {
        $settings[] = [
          'id' => '_' . Plugin::PREFIX . '_price_step_info_0',
          'type' => 'textarea',
          'name' => __('Shipping info:', Plugin::L10N),
          'desc_tip' => __('Text label to display automatically when price is below first price step.', Plugin::L10N),
        ];
        continue;
      }
      $settings[] = [
        'id' => '_' . Plugin::PREFIX . '_price_limit_step_' . $i,
        'type' => 'text',
        'name' => __('Price limit step ' . $i .':', Plugin::L10N),
        'desc_tip' => __('The price limit step over which the shipping info will change to the one below.', Plugin::L10N),
      ];
      $settings[] = [
        'id' => '_' . Plugin::PREFIX . '_price_step_info_' . $i,
        'type' => 'textarea',
        'name' => __('Shipping info:', Plugin::L10N),
        'desc_tip' => __('Text label to display automatically when price is above previous price step.', Plugin::L10N),
      ];
    }
    $settings[] = [
      'type' => 'sectionend',
      'id' => Plugin::PREFIX,
    ];
    return $settings;
  }

}

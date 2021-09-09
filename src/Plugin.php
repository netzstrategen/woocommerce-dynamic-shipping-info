<?php

namespace Netzstrategen\WoocommerceDynamicShippingInfo;

/**
 * Main front-end functionality.
 */
class Plugin {

  /**
   * Prefix for naming.
   *
   * @var string
   */
  const PREFIX = 'woocommerce-dynamic-shipping-info';

  /**
   * Gettext localization domain.
   *
   * @var string
   */
  const L10N = self::PREFIX;

  /**
   * Number of price limit fields shown in settings.
   *
   * @var int
   */
  const LIMITS = 3;

  /**
   * Plugin initialization method.
   *
   * @implements init
   */
  public static function init() {
    add_filter('woocommerce_get_settings_dynamic_shipping_info', __NAMESPACE__ . '\Admin::woocommerce_get_settings_dynamic_shipping_info');
    if (is_plugin_active('woocommerce-german-market/WooCommerce-German-Market.php')) {
      add_filter('gm_get_shipping_page_link_return_string', __CLASS__ . '::gm_get_shipping_page_link_return_string', 10, 3);
    }
  }

  /**
   * Overwrites WGM default shipping info according to price range steps.
   *
   * @implements gm_get_shipping_page_link_return_string
   */
  public static function gm_get_shipping_page_link_return_string($text, $product, $attributes) {
    // Checks if Alternative Shipping Information is already filled at the product level (prefers to use product level detail).
    $wgm_fallback_shipping = sprintf(__('plus <a %s>shipping</a>', 'woocommerce-german-market'), implode(' ', $attributes));
    if ($text !== $wgm_fallback_shipping) {
      return $text;
    }

    // If product level detail Alternative Shipping Information is not used then go through shop level rules.
    $price = $product->get_price();
    $previous_price_step = 0;
    for ($i = 0; $i <= Plugin::LIMITS; $i++) {
      $price_step = get_option('_' . Plugin::PREFIX . '_price_limit_step_' . ($i + 1));
      $shipping_info = get_option('_' . Plugin::PREFIX . '_price_step_info_' . $i);
      if ($i > 0) {
        $previous_price_step = get_option('_' . Plugin::PREFIX . '_price_limit_step_' . $i);
      }
      if ($price_step) {
        if ($price < $price_step && $price >= $previous_price_step) {
          $text = $shipping_info ?: $text;
        }
      }
      else {
        $price_step = get_option('_' . Plugin::PREFIX . '_price_limit_step_' . $i);
        if ($price_step && $shipping_info && $price > $price_step) {
          $text = $shipping_info;
        }
      }
    }
    return $text;
  }

  /**
   * Loads the plugin textdomain.
   */
  public static function loadTextdomain() {
    load_plugin_textdomain(static::L10N, FALSE, static::L10N . '/languages/');
  }

}

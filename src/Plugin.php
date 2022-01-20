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
   * Plugin initialization method.
   *
   * @implements init
   */
  public static function init() {
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

    return self::get_product_dynamic_shipping_text($product);

  }

  /**
   * Determines the first matching shipping rule.
   *
   * @param WC_Product $product
   *   The product to check rules against.
   *
   * @return string
   *   The shipping info text to be outputed.
   */
  public static function get_product_dynamic_shipping_text(\WC_Product $product): string {
    $dynmic_shipping_rules = Admin::get_dynamic_shipping_rules();
    $shipping_country = WC()->customer->get_shipping_country();
    $product_shipping_class = $product->get_shipping_class();
    $price = wc_get_price_to_display($product);

    foreach ($dynmic_shipping_rules as $dynamic_rule) {
      // Check if shipping class is empty (no class) or if its defined in rules.
      if (empty($dynamic_rule['shipping_class']) || in_array($product_shipping_class, $dynamic_rule['shipping_class'])) {
        $sorted_shipping_rules = Plugin::get_sorted_by_price_shipping_rules($dynamic_rule['shipping_class_inner_rules']);
        foreach ($sorted_shipping_rules as $shipping_rule) {
          if (in_array($shipping_country, $shipping_rule['country']) && $price >= $shipping_rule['min_price']) {
            return $shipping_rule['shipping_info'];
          }
        }

      }

    }
    return "";

  }

  /**
   * Gets shipping class inner rules sorted by price.
   *
   * @param array $shipping_rules
   *   Array of Shipping class inner rules.
   *
   * @return array
   *   Sorted by price array of shipping class inner rules.
   */
  public static function get_sorted_by_price_shipping_rules($shipping_rules) {
    usort($shipping_rules, function ($a, $b) {
      return $b['min_price'] <=> $a['min_price'];
    });
    return $shipping_rules;

  }

  /**
   * Loads the plugin textdomain.
   */
  public static function loadTextdomain() {
    load_plugin_textdomain(static::L10N, FALSE, static::L10N . '/languages/');
  }

}

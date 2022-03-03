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
   * @implements init
   */
  public static function init() {
    add_filter('gm_get_shipping_page_link_return_string', __CLASS__ . '::gm_get_shipping_page_link_return_string', 10, 3);
  }

  /**
   * Overwrites WGM default shipping info according to price range steps.
   *
   * @implements gm_get_shipping_page_link_return_string
   */
  public static function gm_get_shipping_page_link_return_string($text, $product, $attributes) {
    $wgm_fallback_shipping = sprintf(__('plus <a %s>shipping</a>', 'woocommerce-german-market'), implode(' ', $attributes));
    $customer = WC()->customer;

    // Do not override the shipping info if specific shipping info has been set
    // for the product or after the customer entered address data in checkout.
    if ($text !== $wgm_fallback_shipping || !$customer) {
      return $text;
    }

    return self::get_product_dynamic_shipping_text($product, $customer) ?: $wgm_fallback_shipping;

  }

  /**
   * Determines the first matching shipping rule.
   *
   * @param WC_Product $product
   *   The product to check rules against.
   * @param WC_Customer $customer
   *   Current customer in session.
   *
   * @return string
   *   The shipping info text to be outputed.
   */
  public static function get_product_dynamic_shipping_text(\WC_Product $product, \WC_Customer $customer): string {
    static $cache = [];

    // This code path is invoked 7 times (per variant) on a product detail page.
    if (isset($cache[$product->get_id()])) {
      return $cache[$product->get_id()];
    }

    $dynmic_shipping_rules = Admin::get_dynamic_shipping_rules();
    $shipping_country = $customer->get_shipping_country();
    $product_shipping_class = $product->get_shipping_class();
    $price = wc_get_price_to_display($product);
    $product_brands = wc_get_product_terms($product->get_id(), 'pa_marken', ['fields' => 'ids']);

    $cache[$product->get_id()] = '';
    foreach ($dynmic_shipping_rules as $dynamic_rule) {
      // Check if both product rule have no shipping class or match.
      if ((empty($dynamic_rule['shipping_class']) && empty($product_shipping_class)) || in_array($product_shipping_class, $dynamic_rule['shipping_class'], TRUE)) {
        // Sort inner rules by prices descending.
        usort($dynamic_rule['shipping_class_inner_rules'], function ($a, $b) {
          return $b['min_price'] <=> $a['min_price'];
        });
        foreach ($dynamic_rule['shipping_class_inner_rules'] as $shipping_rule) {
          if ($price >= $shipping_rule['min_price']
            && in_array($shipping_country, $shipping_rule['country'], TRUE)
            && (empty($shipping_rule['brands']) || array_intersect($product_brands, $shipping_rule['brands']))) {
            $cache[$product->get_id()] = $shipping_rule['shipping_info'];
            break 2;
          }
        }
      }
    }
    return $cache[$product->get_id()];
  }

  /**
   * Loads the plugin textdomain.
   */
  public static function loadTextdomain() {
    load_plugin_textdomain(static::L10N, FALSE, static::L10N . '/languages/');
  }

}

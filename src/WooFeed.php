<?php

namespace Netzstrategen\WoocommerceDynamicShippingInfo;

/**
 * Integration with Webappick Product Feed / CTX Feed plugin.
 *
 * Test feed generation on command line:
 * ```
 * wp eval '$name = "wf_config" . "google_de"; $config = get_option($name); woo_feed_generate_feed($config, $name);'
 * ```
 */
class WooFeed {

  /**
   * Adds shipping_info to available product feed attributes.
   *
   * @implements woo_feed_product_attribute_dropdown
   */
  public static function woo_feed_product_attribute_dropdown(array $attributes): array {
    $group_id = 60;
    while (isset($attributes['--' . $group_id])) {
      $group_id++;
    }
    $attributes["--$group_id"] = 'Dynamic Shipping Info';
    $attributes['dynamic_shipping_info'] = 'Dynamic Shipping Info';
    $attributes["---$group_id"] = '';
    return $attributes;
  }

  /**
   * Generates a product feed value for attribute 'dynamic_shipping_info'.
   *
   * @implements woo_feed_get_{$attribute}_attribute
   */
  public static function woo_feed_get_dynamic_shipping_info_attribute(string $output, \WC_Product $product, array $config): string {
    $shipping_country = $config['feed_country'] ?? '';
    $customer = new \WC_Customer();
    $customer->set_shipping_country($shipping_country);
    return Plugin::get_product_dynamic_shipping_text($product, $customer);
  }

}

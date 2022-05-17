<?php

/**
 * Returns the first matching shipping rule given a product ID and shipping country.
 *
 * Used as dynamic value callback for XML product feeds.
 *
 * @param int $product_id
 *   The product ID to check rules against.
 * @param string $shipping_country
 *   The shipping country code; e.g., "DE".
 *
 * @return string
 *   The shipping info text to be output.
 */
function get_product_dynamic_shipping_text(int $product_id, string $shipping_country): string {
  $product = wc_get_product($product_id);
  $customer = new \WC_Customer();
  $customer->set_shipping_country($shipping_country);
  return Netzstrategen\WoocommerceDynamicShippingInfo\Plugin::get_product_dynamic_shipping_text($product, $customer);
}


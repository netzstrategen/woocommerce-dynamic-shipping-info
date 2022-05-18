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
  const L10N = Plugin::PREFIX;

  /**
   * ACF Option field name containing all rules.
   *
   * @var string
   */
  const FIELD_SHIPPING_CLASS = Plugin::PREFIX . '-rules';

  /**
   * The actual product (variation) currently processed by WGM_Shipping.
   *
   * @var WC_Product
   */
  private static $product;

  /**
   * @implements init
   */
  public static function init() {
    if (!is_callable('acf_add_local_field_group')) {
      return;
    }
    Plugin::add_field_group();

    add_action('wgm_before_shipping_fee_single', __CLASS__ . '::wgm_before_shipping_fee_single');
    add_filter('gm_get_shipping_page_link_return_string', __CLASS__ . '::gm_get_shipping_page_link_return_string', 10, 3);

    // Expose shipping info to webappick-product-feed-for-woocommerce plugin.
    add_filter('woo_feed_product_attribute_dropdown', __NAMESPACE__ . '\WooFeed::woo_feed_product_attribute_dropdown', 100);
    add_filter('woo_feed_get_dynamic_shipping_info_attribute', __NAMESPACE__ . '\WooFeed::woo_feed_get_dynamic_shipping_info_attribute', 10, 3);
  }

  /**
   * Adds plugin settings fields.
   */
  public static function add_field_group() {
    acf_add_local_field_group(
      [
        'key' => Plugin::PREFIX . '_acf_field_group',
        'title' => __('Rules', Plugin::L10N),
        'fields' => [
          [
            'key' => 'rules',
            'name' => Plugin::FIELD_SHIPPING_CLASS,
            'type' => 'repeater',
            'layout' => 'block',
            'button_label' => __('Add shipping class', Plugin::L10N),
            'sub_fields' => [
              [
                'key' => 'shipping_class',
                'label' => __('Shipping Class', Plugin::L10N),
                'instructions' => __('Leave empty to apply this rule to all products without shipping class.', Plugin::L10N),
                'name' => 'shipping_class',
                'type' => 'select',
                'choices' => Plugin::get_shipping_classes(),
                'default_value' => [],
                'allow_null' => 0,
                'multiple' => 1,
                'ui' => 1,
                'return_format' => 'value',
              ],
              [
                'key' => 'conditions',
                'label' => __('Conditions', Plugin::L10N),
                'name' => 'conditions',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => __('Add rule', Plugin::L10N),
                'sub_fields' => [
                  [
                    'key' => 'shipping_info',
                    'label' => __('Shipping info text', Plugin::L10N),
                    'name' => 'shipping_info',
                    'type' => 'text',
                    'required' => 1,
                  ],
                  [
                    'key' => 'min_price',
                    'label' => __('Minimum price', Plugin::L10N),
                    'name' => 'min_price',
                    'type' => 'number',
                    'type' => 'number',
                    'required' => 1,
                  ],
                  [
                    'key' => 'country',
                    'label' => __('Countries', Plugin::L10N),
                    'name' => 'country',
                    'type' => 'select',
                    'choices' => Plugin::get_shipping_countries(),
                    'default_value' => [],
                    'allow_null' => 0,
                    'multiple' => 1,
                    'required' => 1,
                    'ui' => 1,
                    'return_format' => 'value',
                  ],
                  // @see https://www.advancedcustomfields.com/resources/register-fields-via-php/#relational
                  [
                    'key' => 'brands',
                    'label' => __('Brands', Plugin::L10N),
                    'name' => 'brands',
                    'type' => 'taxonomy',
                    'taxonomy' => apply_filters(Plugin::PREFIX . '/rule/brands/taxoomy', 'pa_marken'),
                    'field_type' => 'multi_select',
                    'allow_null' => 1,
                    'required' => 0,
                    'add_term' => 0,
                    'return_format' => 'id',
                  ],
                ],
              ],
            ],
          ],
        ],
        'location' => [
          [
            [
              'param' => 'options_page',
              'operator' => '==',
              'value' => 'dynamic-shipping-info',
            ],
          ],
        ],
      ]
    );
  }

  /**
   * Gets countries that the store ships to.
   *
   * @return array
   *   Array of shipping countries from Woocommerce.
   */
  public static function get_shipping_countries(): array {
    return WC()->countries->get_shipping_countries();
  }

  /**
   * Get array of slug and name value pair of Shipping classes.
   *
   * @return array
   *   Array containing the slug and name of the shipping class.
   */
  public static function get_shipping_classes(): array {
    return array_reduce(WC()->shipping->get_shipping_classes() ?? [], function ($result, $item) {
      $result[$item->slug] = $item->name;
      return $result;
    }, []);
  }

  /**
   * Stores currently processed product for gm_get_shipping_page_link_return_string.
   *
   * For variations, WGM_Shipping::get_shipping_page_link() replaces the passed
   * $product with its parent product before invoking the filter
   * gm_get_shipping_page_link_return_string.
   *
   * @implements wgm_before_shipping_fee_single
   */
  public static function wgm_before_shipping_fee_single($product) {
    static::$product = $product;
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

    return Plugin::get_product_dynamic_shipping_text(static::$product, $customer) ?: $wgm_fallback_shipping;
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

    // This code path is invoked twice per variant on a product detail page.
    if (isset($cache[$product->get_id()])) {
      return $cache[$product->get_id()];
    }

    $rules = Plugin::getRules();
    $shipping_country = $customer->get_shipping_country();
    $product_shipping_class = $product->get_shipping_class();
    $price = wc_get_price_to_display($product);
    $parent_product_id = $product->get_type() === 'variation' ? $product->get_parent_id() : $product->get_id();
    $product_brands = wc_get_product_terms($parent_product_id, apply_filters(Plugin::PREFIX . '/rule/brands/taxonomy', 'pa_marken'), ['fields' => 'ids']);

    $cache[$product->get_id()] = '';
    foreach ($rules as $rule) {
      // Only apply the rule if product has no shipping class or it matches.
      if ((empty($rule['shipping_class']) && empty($product_shipping_class)) || in_array($product_shipping_class, $rule['shipping_class'], TRUE)) {
        // Sort conditions by prices descending.
        usort($rule['conditions'], function ($a, $b) {
          return $b['min_price'] <=> $a['min_price'];
        });
        foreach ($rule['conditions'] as $shipping_rule) {
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
   * Gets dynamic shipping info rules.
   *
   * @return array
   *   Array of defined dynamic shipping info rules.
   */
  public static function getRules(): array {
    return get_field(Plugin::FIELD_SHIPPING_CLASS, 'option') ?: [];
  }

  /**
   * Loads the plugin textdomain.
   */
  public static function loadTextdomain() {
    load_plugin_textdomain(static::L10N, FALSE, static::L10N . '/languages/');
  }

}

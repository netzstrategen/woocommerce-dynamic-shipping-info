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

  const FIELD_SHIPPING_CLASS = Plugin::PREFIX . '-rules';

  /**
   * @implements admin_init
   */
  public static function init() {
    self::add_options_page();
    self::add_field_group();
  }

  /**
   * Adds plugin settings page.
   */
  public static function add_options_page() {
    acf_add_options_page([
      'page_title' => __('Dynamic Shipping Info', Plugin::L10N),
      'menu_title' => __('Dynamic Shipping Info', Plugin::L10N),
      'menu_slug' => 'dynamic-shipping-info',
      'capability' => 'edit_posts',
      'redirect' => FALSE,
      'parent_slug' => 'woocommerce',
    ]);
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
            'name' => self::FIELD_SHIPPING_CLASS,
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
                'choices' => self::get_shipping_classes(),
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
                'button_label' => __('Add condition', Plugin::L10N),
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
                    'required' => 1,
                  ],
                  [
                    'key' => 'country',
                    'label' => __('Countries', Plugin::L10N),
                    'name' => 'country',
                    'type' => 'select',
                    'choices' => self::get_shipping_countries(),
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
   * Gets dynamic shipping info rules.
   *
   * @return array
   *   Array of defined dynamic shipping info rules.
   */
  public static function get_dynamic_shipping_rules(): array {
    return get_field(self::FIELD_SHIPPING_CLASS, 'option') ?: [];
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

}

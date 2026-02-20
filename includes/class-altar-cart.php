<?php

/**
 * WooCommerce Cart and Order Integration for Altar Configurator
 */

if (! defined('ABSPATH')) {
    exit;
}

class Altar_Cart
{

    public function __construct()
    {
        // Display meta in cart
        add_filter('woocommerce_get_item_data', [$this, 'display_cart_item_meta'], 10, 2);

        // Add preview image to cart item name/thumbnail
        add_filter('woocommerce_cart_item_thumbnail', [$this, 'replace_cart_item_thumbnail'], 10, 3);

        // Save meta to order
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_order_item_meta'], 10, 4);
    }

    /**
     * Display metadata in cart item list
     */
    public function display_cart_item_meta($item_data, $cart_item)
    {
        if (isset($cart_item['altar_config'])) {
            $config = $cart_item['altar_config'];

            if (!empty($config['item_type'])) {
                $item_data[] = [
                    'key'   => __('Type', 'altar-configurator'),
                    'value' => ucfirst($config['item_type']),
                ];
            }

            if (!empty($config['preview_url'])) {
                $item_data[] = [
                    'key'   => __('Preview', 'altar-configurator'),
                    'value' => '<a href="' . esc_url($config['preview_url']) . '" target="_blank">' . __('View Image', 'altar-configurator') . '</a>',
                ];
            }
        }
        return $item_data;
    }

    /**
     * Replace product thumbnail with Altar Preview
     */
    public function replace_cart_item_thumbnail($thumbnail, $cart_item, $cart_item_key)
    {
        if (isset($cart_item['altar_config']['preview_url'])) {
            $thumbnail = '<img src="' . esc_url($cart_item['altar_config']['preview_url']) . '" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="Altar Preview" style="width:80px; height:auto; border-radius:4px; border:1px solid #ddd; object-fit: cover;">';
        }
        return $thumbnail;
    }

    /**
     * Save meta to order item
     */
    public function save_order_item_meta($item, $cart_item_key, $values, $order)
    {
        if (isset($values['altar_config'])) {
            $config = $values['altar_config'];
            $item->update_meta_data('_altar_preview_url', $config['preview_url']);
            $item->update_meta_data('_altar_layout_json', $config['layout']);
            $item->update_meta_data('_altar_config_id', $config['config_id']);
            $item->update_meta_data('_altar_item_type', $config['item_type']);
        }
    }
}

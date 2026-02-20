<?php

/**
 * AJAX Handler for Altar Configurator
 */

if (! defined('ABSPATH')) {
    exit;
}

class Altar_AJAX
{

    public function __construct()
    {
        add_action('wp_ajax_altar_add_bundle_to_cart', [$this, 'add_bundle_to_cart']);
        add_action('wp_ajax_nopriv_altar_add_bundle_to_cart', [$this, 'add_bundle_to_cart']);

        add_action('wp_ajax_altar_search_products', [$this, 'search_products']);
        add_action('wp_ajax_nopriv_altar_search_products', [$this, 'search_products']);
    }

    /**
     * Search WooCommerce products with Altar Metadata
     */
    public function search_products()
    {
        check_ajax_referer('altar_configurator_nonce', 'nonce');

        $query = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

        $args = [
            'status' => 'publish',
            'limit'  => 20,
            's'      => $query,
            'stock_status' => 'instock',
            'meta_query' => [
                [
                    'key'     => '_altar_overlay_png',
                    'value'   => '',
                    'compare' => '!=',
                ]
            ]
        ];

        $products = wc_get_products($args);
        $results  = [];

        foreach ($products as $product) {
            $results[] = [
                'id'            => $product->get_id(),
                'type'          => $product->get_type(),
                'name'          => $product->get_name(),
                'price_html'    => $product->get_price_html(),
                'image'         => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
                'overlay_png'   => get_post_meta($product->get_id(), '_altar_overlay_png', true),
                'overlay_scale' => get_post_meta($product->get_id(), '_altar_overlay_scale', true),
                'altar_type'    => get_post_meta($product->get_id(), '_altar_type', true),
            ];
        }

        wp_send_json_success($results);
    }

    public function add_bundle_to_cart()
    {
        check_ajax_referer('altar_configurator_nonce', 'nonce');

        $items        = json_decode(stripslashes($_POST['items']), true);
        $preview_data = $_POST['preview_image'];
        $layout_json  = stripslashes($_POST['layout_json']);

        if (empty($items) || ! is_array($items)) {
            wp_send_json_error(__('No items to add.', 'altar-configurator'));
        }

        // 1. Save Preview Image
        $image_url = $this->save_preview_image($preview_data);
        if (! $image_url) {
            wp_send_json_error(__('Failed to save preview image.', 'altar-configurator'));
        }

        // 2. Add to Cart
        if (! did_action('woocommerce_before_calculate_totals') && ! WC()->cart) {
            wc_load_cart();
        }

        $config_id = uniqid('altar_');

        foreach ($items as $item) {
            $product_id   = intval($item['product_id']);
            $variation_id = isset($item['variation_id']) ? intval($item['variation_id']) : 0;
            $quantity     = intval($item['qty']);
            $type         = sanitize_text_field($item['type']);

            // Validate product
            $product = wc_get_product($variation_id ? $variation_id : $product_id);
            if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
                continue; // Skip invalid products
            }

            $cart_item_data = [
                'altar_config' => [
                    'config_id'   => $config_id,
                    'preview_url' => $image_url,
                    'layout'      => $layout_json,
                    'item_type'   => $type,
                ]
            ];

            WC()->cart->add_to_cart($product_id, $quantity, $variation_id, [], $cart_item_data);
        }

        wp_send_json_success([
            'cart_url' => wc_get_cart_url(),
            'message'  => __('Bundle added to cart.', 'altar-configurator')
        ]);
    }

    private function save_preview_image($base64_string)
    {
        $upload_dir = wp_upload_dir();

        // Handle various base64 formats (jpeg, png)
        if (preg_match('/^data:image\/(\w+);base64,/', $base64_string, $type)) {
            $base64_string = substr($base64_string, strpos($base64_string, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, etc.
            if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                return false;
            }
        } else {
            return false;
        }

        $image_data = str_replace(' ', '+', $base64_string);
        $decoded    = base64_decode($image_data);

        if (!$decoded) return false;

        // Check size (max 2MB as requested in TASK)
        if (strlen($decoded) > 2 * 1024 * 1024) {
            return false;
        }

        $extension = $type === 'png' ? 'png' : 'jpg';
        $filename  = 'altar-preview-' . time() . '-' . wp_generate_password(4, false) . '.' . $extension;
        $filepath  = $upload_dir['path'] . '/' . $filename;

        if (file_put_contents($filepath, $decoded)) {
            return $upload_dir['url'] . '/' . $filename;
        }

        return false;
    }
}

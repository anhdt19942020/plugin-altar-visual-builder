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
            $product_id = $product->get_id();
            $overlay_url = get_post_meta($product_id, '_altar_overlay_png', true);
            $image_id = $product->get_image_id();

            // Priority: Featured Image > Altar Overlay > WooCommerce Placeholder
            $image_url = '';
            if ($image_id) {
                $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
            }

            if (!$image_url && !empty($overlay_url)) {
                $image_url = $overlay_url;
            }

            if (!$image_url) {
                $image_url = wc_placeholder_img_src();
            }

            $results[] = [
                'id'            => $product_id,
                'type'          => $product->get_type(),
                'name'          => $product->get_name(),
                'price_html'    => $product->get_price_html(),
                'image'         => $image_url,
                'overlay_png'   => $overlay_url,
                'overlay_scale' => get_post_meta($product_id, '_altar_overlay_scale', true),
                'altar_type'    => get_post_meta($product_id, '_altar_type', true),
                'default_variation_id' => $product->is_type('variable') ? $product->get_children()[0] : 0,
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

        // Debug: Log received items
        error_log('[Altar Configurator] Received items: ' . print_r($items, true));

        // 1. Save Preview Image
        $image_url = $this->save_preview_image($preview_data);
        if (! $image_url) {
            wp_send_json_error(__('Failed to save preview image.', 'altar-configurator'));
        }

        if (! did_action('woocommerce_before_calculate_totals') && ! WC()->cart) {
            wc_load_cart();
        }

        // 2. Clear Cart to avoid duplicates and ensure purely this bundle
        WC()->cart->empty_cart();

        $config_id = uniqid('altar_');
        $added   = [];
        $skipped = [];

        foreach ($items as $item) {
            $product_id   = intval($item['product_id']);
            $variation_id = isset($item['variation_id']) ? intval($item['variation_id']) : 0;
            $quantity     = intval($item['qty']);
            $type         = sanitize_text_field($item['type']);

            // Validate product exists
            $product = wc_get_product($product_id);
            if (!$product) {
                $skipped[] = "ID {$product_id}: Product not found";
                error_log("[Altar Configurator] Product not found: {$product_id}");
                continue;
            }

            // For variable products, we need a valid variation
            if ($product->is_type('variable')) {
                if ($variation_id) {
                    $variation = wc_get_product($variation_id);
                    if (!$variation) {
                        $skipped[] = "ID {$product_id}: Variation {$variation_id} not found";
                        error_log("[Altar Configurator] Variation not found: {$variation_id}");
                        continue;
                    }
                } else {
                    // Auto-select first variation
                    $children = $product->get_children();
                    if (!empty($children)) {
                        $variation_id = $children[0];
                        error_log("[Altar Configurator] Auto-selected variation {$variation_id} for product {$product_id}");
                    } else {
                        $skipped[] = "ID {$product_id}: Variable product has no variations";
                        error_log("[Altar Configurator] Variable product has no variations: {$product_id}");
                        continue;
                    }
                }
            }

            // Check purchasable
            $check_product = $variation_id ? wc_get_product($variation_id) : $product;
            if (!$check_product->is_purchasable()) {
                $skipped[] = "ID {$product_id}: Not purchasable (missing price?)";
                error_log("[Altar Configurator] Not purchasable: {$product_id} (price: " . $check_product->get_price() . ")");
                continue;
            }

            if (!$check_product->is_in_stock()) {
                $skipped[] = "ID {$product_id}: Out of stock";
                error_log("[Altar Configurator] Out of stock: {$product_id}");
                continue;
            }

            $cart_item_data = [
                'altar_config' => [
                    'config_id'   => $config_id,
                    'preview_url' => $image_url,
                    'layout'      => $layout_json,
                    'item_type'   => $type,
                ]
            ];

            $result = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, [], $cart_item_data);

            if ($result) {
                $added[] = "ID {$product_id} (qty: {$quantity})";
                error_log("[Altar Configurator] Added to cart: {$product_id}, qty: {$quantity}, variation: {$variation_id}");
            } else {
                $skipped[] = "ID {$product_id}: WC add_to_cart returned false";
                error_log("[Altar Configurator] add_to_cart FAILED for: {$product_id}");
            }
        }

        error_log('[Altar Configurator] Added: ' . implode(', ', $added));
        error_log('[Altar Configurator] Skipped: ' . implode(', ', $skipped));

        wp_send_json_success([
            'cart_url' => wc_get_cart_url(),
            'message'  => __('Bundle added to cart.', 'altar-configurator'),
            'debug'    => [
                'added'   => $added,
                'skipped' => $skipped,
            ]
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

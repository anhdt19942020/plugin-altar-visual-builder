<?php

/**
 * Admin Product Meta Fields for Altar Configurator
 */

if (! defined('ABSPATH')) {
    exit;
}

class Altar_Admin
{
    public function __construct()
    {
        // Add tab/fields to product edit page
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_altar_fields']);
        add_action('woocommerce_process_product_meta', [$this, 'save_altar_fields']);
    }

    public function add_altar_fields()
    {
        echo '<div class="options_group">';
        echo '<h3>' . __('Altar Configurator Settings', 'altar-configurator') . '</h3>';

        woocommerce_wp_text_input([
            'id'          => '_altar_overlay_png',
            'label'       => __('Overlay PNG URL', 'altar-configurator'),
            'placeholder' => 'https://...',
            'desc_tip'    => 'true',
            'description' => __('URL to transparent PNG image for canvas.', 'altar-configurator'),
        ]);

        woocommerce_wp_text_input([
            'id'          => '_altar_overlay_scale',
            'label'       => __('Default Scale', 'altar-configurator'),
            'placeholder' => '0.5',
            'type'        => 'number',
            'custom_attributes' => ['step' => '0.01', 'min' => '0.01'],
        ]);

        woocommerce_wp_select([
            'id'      => '_altar_type',
            'label'   => __('Altar Item Type', 'altar-configurator'),
            'options' => [
                ''        => __('-- Select Type --', 'altar-configurator'),
                'incense' => __('Incense burner', 'altar-configurator'),
                'cup'     => __('Water cup', 'altar-configurator'),
                'vase'    => __('Flower vase', 'altar-configurator'),
                'other'   => __('Other decoration', 'altar-configurator'),
            ],
        ]);

        echo '</div>';
    }

    public function save_altar_fields($post_id)
    {
        $overlay_png = isset($_POST['_altar_overlay_png']) ? esc_url_raw($_POST['_altar_overlay_png']) : '';
        $scale       = isset($_POST['_altar_overlay_scale']) ? sanitize_text_field($_POST['_altar_overlay_scale']) : '0.5';
        $type        = isset($_POST['_altar_type']) ? sanitize_text_field($_POST['_altar_type']) : '';

        update_post_meta($post_id, '_altar_overlay_png', $overlay_png);
        update_post_meta($post_id, '_altar_overlay_scale', $scale);
        update_post_meta($post_id, '_altar_type', $type);
    }
}

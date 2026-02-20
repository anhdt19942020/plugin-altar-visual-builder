<?php

/**
 * Shortcode Handler for Altar Configurator
 */

if (! defined('ABSPATH')) {
    exit;
}

class Altar_Shortcode
{

    public function __construct()
    {
        add_shortcode('altar_configurator', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }

    public function register_assets()
    {
        wp_register_script('fabric-js', 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js', [], '5.3.1', true);

        wp_register_script(
            'altar-configurator-js',
            ALTAR_CONFIGURATOR_URL . 'assets/js/configurator.js',
            ['fabric-js'],
            ALTAR_CONFIGURATOR_VERSION,
            true
        );

        // Localize config right after registration to ensure it's attached
        wp_localize_script('altar-configurator-js', 'altarConfig', [
            'ajax_url'    => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('altar_configurator_nonce'),
            'messages'    => [
                'empty_canvas'  => __('Please add at least one item to the altar.', 'altar-configurator'),
                'searching'     => __('Searching...', 'altar-configurator'),
                'no_results'    => __('No products found with altar-overlay.', 'altar-configurator'),
                'processing'    => __('Processing...', 'altar-configurator'),
                'success'       => __('Success! Redirecting to cart...', 'altar-configurator'),
                'add_to_canvas' => __('Add to Altar', 'altar-configurator'),
            ]
        ]);

        wp_register_style(
            'altar-configurator-css',
            ALTAR_CONFIGURATOR_URL . 'assets/css/style.css',
            [],
            ALTAR_CONFIGURATOR_VERSION
        );
    }

    public function render_shortcode($atts)
    {
        // Only enqueue if shortcode is present
        wp_enqueue_script('altar-configurator-js');
        wp_enqueue_style('altar-configurator-css');

        ob_start();
?>
        <div id="altar-configurator-container" class="altar-configurator">
            <div class="altar-sidebar">
                <h3><?php _e('Product Search', 'altar-configurator'); ?></h3>

                <div class="altar-search-box">
                    <input type="text" id="altar-search-input" placeholder="<?php esc_attr_e('Search products...', 'altar-configurator'); ?>">
                    <button id="altar-search-btn" class="button"><?php _e('Search', 'altar-configurator'); ?></button>
                </div>

                <div id="altar-product-results" class="item-library">
                    <!-- Results will be injected here via JS -->
                    <p class="search-hint"><?php _e('Search for items like "Bát hương" or "Lọ hoa"...', 'altar-configurator'); ?></p>
                </div>

                <hr>
                <div class="altar-controls">
                    <button id="add-to-cart-btn" class="button alt"><?php _e('Add Bundle to Cart', 'altar-configurator'); ?></button>
                    <div id="altar-status"></div>
                </div>
            </div>

            <div class="altar-canvas-wrapper">
                <canvas id="altar-canvas"></canvas>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}

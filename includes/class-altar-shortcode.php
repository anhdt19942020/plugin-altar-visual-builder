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

        // Preload Google Fonts for premium typography
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';

        ob_start();
?>
        <div id="altar-configurator-container" class="altar-configurator">

            <!-- ═══ LEFT SIDEBAR ═══ -->
            <div class="altar-sidebar">

                <!-- Sidebar Header -->
                <div class="altar-sidebar-header">
                    <h3><?php _e('Vật Phẩm Thờ Cúng', 'altar-configurator'); ?></h3>
                </div>

                <!-- Search Box -->
                <div class="altar-search-box">
                    <input type="text"
                        id="altar-search-input"
                        placeholder="<?php esc_attr_e('Tìm bát hương, lọ hoa…', 'altar-configurator'); ?>"
                        autocomplete="off">
                    <button id="altar-search-btn" aria-label="<?php esc_attr_e('Tìm kiếm', 'altar-configurator'); ?>">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:4px">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                        <?php _e('Tìm', 'altar-configurator'); ?>
                    </button>
                </div>

                <!-- Results -->
                <div id="altar-product-results" class="item-library">
                    <p class="search-hint"><?php _e('Gõ tên vật phẩm rồi bấm Tìm để thêm vào bàn thờ.', 'altar-configurator'); ?></p>
                </div>

                <!-- Add to Cart -->
                <div class="altar-controls">
                    <div class="altar-item-count" id="altar-item-count"></div>
                    <button id="add-to-cart-btn">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:6px">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                            <path d="M3 6h18M16 10a4 4 0 0 1-8 0" />
                        </svg>
                        <?php _e('Thêm Bộ Vào Giỏ Hàng', 'altar-configurator'); ?>
                    </button>
                    <div id="altar-status"></div>
                </div>
            </div>

            <!-- ═══ CANVAS PANEL ═══ -->
            <div class="altar-canvas-wrapper">

                <!-- Canvas Header -->
                <div class="altar-canvas-header">
                    <span class="altar-canvas-title"><?php _e('Bàn Thờ · Không Gian Trang Trí', 'altar-configurator'); ?></span>
                    <span class="altar-canvas-hint"><?php _e('Kéo thả và sắp xếp vật phẩm theo ý muốn', 'altar-configurator'); ?></span>
                </div>

                <!-- Fabric.js Canvas -->
                <canvas id="altar-canvas"></canvas>

                <!-- Canvas Footer -->
                <div class="altar-canvas-footer">
                    <span><?php _e('Chọn vật phẩm để tùy chỉnh', 'altar-configurator'); ?></span>
                    <span><kbd>Del</kbd> <?php _e('Xóa', 'altar-configurator'); ?></span>
                    <span><kbd>Scroll</kbd> <?php _e('Thu phóng', 'altar-configurator'); ?></span>
                </div>
            </div>

        </div>
<?php
        return ob_get_clean();
    }
}

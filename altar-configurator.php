<?php

/**
 * Plugin Name: Altar Configurator
 * Plugin URI: https://github.com/anhdt19942020/plugin-altar-visual-builder
 * Description: A 2D drag-and-drop altar configurator using Fabric.js with WooCommerce integration.
 * Version: 1.0.0
 * Author: Senior Engineer
 * Author URI: https://github.com/anhdt19942020
 * License: MIT
 * Text Domain: altar-configurator
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

// Define plugin constants
define('ALTAR_CONFIGURATOR_PATH', plugin_dir_path(__FILE__));
define('ALTAR_CONFIGURATOR_URL', plugin_dir_url(__FILE__));
define('ALTAR_CONFIGURATOR_VERSION', '1.0.0');

/**
 * Check if WooCommerce is active
 */
function altar_configurator_check_woocommerce()
{
	if (! class_exists('WooCommerce')) {
		add_action('admin_notices', function () {
			echo '<div class="error"><p>' . esc_html__('Altar Configurator requires WooCommerce to be installed and active.', 'altar-configurator') . '</p></div>';
		});
		return false;
	}
	return true;
}

// Main plugin class
class Altar_Configurator
{

	public function __construct()
	{
		add_action('plugins_loaded', [$this, 'init']);
	}

	public function init()
	{
		if (! altar_configurator_check_woocommerce()) {
			return;
		}

		// Phase 1+: Load components
		require_once ALTAR_CONFIGURATOR_PATH . 'includes/class-altar-shortcode.php';
		require_once ALTAR_CONFIGURATOR_PATH . 'includes/class-altar-ajax.php';
		require_once ALTAR_CONFIGURATOR_PATH . 'includes/class-altar-cart.php';
		require_once ALTAR_CONFIGURATOR_PATH . 'includes/class-altar-admin.php';

		new Altar_Shortcode();
		new Altar_AJAX();
		new Altar_Cart();
		new Altar_Admin();
	}
}

new Altar_Configurator();

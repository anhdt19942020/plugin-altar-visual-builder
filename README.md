# WordPress Plugin: Altar Configurator

A 2D drag-and-drop altar configurator for WooCommerce using Fabric.js.

## Description

This plugin allows users to customize their altar setup by dragging and dropping ritual items onto an altar table in 2D.
The final configuration can be added to the WooCommerce cart as a bundle of individual products.

## Features

- Fabric.js based 2D configurator.
- Add/Remove items (Incense burner, Cups, Vases, etc.).
- Drag, Scale, Rotate items.
- Save preview image to the cart.
- WooCommerce integration (guest support).

## How to Use Dynamic System

1.  **Configure Products**:
    - Go to **Products > All Products**.
    - Edit a product you want to appear in the configurator.
    - In the **General** tab, scroll down to **Altar Configurator Settings**.
    - Enter the **Overlay PNG URL** (a transparent background image of the product).
    - (Optional) Set a **Default Scale** (e.g., 0.6) and **Altar Item Type**.
    - Save the product.
2.  **Using the Configurator**:
    - Go to the page containing the `[altar_configurator]` shortcode.
    - Use the **Search Bar** in the sidebar to find the products you configured.
    - Click **Add to Altar** on any product result.
    - Arrange, resize, or delete items on the canvas.
    - Click **Add Bundle to Cart** to add all items to your WooCommerce cart.

## Features

- **Dynamic Search**: No more hardcoded IDs.
- **Z-Index Controls**: Use "Backspace" or "Delete" to remove items.
- **Real-time Preview**: Generates an image of your layout and attaches it to the order.
- **WooCommerce Sync**: Validates stock and pricing before adding to cart.

## Installation

1. Upload the `altar-configurator` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure WooCommerce is installed and active.

## Usage

Add the shortcode `[altar_configurator]` to any page where you want the configurator to appear.

## Demo Products

For the configurator to work properly, create products in WooCommerce and map their IDs in the plugin settings (or via filter).

## License

MIT

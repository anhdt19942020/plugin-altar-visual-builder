---
task_slug: altar-configurator-mvp
title: Altar Configurator Plugin Implementation
status: completed
---

# Altar Configurator MVP Plan

## Goals

Create a WordPress plugin that allows 2D drag-and-drop altar configuration using Fabric.js and adds items to WooCommerce cart.

## Phases

### Phase 0: Repo & Skeleton (COMPLETED)

- [x] Init plugin structure.
- [x] Main plugin file with WooCommerce check.
- [x] README and .gitignore.

### Phase 1: Shortcode + Enqueue (COMPLETED)

- [x] Create `includes/class-altar-shortcode.php`.
- [x] Implement shortcode `[altar_configurator]`.
- [x] Enqueue Fabric.js (CDN) + local JS/CSS.
- [x] Use `wp_localize_script` for config (demo products, images).

### Phase 2: Fabric.js Canvas Core (COMPLETED)

- [x] Implement `assets/js/configurator.js`.
- [x] Layout template (2 columns: Sidebar & Canvas).
- [x] Drag-and-drop / Add item logic.
- [x] Basic transformations (scale, rotate).

### Phase 3: AJAX Add to Cart (COMPLETED)

- [x] Create `includes/class-altar-ajax.php`.
- [x] Handle `altar_add_bundle_to_cart`.
- [x] Save preview image to `uploads/`.
- [x] Add multiple products to WC cart with custom meta.

### Phase 4: Cart & Order Integration (COMPLETED)

- [x] Create `includes/class-altar-cart.php`.
- [x] Display preview meta in Cart and Checkout.
- [x] Persist meta to Order items.

### Phase 5: Polishing & Testing (COMPLETED)

- [x] Placeholder images integration (via placehold.co).
- [x] Responsive UI for mobile.
- [x] Hardening and final tests.

## Verification

- Shortcode renders canvas.
- Items can be added and moved.
- "Add to Cart" creates a bundle in WC cart with a preview link.

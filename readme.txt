=== eShopLogistic Shipping Calculator ===
Contributors: Moonshine
Tags: shipping,eshoplogistic,delivery,woocommerce
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.2.22
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integration with eShopLogistic service for WooCommerce. Supports 18+ delivery services with real-time shipping calculations.

== Description ==

**English Description**

eShopLogistic is the official WordPress plugin for integrating your WooCommerce store with the eShopLogistic service ([eshoplogistic.ru](https://eshoplogistic.ru/)).

This plugin offers comprehensive shipping integration with support for multiple Russian and international delivery services:
- Display real-time shipping cost and delivery time calculations in shopping cart and product pages
- Support for 18+ delivery carriers (CDEK, DPD, Post Russia, Dostavista, Boxberry, IML, Delovye Linii, PEC, GTD, Baikal Service, Yandex Delivery and more)
- Pickup point (PVZ) selection with interactive map
- Automatic order export to carrier systems
- Flexible shipping rules and adjustments
- Support for custom shipping methods
- Shipping calculator in product card, shopping cart, and custom pages via widgets
- Order tracking with status updates
- Comprehensive settings management panel
- Full support for WooCommerce Block Checkout (Gutenberg-based checkout pages)
- Four Gutenberg blocks for adding shipping widgets to any page

**Feature Highlights:**

* Real-time shipping rate calculation for multiple carriers
* Single unified pickup point selector across all carriers
* One control panel for all delivery services with advanced customization
* Shipping rate rules based on payment method, delivery type, destination, order amount, weight and more
* Dynamic cost and delivery time adjustments per rule
* Custom delivery methods support
* Full order export and tracking capabilities
* WooCommerce Blocks checkout support — works with both classic shortcode and block-based checkout
* Gutenberg blocks: Shipping Calculator (Checkout), Product Shipping Calculator, Cart Shipping, Checkout Form (Legacy)

**Requirements:**
- WordPress 6.0+
- WooCommerce 4.0+
- PHP 7.4+

**Checkout Mode Support:**
Compatible with both traditional shortcode checkout (`[woocommerce_checkout]`) and WooCommerce Block Checkout.

For detailed documentation and demo: [wp-v2.eshoplogistic.ru](https://wp-v2.eshoplogistic.ru/)

== Installation ==

1. After activating the plugin, go to "WC eShopLogistic" in the WordPress admin menu.
2. Enter your API key from your eShopLogistic account ([my.eshoplogistic.ru](https://my.eshoplogistic.ru/)).
3. Map your WooCommerce payment methods to eShopLogistic payment methods in the plugin settings.

== Frequently Asked Questions ==

= Does this plugin require an eShopLogistic account? =

Yes. The plugin is a front-end/checkout integration for the eShopLogistic shipping service. You need a free account and API key from [my.eshoplogistic.ru](https://my.eshoplogistic.ru/) to calculate rates, show pickup points and export orders.

= Which carriers are supported? =

CDEK, DPD, Post Russia, Dostavista, Boxberry, IML, Delovye Linii, PEC, GTD, Baikal Service, Yandex Delivery and others — the exact list of available carriers depends on what is enabled in your eShopLogistic account.

= Does it work with WooCommerce Blocks (Gutenberg) checkout? =

Yes. Both the classic shortcode checkout (`[woocommerce_checkout]`) and the block-based checkout are supported side by side.

= Do I need a Yandex Maps API key? =

Only if you want to display pickup points on an interactive map — this is optional and configured in the plugin settings.

== Screenshots ==

1. Plugin settings screen
2. Shipping rate selection in cart/checkout
3. Pickup point (PVZ) selection with interactive map
4. Order export/tracking screen

== External Services ==

This plugin connects to the eShopLogistic service to provide its core shipping-calculation functionality. It communicates with the following third-party services:

1. **eShopLogistic API** (`api.eshoplogistic.ru`, `api.esplc.ru`) — used to calculate shipping rates and delivery times, retrieve pickup points, and export/track orders. Requests are sent when a customer views the cart/checkout/product page with shipping calculation enabled, and when an order is placed. See the [eShopLogistic Terms of Service](https://eshoplogistic.ru/) and [Privacy Policy](https://eshoplogistic.ru/).
2. **eShopLogistic widget scripts** (`https://api.esplc.ru/widgets/*.js`) — small JavaScript widgets (cart/product/checkout shipping calculators) are loaded directly from the eShopLogistic domain so that carrier rate logic and pickup-point UI stay in sync with the account's service configuration without requiring a plugin update. This only runs on pages where a shipping widget is displayed.
3. **Yandex Maps API** (`api-maps.yandex.ru`) — loaded only if you enable the interactive pickup-point map and provide a Yandex Maps API key in the plugin settings. See [Yandex Terms of Use](https://yandex.ru/legal/).

== Changelog ==

= 2.2.22 =
* Public release preparation for the WordPress.org plugin directory.

== Upgrade Notice ==

= 2.2.22 =
Initial public release on WordPress.org.

== Credits ==

This plugin bundles the following third-party libraries:

* [Bootstrap](https://getbootstrap.com/) 4.6.0 — MIT License
* [Font Awesome Free](https://fontawesome.com/) 5.15.3 — Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License
=== eShopLogistic Shipping Calculator ===
Contributors: eshoplogistic
Tags: shipping,eshoplogistic,delivery,woocommerce
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.2.25
License: GPLv2 or later
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

1. **eShopLogistic API** (`api.eshoplogistic.ru`, `api.esplc.ru`) — this is the backend of the eShopLogistic shipping service itself (the service this plugin integrates with). The plugin's PHP code (server-side, via `Http/WpHttpClient.php`) sends it: the account API key, the shopping cart/order contents needed to calculate a rate (article, name, quantity, price, weight, dimensions), origin/destination city, chosen payment method, and — when an order is placed — the customer's shipping address and order line items, so the order can be created/exported/tracked in the carrier's system. This happens whenever a customer views the cart/checkout/product page with shipping calculation enabled, and when an order is placed. See the eShopLogistic [Terms of Service (offer agreement)](https://eshoplogistic.ru/dokumenty/dogovor-oferta.html) and [Privacy Policy](https://eshoplogistic.ru/dokumenty/politika-konfidencialnosti.html).
2. **eShopLogistic embeddable widget bundle** (`https://api.esplc.ru/widgets/{cart,modal,block}/app.js`, which in turn loads a versioned, content-hashed JS/CSS bundle from the same `api.esplc.ru` domain) — this is a live, self-updating cart/product/checkout shipping-calculator UI, analogous to an embeddable live-chat widget: the eShopLogistic team ships UI updates to this bundle independently of plugin releases, and the asset filenames change with every such release, so they cannot be bundled statically inside the plugin without going stale. It is only loaded on pages where a shipping widget is displayed (product page, cart, checkout), and once loaded it talks to the same eShopLogistic API above (sending cart contents and the visitor's IP address to determine their city) to render rates and pickup points. Governed by the same [Terms of Service](https://eshoplogistic.ru/dokumenty/dogovor-oferta.html) and [Privacy Policy](https://eshoplogistic.ru/dokumenty/politika-konfidencialnosti.html) linked above. A second, older widget UI (used for the product-tab "static" and "modal" display modes) is bundled locally inside the plugin (`assets/css/widget-*.css`, `assets/js/widget-*.js`) and is not loaded remotely.
3. **Yandex Maps API** (`api-maps.yandex.ru`) — loaded only if you enable the interactive pickup-point map and provide your own Yandex Maps API key in the plugin settings. When enabled, the visitor's browser loads the Yandex Maps JavaScript API to render pickup-point markers on a map. See [Yandex Terms of Use](https://yandex.ru/legal/) and [Yandex Privacy Policy](https://yandex.ru/legal/confidential/).
4. **DaData address suggestions** (`suggestions.dadata.ru`) — the locally-bundled product-tab/modal widget UI (see item 2) uses this service to suggest matching Russian addresses as the customer types their delivery address, so the visitor's browser sends the partial address text they are typing, together with an eShopLogistic-issued API token, directly to `suggestions.dadata.ru`. This only runs while the customer is actively typing in the delivery-address field of that widget. See [DaData Terms of Service](https://dadata.ru/terms/) and [DaData Privacy Policy](https://dadata.ru/privacy/).
5. **Google Fonts** (`fonts.googleapis.com`) — the same widget UI loads the "Roboto" web font (SIL Open Font License) from Google Fonts for its own styling. See [Google Fonts FAQ](https://developers.google.com/fonts/faq) and [Google Privacy Policy](https://policies.google.com/privacy).

== Changelog ==

= 2.2.25 =
* Bundled the product-tab widget's CSS/JS locally instead of loading it from a remote domain at runtime.
* Replaced direct cURL calls with the WordPress HTTP API (`wp_remote_post()`) in the widget-data proxy and the SSL-retry fallback.
* Added per-IP rate limiting to the order-creation REST endpoint as an additional layer of protection alongside the optional widget secret.
* Sanitized the widget-data REST endpoint's POST payload (all fields except the raw `offers` JSON, which is sanitized downstream).
* Corrected the External Services disclosure to accurately describe the live, self-updating cart/checkout widget bundle.
* Renamed an unused, non-prefixed legacy AJAX action; removed a dead reference to a third-party plugin's global variable.

= 2.2.24 =
* Fixed invalid Author URI.
* Updated bundled Bootstrap to 4.6.2 (latest 4.x release).
* Expanded the External Services disclosure with specific data-sent details and direct links to the eShopLogistic Terms of Service and Privacy Policy.
* Restricted the public widget-data REST endpoint to the `widget/*` API method namespace so it can no longer be used to reach account/order-management API methods.
* Re-enabled the widget secret-key check on the order-creation REST endpoint (enforced only when a secret is configured in settings).
* Removed the raw API key from shipping-rate transient cache key names.
* Added a capability check to the admin order-export panel to prevent unauthorized order data disclosure.
* Sanitized the widget-data REST endpoint's POST payload before use.
* Escaped remaining unescaped shortcode output attributes.
* Renamed internal PHP callback function names to use the plugin's `wc_esl_` prefix consistently.

= 2.2.23 =
* Shortened plugin display name to comply with WordPress.org directory guidelines (keyword stuffing removal).
* Fixed WooCommerce-dependency notice to use admin_notices and proper translation strings instead of a direct echo.

= 2.2.22 =
* Public release preparation for the WordPress.org plugin directory.

== Upgrade Notice ==

= 2.2.25 =
Further security hardening and guideline compliance fixes required for WordPress.org re-review.

= 2.2.24 =
Security hardening and guideline compliance fixes required for WordPress.org re-review.

= 2.2.23 =
Guideline compliance fixes required for WordPress.org re-review.

== Credits ==

This plugin bundles the following third-party libraries:

* [Bootstrap](https://getbootstrap.com/) 4.6.0 — MIT License
* [Font Awesome Free](https://fontawesome.com/) 5.15.3 — Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License
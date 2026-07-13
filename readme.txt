=== Contract Withdrawal Free for WooCommerce ===
Contributors: foxly
Tags: woocommerce, withdrawal, returns, romania, consumer law
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.0
Requires Plugins: woocommerce
WC requires at least: 8.0
WC tested up to: 10.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A free online contract-withdrawal form with evidence records and email acknowledgements for Romanian WooCommerce stores.

== Description ==

Contract Withdrawal Free provides a dedicated online function through which a consumer can submit an unambiguous contract-withdrawal declaration. Guests can identify a contract manually, while signed-in customers can securely select one of their own WooCommerce orders and its products.

The plugin records a unique reference, submission time, declaration, items, configured merchant information and the exact acknowledgement content. It attempts to send the complete acknowledgement to the customer and a notification to the merchant immediately.

Included placement options:

* online form shortcode, Gutenberg block and classic widget;
* withdrawal-information shortcode, block and widget;
* permanent-link shortcode, block and widget;
* dedicated form and information pages;
* optional permanent footer link.

The plugin does not determine legal eligibility, approve a return, cancel an order, alter stock, issue a refund or guarantee legal compliance. Each merchant must configure and review the displayed information and operating procedure.

This free plugin has no trial period and contains no disabled Premium code. A separate commercial add-on may provide advanced operational features.

== Installation ==

1. Install and activate WooCommerce.
2. Install and activate Contract Withdrawal Free for WooCommerce.
3. Open WooCommerce > Withdrawal settings.
4. Complete the merchant identity, business address, contact details and return information.
5. Select the form, withdrawal-information and privacy pages.
6. Test a declaration and email delivery.
7. Enable the public function after the configuration has been reviewed.

== Frequently Asked Questions ==

= Does the plugin approve returns or refunds? =

No. It records and acknowledges a declaration. The merchant decides and performs all downstream actions.

= Can guests submit? =

Yes. Guests identify the contract manually. Order contents are never exposed to guests.

= Can signed-in customers select products? =

Yes. The server loads only that customer’s own orders and validates selected quantities against the canonical WooCommerce order items.

= Does the plugin contact an external service? =

No. The free edition contains no telemetry, external updater, licence server or remote service integration. Email is sent through the WordPress mail configuration of the site.

= What happens when email delivery fails? =

The declaration remains stored and the customer can immediately download its evidence. Automatic retries are a separate advanced feature and are not included in this free edition.

= What happens on uninstall? =

Withdrawal evidence, settings and generated pages are retained. Only transient rate-limit state is removed.

== Privacy ==

The plugin stores the name, email address, contract reference, selected products, note, declaration, submission time and evidence snapshots needed for the merchant’s withdrawal workflow. It does not store raw visitor IP addresses. Merchants remain responsible for their privacy notice, retention policy and lawful processing.

== Changelog ==

= 1.1.0 =

* Added stable integration actions for add-ons after a declaration is stored and after initial email processing.
* Added compatibility validation for PHP 8.4 and PHP 8.5.

= 1.0.0 =

* Initial free release.
* Guest and account-bound full/partial declaration flows.
* Immutable evidence, immediate email acknowledgement and protected downloads.
* Basic administration, shortcodes, Gutenberg blocks and classic widgets.

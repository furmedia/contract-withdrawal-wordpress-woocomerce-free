# Furmedia Romanian Withdrawal Law for WooCommerce

Free, GPL-licensed WordPress plugin that gives Romanian WooCommerce stores a dedicated online contract-withdrawal function. It records guest and signed-in declarations, creates an evidence reference, sends an acknowledgement and notifies the merchant.

The plugin assists with a withdrawal workflow. It does not determine eligibility, approve a return, cancel an order, restock products, issue a refund or guarantee legal compliance.

## Free features

- guest and signed-in submissions;
- secure selection of a signed-in customer’s own WooCommerce orders;
- full and partial declarations with bounded order quantities;
- manual contract identification without exposing order data;
- immutable declaration, configuration and email snapshots;
- one-time idempotency, nonce, honeypot and privacy-preserving rate limits;
- immediate customer acknowledgement and one merchant notification;
- protected evidence download;
- basic WooCommerce administration list and detail view;
- Romanian translation, shortcodes, three Gutenberg blocks and three classic widgets;
- permanent footer link and dedicated form/information pages.

## Placement

Shortcodes:

```text
[furmrowi_form]
[furmrowi_legal_notice full="yes"]
[furmrowi_link]
```

Gutenberg blocks:

- Furmedia Romanian Withdrawal Law: Form
- Furmedia Romanian Withdrawal Law: Information
- Furmedia Romanian Withdrawal Law: Link

## Free and Pro boundary

This repository contains only the free implementation. It contains no disabled Premium code and no trial period. The separate commercial add-on is planned to provide customer history, operator workflows, status notifications, retry queues, cron/API processing, CSV exports, RTF/PDF tools and diagnostics. See [Free versus Pro](docs/FREE_VS_PRO.md).

## Requirements

- WordPress 6.5 or newer;
- PHP 7.4 or newer;
- WooCommerce 8.0 or newer;
- HTTPS and a working WordPress mail transport are strongly recommended.

## Development

```powershell
php tests/validate.php
powershell -ExecutionPolicy Bypass -File tests/package-release.ps1
```

## Data retention

Uninstall removes only transient rate-limit state. Withdrawal evidence, settings and generated pages are retained to avoid accidental destruction of merchant records.

## License

GPL-2.0-or-later. See [LICENSE.txt](LICENSE.txt).

# Tests

`validate.php` checks syntax, security invariants, the Free/Pro boundary, translation completeness and JavaScript syntax. `package-release.ps1` creates the WordPress.org-oriented installable ZIP and verifies its manifest.

    php tests/validate.php
    powershell -ExecutionPolicy Bypass -File tests/package-release.ps1

Runtime release testing additionally requires a real WordPress/WooCommerce site, guest and signed-in customers, orders with products/options and a working mail transport.

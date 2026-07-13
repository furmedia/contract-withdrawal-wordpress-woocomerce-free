# WordPress.org submission checklist

- Reserve/approve the final plugin slug before SVN deployment.
- Confirm the display name does not imply guaranteed legal compliance.
- Run `php tests/validate.php` and build the release ZIP.
- Run the official WordPress Plugin Check on the release.
- Test activation with WooCommerce active and inactive.
- Test guest, account, full, partial, evidence and failed-mail paths.
- Review `readme.txt`, screenshots, banner and icon assets.
- Confirm all bundled files are GPL-compatible and human readable.
- Confirm the free package has no external updater, telemetry or disabled Premium code.
- Upload the stable release to WordPress.org SVN only after plugin approval.

WordPress.org updates will be delivered from its SVN repository. GitHub remains the public development repository.

<?php
/** Standalone deterministic release validation. Run: php tests/validate.php */

$root   = dirname( __DIR__ );
$checks = 0;

function furmrowi_test( $condition, $message ) {
	global $checks;
	++$checks;
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function furmrowi_read( $root, $path ) {
	$file = $root . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $path );
	furmrowi_test( is_file( $file ), "Required file missing: {$path}" );
	$content = file_get_contents( $file );
	furmrowi_test( false !== $content, "Cannot read: {$path}" );
	return $content;
}

$required = array(
	'furmedia-romanian-withdrawal-law-for-woocommerce.php',
	'includes/class-furmrowi-plugin.php',
	'includes/class-furmrowi-installer.php',
	'includes/class-furmrowi-settings.php',
	'includes/class-furmrowi-repository.php',
	'includes/class-furmrowi-security.php',
	'includes/class-furmrowi-mailer.php',
	'includes/class-furmrowi-frontend.php',
	'includes/class-furmrowi-admin.php',
	'includes/class-furmrowi-widgets.php',
	'templates/form.php',
	'templates/success.php',
	'templates/legal.php',
	'languages/furmedia-romanian-withdrawal-law-for-woocommerce-ro_RO.l10n.php',
	'languages/furmedia-romanian-withdrawal-law-for-woocommerce.pot',
	'languages/furmedia-romanian-withdrawal-law-for-woocommerce-ro_RO.po',
	'languages/furmedia-romanian-withdrawal-law-for-woocommerce-ro_RO.mo',
	'docs/FREE_VS_PRO.md',
	'docs/WORDPRESS_ORG_SUBMISSION.md',
	'uninstall.php',
	'readme.txt',
);
foreach ( $required as $path ) {
	furmrowi_read( $root, $path );
}
foreach ( array( 'templates/history.php', 'templates/model.php', 'assets/js/print.js', 'assets/css/print.css' ) as $forbidden_file ) {
	furmrowi_test( ! is_file( $root . '/' . $forbidden_file ), "Premium-only file present: {$forbidden_file}" );
}

$main = furmrowi_read( $root, 'furmedia-romanian-withdrawal-law-for-woocommerce.php' );
furmrowi_test( false !== strpos( $main, 'Version: 1.1.2' ), 'Plugin version header mismatch.' );
furmrowi_test( false !== strpos( $main, 'Requires Plugins: woocommerce' ), 'WooCommerce dependency header missing.' );
furmrowi_test( false !== strpos( $main, 'custom_order_tables' ), 'HPOS compatibility declaration missing.' );
furmrowi_test( false !== strpos( $main, 'Text Domain: furmedia-romanian-withdrawal-law-for-woocommerce' ), 'Text domain header mismatch.' );
furmrowi_test( false === strpos( $main, 'load_plugin_textdomain' ), 'WordPress.org translations must use just-in-time loading.' );

$installer = furmrowi_read( $root, 'includes/class-furmrowi-installer.php' );
foreach ( array( 'furmrowi_withdrawals', 'furmrowi_rate_limits' ) as $table ) {
	furmrowi_test( false !== strpos( $installer, $table ), "Schema table missing: {$table}" );
}
foreach ( array( 'furmrowi_history', 'furmrowi_runtime', 'email_next_retry_utc' ) as $premium_schema ) {
	furmrowi_test( false === strpos( $installer, $premium_schema ), "Premium-only schema present: {$premium_schema}" );
}
furmrowi_test( false !== strpos( $installer, 'UNIQUE KEY idempotency_hash' ), 'Idempotency uniqueness is missing.' );
furmrowi_test( false !== strpos( $installer, 'UNIQUE KEY reference' ), 'Reference uniqueness is missing.' );

$repository = furmrowi_read( $root, 'includes/class-furmrowi-repository.php' );
furmrowi_test( false !== strpos( $repository, 'START TRANSACTION' ) && false !== strpos( $repository, 'ROLLBACK' ), 'Transactional evidence storage missing.' );
furmrowi_test( false !== strpos( $repository, 'LAST_INSERT_ID' ), 'Atomic rate-limit primitive missing.' );
furmrowi_test( false !== strpos( $repository, 'confirmation_text' ) && false !== strpos( $repository, 'settings_snapshot' ), 'Evidence snapshots missing.' );
furmrowi_test( ! preg_match( '/SELECT\s+(?:[A-Za-z0-9_]+\.)?\*/i', $repository ), 'Database reads must specify column names.' );
foreach ( array( 'claim_due_email', 'schedule_email_retry', 'export_rows', 'update_status', 'histories' ) as $premium_method ) {
	furmrowi_test( false === strpos( $repository, $premium_method ), "Premium repository method present: {$premium_method}" );
}

$security = furmrowi_read( $root, 'includes/class-furmrowi-security.php' );
furmrowi_test( false !== strpos( $security, 'hash_equals' ), 'Constant-time token comparison missing.' );
furmrowi_test( false !== strpos( $security, "hash_hmac( 'sha256'" ), 'Keyed source bucket missing.' );
furmrowi_test( false !== strpos( $security, 'REMOTE_ADDR' ), 'Server source input missing.' );
furmrowi_test( false !== strpos( $security, "wp_unslash( (string) \$_SERVER['REMOTE_ADDR'] )" ), 'Server source input must be unslashed.' );
furmrowi_test( false === strpos( $installer, 'remote_address' ) && false === strpos( $installer, 'ip_address' ), 'Raw source address must not be stored.' );

$frontend = furmrowi_read( $root, 'includes/class-furmrowi-frontend.php' );
furmrowi_test( false !== strpos( $frontend, "do_action( 'furmrowi_withdrawal_recorded'" ), 'Recorded integration action is missing.' );
furmrowi_test( false !== strpos( $frontend, "do_action( 'furmrowi_withdrawal_processed'" ), 'Processed integration action is missing.' );
foreach ( array( 'furmrowi_form', 'furmrowi_legal_notice', 'furmrowi_link' ) as $shortcode ) {
	furmrowi_test( false !== strpos( $frontend, "'{$shortcode}'" ), "Shortcode missing: {$shortcode}" );
}
preg_match_all( "/add_shortcode\(\s*'([^']+)'/", $frontend, $registered_shortcodes );
foreach ( $registered_shortcodes[1] as $registered_shortcode ) {
	furmrowi_test( 0 === strpos( $registered_shortcode, 'furmrowi_' ), "Shortcode is not uniquely prefixed: {$registered_shortcode}" );
}
foreach ( array( 'furmedia/romanian-withdrawal-law-form', 'furmedia/romanian-withdrawal-law-legal', 'furmedia/romanian-withdrawal-law-link' ) as $block ) {
	furmrowi_test( false !== strpos( $frontend, "'{$block}'" ), "Block missing: {$block}" );
}
foreach ( array( 'shortcode_history', 'shortcode_model', 'account_endpoint', 'output_model_document' ) as $premium_frontend ) {
	furmrowi_test( false === strpos( $frontend, $premium_frontend ), "Premium frontend method present: {$premium_frontend}" );
}
furmrowi_test( false !== strpos( $frontend, 'get_customer_id() === get_current_user_id()' ), 'Order ownership binding missing.' );
furmrowi_test( false !== strpos( $frontend, 'can_access_evidence' ), 'Evidence authorization missing.' );
$nonce_position = strpos( $frontend, "wp_verify_nonce( \$nonce, 'furmrowi_submit' )" );
$form_position  = strpos( $frontend, '$this->form = $this->read_form();' );
furmrowi_test( false !== $nonce_position && false !== $form_position && $nonce_position < $form_position, 'Form values must be parsed only after nonce verification.' );

$frontend_js = furmrowi_read( $root, 'assets/js/frontend.js' );
furmrowi_test( false !== strpos( $frontend_js, "actionField.name = 'furmrowi_action'" ) && false !== strpos( $frontend_js, "actionField.value = 'submit'" ), 'Submit action must survive button disabling.' );

$mailer = furmrowi_read( $root, 'includes/class-furmrowi-mailer.php' );
furmrowi_test( false !== strpos( $mailer, 'deliver_initial' ) && false !== strpos( $mailer, 'notify_admin' ), 'Immediate email delivery is incomplete.' );
furmrowi_test( substr_count( $mailer, 'translators: %s: withdrawal reference.' ) >= 3, 'Mailer placeholder translator comments are incomplete.' );
furmrowi_test( false === strpos( $mailer, 'error_log(' ) && false !== strpos( $mailer, 'furmrowi_mail_delivery_error' ), 'Mail failures must use the integration hook instead of direct debug logging.' );
foreach ( array( 'run_retry_queue', 'register_rest_route', 'resend_confirmation', 'send_status_update' ) as $premium_mailer ) {
	furmrowi_test( false === strpos( $mailer, $premium_mailer ), "Premium mail method present: {$premium_mailer}" );
}

$widgets = furmrowi_read( $root, 'includes/class-furmrowi-widgets.php' );
foreach ( array( 'Form_Widget', 'Link_Widget', 'Legal_Widget' ) as $widget ) {
	furmrowi_test( false !== strpos( $widgets, $widget ), "Widget missing: {$widget}" );
}
furmrowi_test( false === strpos( $widgets, 'History_Widget' ), 'Premium history widget present.' );

$admin = furmrowi_read( $root, 'includes/class-furmrowi-admin.php' );
furmrowi_test( false !== strpos( $admin, 'manage_woocommerce' ), 'Administrator capability gate missing.' );
furmrowi_test( false !== strpos( $admin, 'check_admin_referer' ), 'Administrator CSRF gate missing.' );
furmrowi_test( false !== strpos( $admin, "'echo'              => 0" ) && false !== strpos( $admin, 'echo wp_kses(' ), 'Page dropdown output must be explicitly escaped.' );
foreach ( array( 'export_csv', 'update_status', 'resend' ) as $premium_admin ) {
	furmrowi_test( false === strpos( $admin, $premium_admin ), "Premium administration method present: {$premium_admin}" );
}

$form_template  = furmrowi_read( $root, 'templates/form.php' );
$legal_template = furmrowi_read( $root, 'templates/legal.php' );
furmrowi_test( false !== strpos( $form_template, 'translators: %s: WooCommerce order number.' ) && false !== strpos( $form_template, 'translators: %d: quantity originally ordered.' ), 'Form placeholder translator comments are incomplete.' );
furmrowi_test( 2 === substr_count( $legal_template, 'translators: %d: statutory withdrawal period in days.' ), 'Legal placeholder translator comments are incomplete.' );

$uninstall = furmrowi_read( $root, 'uninstall.php' );
furmrowi_test( false !== strpos( $uninstall, 'furmrowi_rate_limits' ), 'Transient uninstall cleanup missing.' );
furmrowi_test( false === strpos( $uninstall, 'furmrowi_withdrawals' ), 'Uninstall must preserve evidence.' );
furmrowi_test( false !== strpos( $uninstall, "prepare( 'DROP TABLE IF EXISTS %i'" ), 'Transient table identifier must be prepared safely.' );

$iterator  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
$php_files = array();
$all_php   = '';
foreach ( $iterator as $file ) {
	$path = $file->getPathname();
	if ( 'php' !== strtolower( $file->getExtension() ) || false !== strpos( $path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR ) || false !== strpos( $path, DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR ) ) {
		continue;
	}
	$php_files[] = $path;
	$bytes       = file_get_contents( $path );
	if ( false === strpos( $path, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR ) ) {
		$all_php .= $bytes;
	}
	furmrowi_test( substr( $bytes, 0, 3 ) !== "\xEF\xBB\xBF", 'UTF-8 BOM found: ' . $path );
	$output = array();
	$status = 0;
	exec( escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $path ), $output, $status );
	furmrowi_test( 0 === $status, 'PHP lint failed: ' . $path );
}
furmrowi_test( count( $php_files ) >= 16, 'Unexpectedly small PHP release surface.' );
furmrowi_test( false === strpos( $all_php, "'romanian-withdrawal-law-for-woocommerce'" ), 'Old text domain remains in PHP.' );
$legacy_prefix = 'cw' . 'fw';
furmrowi_test( false === stripos( $all_php, $legacy_prefix ), 'Legacy generic identifier remains in PHP.' );
furmrowi_test( false === strpos( $all_php, 'wp_remote_' ), 'Free edition must not phone home.' );

$translation_file = $root . '/languages/furmedia-romanian-withdrawal-law-for-woocommerce-ro_RO.l10n.php';
$translation      = include $translation_file;
furmrowi_test( isset( $translation['messages'] ) && is_array( $translation['messages'] ), 'Romanian translation data is invalid.' );
$functions   = array( '__', '_e', '_x', '_ex', '_n', '_nx', 'esc_html__', 'esc_html_e', 'esc_attr__', 'esc_attr_e' );
$message_ids = array();
foreach ( $php_files as $php_file ) {
	if ( $php_file === $translation_file || false !== strpos( $php_file, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR ) ) {
		continue;
	}
	$tokens = token_get_all( file_get_contents( $php_file ) );
	$count  = count( $tokens );
	for ( $index = 0; $index < $count; $index++ ) {
		if ( ! is_array( $tokens[ $index ] ) || T_STRING !== $tokens[ $index ][0] || ! in_array( $tokens[ $index ][1], $functions, true ) ) {
			continue;
		}
		$function = $tokens[ $index ][1];
		$values   = array();
		for ( $cursor = $index + 1; $cursor < $count; $cursor++ ) {
			if ( is_array( $tokens[ $cursor ] ) && T_CONSTANT_ENCAPSED_STRING === $tokens[ $cursor ][0] ) {
				$values[] = eval( 'return ' . $tokens[ $cursor ][1] . ';' );
				if ( ! in_array( $function, array( '_n', '_nx' ), true ) || 2 === count( $values ) ) {
					break;
				}
			}
			if ( ')' === $tokens[ $cursor ] ) {
				break;
			}
		}
		if ( $values ) {
			$key                 = in_array( $function, array( '_n', '_nx' ), true ) && count( $values ) > 1 ? $values[0] . "\0" . $values[1] : $values[0];
			$message_ids[ $key ] = true;
		}
	}
}
$missing = array_diff_key( $message_ids, $translation['messages'] );
furmrowi_test( ! $missing, 'Romanian translations missing: ' . implode( ', ', array_keys( $missing ) ) );
$pot = file_get_contents( $root . '/languages/furmedia-romanian-withdrawal-law-for-woocommerce.pot' );
furmrowi_test( false !== strpos( $pot, 'X-Domain: furmedia-romanian-withdrawal-law-for-woocommerce' ), 'POT catalog header is invalid.' );
$mo = file_get_contents( $root . '/languages/furmedia-romanian-withdrawal-law-for-woocommerce-ro_RO.mo' );
furmrowi_test( substr( $mo, 0, 4 ) === pack( 'V', 0x950412de ), 'Romanian MO catalog is invalid.' );

foreach ( array( 'assets/js/frontend.js', 'assets/js/blocks.js' ) as $javascript ) {
	$output = array();
	$status = 0;
	exec( 'node --check ' . escapeshellarg( $root . '/' . $javascript ), $output, $status );
	furmrowi_test( 0 === $status, "JavaScript syntax failed: {$javascript}" );
}

echo "PASS: {$checks} deterministic checks; " . count( $php_files ) . ' PHP files; ' . count( $message_ids ) . " translated PHP messages.\n";

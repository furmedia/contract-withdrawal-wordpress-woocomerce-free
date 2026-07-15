<?php
/**
 * Uninstall policy: immutable declarations, settings and created pages are retained.
 *
 * Only transient rate-limit state is removed. This prevents an uninstall
 * click from destroying records the merchant may be required to retain.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;
$furmrowi_limits = $wpdb->prefix . 'furmrowi_rate_limits';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall removes only transient rate-limit state.
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $furmrowi_limits ) );

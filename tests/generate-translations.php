<?php
/**
 * Generate the marketplace POT, Romanian PO and Romanian MO catalogs.
 *
 * Run: php tests/generate-translations.php
 */

$root             = dirname( __DIR__ );
$translation_file = $root . '/languages/contract-withdrawal-free-for-woocommerce-ro_RO.l10n.php';
$translation      = include $translation_file;
$romanian         = isset( $translation['messages'] ) && is_array( $translation['messages'] ) ? $translation['messages'] : array();
$functions        = array( '__', '_e', '_n', 'esc_html__', 'esc_html_e', 'esc_attr__', 'esc_attr_e' );
$entries          = array();

$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
foreach ( $iterator as $file ) {
	$path = $file->getPathname();
	if ( 'php' !== strtolower( $file->getExtension() ) || false !== strpos( $path, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR ) || false !== strpos( $path, DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR ) || false !== strpos( $path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR ) ) {
		continue;
	}
	$relative = str_replace( '\\', '/', substr( $path, strlen( $root ) + 1 ) );
	$tokens   = token_get_all( file_get_contents( $path ) );
	$count    = count( $tokens );
	for ( $index = 0; $index < $count; $index++ ) {
		if ( ! is_array( $tokens[ $index ] ) || T_STRING !== $tokens[ $index ][0] || ! in_array( $tokens[ $index ][1], $functions, true ) ) {
			continue;
		}
		$function = $tokens[ $index ][1];
		$values   = array();
		for ( $cursor = $index + 1; $cursor < $count; $cursor++ ) {
			if ( is_array( $tokens[ $cursor ] ) && T_CONSTANT_ENCAPSED_STRING === $tokens[ $cursor ][0] ) {
				$values[] = eval( 'return ' . $tokens[ $cursor ][1] . ';' );
				if ( '_n' !== $function || 2 === count( $values ) ) {
					break;
				}
			}
			if ( ')' === $tokens[ $cursor ] ) {
				break;
			}
		}
		if ( ! $values ) {
			continue;
		}
		$key = '_n' === $function && isset( $values[1] ) ? $values[0] . "\0" . $values[1] : $values[0];
		if ( ! isset( $entries[ $key ] ) ) {
			$entries[ $key ] = array();
		}
		$entries[ $key ][] = $relative . ':' . $tokens[ $index ][2];
	}
}

ksort( $entries, SORT_STRING );
$missing = array_diff_key( $entries, $romanian );
if ( $missing ) {
	fwrite( STDERR, 'Missing Romanian translations: ' . implode( ', ', array_keys( $missing ) ) . "\n" );
	exit( 1 );
}

function cwfw_po_quote( $value ) {
	return '"' . str_replace( array( '\\', '"', "\t", "\r", "\n" ), array( '\\\\', '\\"', '\\t', '\\r', '\\n' ), (string) $value ) . '"';
}

function cwfw_catalog_header( $language = '' ) {
	$lines = array(
		'Project-Id-Version: Contract Withdrawal Free for WooCommerce 1.1.0',
		'Report-Msgid-Bugs-To: support@foxly.ro',
		'POT-Creation-Date: 2026-07-13 00:00+0300',
		'PO-Revision-Date: 2026-07-13 00:00+0300',
		'Last-Translator: Foxly Software',
		'Language-Team: Foxly Software',
	);
	if ( $language ) {
		$lines[] = 'Language: ' . $language;
		$lines[] = 'Plural-Forms: nplurals=3; plural=(n==1 ? 0 : (n==0 || (n%100>0 && n%100<20)) ? 1 : 2);';
	}
	$lines[] = 'MIME-Version: 1.0';
	$lines[] = 'Content-Type: text/plain; charset=UTF-8';
	$lines[] = 'Content-Transfer-Encoding: 8bit';
	$lines[] = 'X-Domain: contract-withdrawal-free-for-woocommerce';
	return implode( "\n", $lines ) . "\n";
}

function cwfw_catalog_text( array $entries, array $translations = array(), $language = '' ) {
	$output  = '# Copyright (C) 2026 Foxly Software' . "\n";
	$output .= '# This file is distributed under the same license as the plugin.' . "\n";
	$output .= "msgid \"\"\nmsgstr \"\"\n";
	foreach ( explode( "\n", cwfw_catalog_header( $language ) ) as $line ) {
		if ( '' !== $line ) {
			$output .= cwfw_po_quote( $line . "\n" ) . "\n";
		}
	}
	foreach ( $entries as $key => $references ) {
		$output .= "\n#: " . implode( ' ', array_unique( $references ) ) . "\n";
		if ( false !== strpos( $key, "\0" ) ) {
			list( $singular, $plural ) = explode( "\0", $key, 2 );
			$output .= 'msgid ' . cwfw_po_quote( $singular ) . "\n";
			$output .= 'msgid_plural ' . cwfw_po_quote( $plural ) . "\n";
			$values = $language ? explode( "\0", (string) $translations[ $key ] ) : array( '', '' );
			$forms  = $language ? 3 : 2;
			for ( $form = 0; $form < $forms; $form++ ) {
				$output .= 'msgstr[' . $form . '] ' . cwfw_po_quote( isset( $values[ $form ] ) ? $values[ $form ] : '' ) . "\n";
			}
		} else {
			$output .= 'msgid ' . cwfw_po_quote( $key ) . "\n";
			$output .= 'msgstr ' . cwfw_po_quote( $language ? $translations[ $key ] : '' ) . "\n";
		}
	}
	return $output;
}

function cwfw_compile_mo( array $entries, array $translations ) {
	$catalog = array( '' => cwfw_catalog_header( 'ro_RO' ) );
	foreach ( $entries as $key => $references ) {
		$catalog[ $key ] = (string) $translations[ $key ];
	}
	ksort( $catalog, SORT_STRING );
	$count                    = count( $catalog );
	$original_table_offset    = 28;
	$translation_table_offset = $original_table_offset + ( $count * 8 );
	$original_data_offset     = $translation_table_offset + ( $count * 8 );
	$original_table           = '';
	$original_data            = '';
	foreach ( array_keys( $catalog ) as $original ) {
		$original_table .= pack( 'V2', strlen( $original ), $original_data_offset + strlen( $original_data ) );
		$original_data  .= $original . "\0";
	}
	$translation_data_offset = $original_data_offset + strlen( $original_data );
	$translation_table       = '';
	$translation_data        = '';
	foreach ( $catalog as $translated ) {
		$translation_table .= pack( 'V2', strlen( $translated ), $translation_data_offset + strlen( $translation_data ) );
		$translation_data  .= $translated . "\0";
	}
	$header = pack( 'V7', 0x950412de, 0, $count, $original_table_offset, $translation_table_offset, 0, 0 );
	return $header . $original_table . $translation_table . $original_data . $translation_data;
}

$pot = cwfw_catalog_text( $entries );
$po  = cwfw_catalog_text( $entries, $romanian, 'ro_RO' );
$mo  = cwfw_compile_mo( $entries, $romanian );

file_put_contents( $root . '/languages/contract-withdrawal-free-for-woocommerce.pot', $pot );
file_put_contents( $root . '/languages/contract-withdrawal-free-for-woocommerce-ro_RO.po', $po );
file_put_contents( $root . '/languages/contract-withdrawal-free-for-woocommerce-ro_RO.mo', $mo );

echo 'Generated POT, PO and MO catalogs for ' . count( $entries ) . " messages.\n";

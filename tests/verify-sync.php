<?php
/**
 * Manual verification for ACF Sync Bar.
 *
 * Usage (against a dev site with ACF + at least one local-JSON field group):
 *   wp eval-file tests/verify-sync.php <field_group_key>
 *
 * Exits non-zero and prints FAIL if the synced group ends up with zero DB fields.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
  fwrite( STDERR, "Run via: wp eval-file tests/verify-sync.php <key>\n" );
  exit( 1 );
}

$key = isset( $args[0] ) ? $args[0] : '';
if ( ! $key ) {
  WP_CLI::error( 'Pass a field group key: wp eval-file tests/verify-sync.php group_abc123' );
}

if ( ! function_exists( 'acf_get_local_field_group' ) ) {
  WP_CLI::error( 'ACF is not active.' );
}

// Resolve the field group exactly the way the plugin does after the fix.
$ajax     = new ACF_Sync_Bar_Ajax( new ACF_Sync_Bar_Detector() );
$resolver = new ReflectionMethod( 'ACF_Sync_Bar_Ajax', 'get_local_with_fields' );
$resolver->setAccessible( true );
$local = $resolver->invoke( $ajax, $key );

if ( ! is_array( $local ) ) {
  WP_CLI::error( "Could not resolve local field group for key: {$key}" );
}

$field_count = isset( $local['fields'] ) && is_array( $local['fields'] ) ? count( $local['fields'] ) : 0;
WP_CLI::log( "Resolved local field group '{$key}' with {$field_count} field(s)." );

if ( $field_count === 0 ) {
  WP_CLI::error( "FAIL: resolved field group has 0 fields — importing this would wipe the DB group." );
}

// Perform the import the same way the handler does.
$existing    = acf_get_field_group_post( $key );
$local['ID'] = $existing ? (int) ( is_object( $existing ) ? $existing->ID : $existing ) : 0;

$prev = acf_get_setting( 'json' );
acf_update_setting( 'json', false );
$result = acf_import_field_group( $local );
acf_update_setting( 'json', $prev );

if ( ! is_array( $result ) || empty( $result['ID'] ) ) {
  WP_CLI::error( 'FAIL: acf_import_field_group did not return a valid group.' );
}

$db_fields = acf_get_fields( $result );
$db_count  = is_array( $db_fields ) ? count( $db_fields ) : 0;
WP_CLI::log( "After import, DB group has {$db_count} field(s)." );

if ( $db_count === 0 ) {
  WP_CLI::error( 'FAIL: DB field group has 0 fields after sync — the disappearing-fields bug is present.' );
}

WP_CLI::success( "PASS: field group '{$key}' kept {$db_count} field(s) through sync." );

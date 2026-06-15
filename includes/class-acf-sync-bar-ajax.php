<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class ACF_Sync_Bar_Ajax {

  private $detector;

  public function __construct( ACF_Sync_Bar_Detector $detector ) {
    $this->detector = $detector;
  }

  public function handle() {
    check_ajax_referer( 'acf_sync_bar', 'nonce' );

    if ( ! function_exists( 'acf_get_setting' ) ) {
      wp_send_json_error( [ 'message' => 'ACF not available.' ], 500 );
    }

    $capability = acf_get_setting( 'capability' );
    if ( ! $capability ) {
      $capability = 'manage_options';
    }

    if ( ! current_user_can( $capability ) ) {
      wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
    }

    if ( ! function_exists( 'acf_get_local_field_group' )
      || ! function_exists( 'acf_import_field_group' )
      || ! function_exists( 'acf_get_field_group_post' )
      || ! function_exists( 'acf_update_setting' ) ) {
      wp_send_json_error( [ 'message' => 'Required ACF API missing.' ], 500 );
    }

    $key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
    if ( ! $key ) {
      wp_send_json_error( [ 'message' => 'Missing field group key.' ], 400 );
    }

    $pending = $this->detector->get_pending();
    $pending_keys = array_column( $pending, 'key' );

    if ( '__all__' === $key ) {
      $target_keys = $pending_keys;
    } else {
      if ( ! in_array( $key, $pending_keys, true ) ) {
        wp_send_json_error( [ 'message' => 'Field group is not pending sync.' ], 400 );
      }
      $target_keys = [ $key ];
    }

    $synced = [];
    $failed = [];

    $previous_json_setting = acf_get_setting( 'json' );
    acf_update_setting( 'json', false );

    foreach ( $target_keys as $target_key ) {
      $local = acf_get_local_field_group( $target_key );
      if ( ! is_array( $local ) ) {
        $failed[] = $target_key;
        continue;
      }

      $existing = acf_get_field_group_post( $target_key );
      $local['ID'] = $existing ? (int) ( is_object( $existing ) ? $existing->ID : $existing ) : 0;

      $result = acf_import_field_group( $local );
      if ( is_array( $result ) && ! empty( $result['ID'] ) ) {
        $synced[] = $target_key;
      } else {
        $failed[] = $target_key;
      }
    }

    acf_update_setting( 'json', $previous_json_setting );

    wp_send_json_success( [
      'synced' => $synced,
      'failed' => $failed,
    ] );
  }
}

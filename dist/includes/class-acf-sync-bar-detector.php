<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class ACF_Sync_Bar_Detector {

  private $cache = null;

  /**
   * Return array of field groups whose local JSON differs from the DB.
   *
   * Each entry: [ 'key' => string, 'title' => string, 'status' => 'new'|'json_newer' ].
   * Returns [] if ACF is unavailable or an expected internal function is missing.
   */
  public function get_pending() {
    if ( null !== $this->cache ) {
      return $this->cache;
    }

    $this->cache = [];

    if ( ! function_exists( 'acf_get_field_groups' )
      || ! function_exists( 'acf_get_local_field_group' )
      || ! function_exists( 'acf_is_local_field_group' )
      || ! function_exists( 'acf_get_field_group_post' ) ) {
      return $this->cache;
    }

    $groups = acf_get_field_groups();
    if ( ! is_array( $groups ) ) {
      return $this->cache;
    }

    $seen_keys = [];

    foreach ( $groups as $group ) {
      if ( empty( $group['key'] ) ) {
        continue;
      }

      if ( isset( $seen_keys[ $group['key'] ] ) ) {
        continue;
      }
      $seen_keys[ $group['key'] ] = true;

      if ( ! acf_is_local_field_group( $group['key'] ) ) {
        continue;
      }

      $local = acf_get_local_field_group( $group['key'] );
      if ( ! is_array( $local ) || empty( $local['local'] ) || 'json' !== $local['local'] ) {
        continue;
      }

      $db_post = acf_get_field_group_post( $group['key'] );

      if ( ! $db_post ) {
        $this->cache[] = [
          'key'    => $group['key'],
          'title'  => isset( $local['title'] ) ? $local['title'] : $group['key'],
          'status' => 'new',
        ];
        continue;
      }

      $local_modified = isset( $local['modified'] ) ? (int) $local['modified'] : 0;
      $db_modified    = (int) get_post_modified_time( 'U', true, $db_post );

      if ( $local_modified > $db_modified ) {
        $this->cache[] = [
          'key'    => $group['key'],
          'title'  => isset( $local['title'] ) ? $local['title'] : $group['key'],
          'status' => 'json_newer',
        ];
      }
    }

    return $this->cache;
  }
}

<?php

if (! defined('ABSPATH')) {
  exit;
}

class ACF_Sync_Bar_Admin_Bar {

  const NODE_ID_PARENT = 'acf-sync-bar';
  const NODE_ID_ALL    = 'acf-sync-bar-all';
  const NODE_ID_PREFIX = 'acf-sync-item-';

  private $detector;

  public function __construct(ACF_Sync_Bar_Detector $detector) {
    $this->detector = $detector;
  }

  private function user_can_sync() {
    if (! function_exists('acf_get_setting')) {
      return false;
    }
    $capability = acf_get_setting('capability');
    if (! $capability) {
      $capability = 'manage_options';
    }
    return current_user_can($capability);
  }

  /**
   * Render the admin bar node + dropdown.
   *
   * @param WP_Admin_Bar $wp_admin_bar
   */
  public function render($wp_admin_bar) {
    if (! $this->user_can_sync()) {
      return;
    }

    $pending = $this->detector->get_pending();
    if (empty($pending)) {
      return;
    }

    $count = count($pending);

    $wp_admin_bar->add_node([
      'id'    => self::NODE_ID_PARENT,
      'title' => sprintf('ACF Sync (%d)', $count),
      'href'  => '#',
      'meta'  => [
        'title' => sprintf('%d ACF field group(s) need to be synced', $count),
      ],
    ]);

    if ($count > 1) {
      $wp_admin_bar->add_node([
        'parent' => self::NODE_ID_PARENT,
        'id'     => self::NODE_ID_ALL,
        'title'  => sprintf('<strong>Sync All (%d)</strong>', $count),
        'href'   => '#',
        'meta'   => [
          'class' => 'acf-sync-bar-action acf-sync-bar-all-row',
        ],
      ]);
    }

    foreach ($pending as $group) {
      $label = sprintf(
        '%s <em>(%s)</em>',
        esc_html($group['title']),
        esc_html($group['status'])
      );

      $wp_admin_bar->add_node([
        'parent' => self::NODE_ID_PARENT,
        'id'     => self::NODE_ID_PREFIX . sanitize_key($group['key']),
        'title'  => $label,
        'href'   => '#',
        'meta'   => [
          'class' => 'acf-sync-bar-action acf-sync-bar-item',
        ],
      ]);
    }
  }

  public function enqueue_assets() {
    if (! $this->user_can_sync()) {
      return;
    }
    if (! is_admin_bar_showing()) {
      return;
    }
    $pending = $this->detector->get_pending();
    if (empty($pending)) {
      return;
    }

    wp_enqueue_script(
      'acf-sync-bar',
      ACF_SYNC_BAR_URL . 'assets/sync-bar.js',
      [],
      ACF_SYNC_BAR_VERSION,
      true
    );

    $keys_by_node_id = [];
    foreach ($pending as $group) {
      $node_id = 'wp-admin-bar-' . self::NODE_ID_PREFIX . sanitize_key($group['key']);
      $keys_by_node_id[$node_id] = $group['key'];
    }

    wp_localize_script('acf-sync-bar', 'ACF_SYNC_BAR', [
      'ajaxUrl'  => admin_url('admin-ajax.php'),
      'nonce'    => wp_create_nonce('acf_sync_bar'),
      'allNode'  => 'wp-admin-bar-' . self::NODE_ID_ALL,
      'parent'   => 'wp-admin-bar-' . self::NODE_ID_PARENT,
      'keys'     => $keys_by_node_id,
      'confirm'  => "DEV ONLY\n\nSync field group(s) from JSON to the database?\n\nThis overwrites the database copy with the JSON file.",
      'syncingLabel' => 'Syncing...',
    ]);
  }
}

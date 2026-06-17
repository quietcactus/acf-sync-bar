<?php

/**
 * Plugin Name: ACF Sync Bar
 * Description: Adds an admin bar button that surfaces ACF field groups with pending local-JSON syncs.
 * Version:     1.1.1
 * Author:      Paperstreet Web Design
 * License:     GPL-2.0-or-later
 * Requires PHP: 7.4
 * Requires at least: 6.0
 */

if (! defined('ABSPATH')) {
  exit;
}

define('ACF_SYNC_BAR_DIR', plugin_dir_path(__FILE__));
define('ACF_SYNC_BAR_URL', plugin_dir_url(__FILE__));
define('ACF_SYNC_BAR_VERSION', '1.1.1');

require_once ACF_SYNC_BAR_DIR . 'includes/class-acf-sync-bar-detector.php';
require_once ACF_SYNC_BAR_DIR . 'includes/class-acf-sync-bar-admin-bar.php';
require_once ACF_SYNC_BAR_DIR . 'includes/class-acf-sync-bar-ajax.php';

register_activation_hook(__FILE__, function () {
  if (! function_exists('acf_get_field_groups')) {
    deactivate_plugins(plugin_basename(__FILE__));
    wp_die(
      esc_html__('ACF Sync Bar requires Advanced Custom Fields (free or Pro) to be installed and active.', 'acf-sync-bar'),
      esc_html__('Plugin dependency missing', 'acf-sync-bar'),
      ['back_link' => true]
    );
  }
});

add_action('admin_notices', function () {
  if (! current_user_can('activate_plugins')) {
    return;
  }
  if (function_exists('acf_get_field_groups')) {
    return;
  }
  if (! is_plugin_active(plugin_basename(__FILE__))) {
    return;
  }
  echo '<div class="notice notice-warning"><p><strong>ACF Sync Bar:</strong> Advanced Custom Fields is not active. The plugin will do nothing until ACF (free or Pro) is enabled.</p></div>';
});

add_action('plugins_loaded', function () {
  if (! function_exists('acf_get_field_groups')) {
    return;
  }

  $detector  = new ACF_Sync_Bar_Detector();
  $admin_bar = new ACF_Sync_Bar_Admin_Bar($detector);
  $ajax      = new ACF_Sync_Bar_Ajax($detector);

  add_action('admin_bar_menu', [$admin_bar, 'render'], 100);
  add_action('wp_enqueue_scripts', [$admin_bar, 'enqueue_assets']);
  add_action('admin_enqueue_scripts', [$admin_bar, 'enqueue_assets']);
  add_action('wp_ajax_acf_sync_bar_sync', [$ajax, 'handle']);
});

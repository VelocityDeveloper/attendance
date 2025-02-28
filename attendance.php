<?php
/*
Plugin Name: Absensi GPS Plugin
Plugin URI: https://yourwebsite.com
Description: Plugin absensi berbasis GPS menggunakan Alpine.js & AJAX.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) exit; // Mencegah akses langsung

// Memuat semua file yang diperlukan
require_once plugin_dir_path(__FILE__) . 'includes/db.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-izin.php';
require_once plugin_dir_path(__FILE__) . 'includes/jabatan.php';

require_once plugin_dir_path(__FILE__) . 'includes/shortcode/login.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode/list-izin.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode/register.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode/izin.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode/kordinat.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode/absensi.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode/daftar-absensi.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode/shift-setting.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode/shift-assignment.php';

/**
 * Enqueue scripts dan styles
 */
function absensi_enqueue_scripts()
{
  // Load Alpine.js dengan defer
  wp_enqueue_script(
    'alpinejs',
    'https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js',
    [],
    '3.14.8',
    ['strategy' => 'defer'] // Gunakan defer untuk optimasi
  );

  // wp_enqueue_script(
  //   'absensi-js',
  //   plugins_url('assets/js/absensi.js', __FILE__),
  //   ['alpinejs'],
  //   filemtime(plugin_dir_path(__FILE__) . 'assets/js/absensi.js'), // Cache busting
  //   ['strategy' => 'defer']
  // );

  // Localize script untuk AJAX
  wp_localize_script(
    'alpinejs',
    'absensiAjax',
    [
      'ajaxurl' => admin_url('admin-ajax.php'),
      'nonce'   => wp_create_nonce('absensi_nonce')
    ]
  );
}
add_action('wp_enqueue_scripts', 'absensi_enqueue_scripts');

// Enqueue admin scripts
add_action('admin_enqueue_scripts', 'enqueue_custom_admin_script');
function enqueue_custom_admin_script()
{
  wp_enqueue_script('custom-admin-script', plugin_dir_url(__FILE__) . '/assets/js/absensi.js', array('jquery'), null, true);
  wp_localize_script('custom-admin-script', 'absensiAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
}


add_filter('show_admin_bar', function ($show) {
  if (!current_user_can('administrator')) {
    return false;
  }
  return $show;
});

register_activation_hook(__FILE__, 'create_absensi_table');

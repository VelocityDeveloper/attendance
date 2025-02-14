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
require_once plugin_dir_path(__FILE__) . 'includes/status.php';
// require_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';
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

  // Load custom script dengan defer
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

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
require_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax.php';
require_once plugin_dir_path(__FILE__) . 'includes/status.php';

// Daftarkan file JS
function absensi_enqueue_scripts()
{
  wp_enqueue_script('alpinejs', 'https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js', [], '3.14.8', true);
  wp_enqueue_script('absensi-js', plugin_dir_url(__FILE__) . 'assets/js/absensi.js', [], '1.0', true);
}
add_action('wp_enqueue_scripts', 'absensi_enqueue_scripts');

<?php
if (!defined('ABSPATH')) exit;

function get_absensi_status()
{
  if (!is_user_logged_in()) {
    wp_send_json_error(['status' => 'Anda belum login.']);
  }

  global $wpdb;
  $table_name = $wpdb->prefix . 'absensi';
  $user_id = get_current_user_id();
  $tanggal_sekarang = date('Y-m-d');

  $absensi = $wpdb->get_row($wpdb->prepare("
        SELECT * FROM $table_name WHERE user_id = %d AND DATE(waktu_masuk) = %s
    ", $user_id, $tanggal_sekarang));

  if (!$absensi) {
    wp_send_json_success(['status' => 'Belum ada absensi hari ini.']);
  } elseif (!$absensi->waktu_pulang) {
    wp_send_json_success(['status' => 'Anda sudah check-in, tetapi belum check-out.']);
  } else {
    wp_send_json_success(['status' => 'Anda sudah check-in dan check-out hari ini.']);
  }
}
add_action('wp_ajax_get_absensi_status', 'get_absensi_status');

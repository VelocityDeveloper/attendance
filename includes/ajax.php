<?php
if (!defined('ABSPATH')) exit;

function save_absensi()
{
  if (!is_user_logged_in() || !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'absensi_nonce')) {
    wp_send_json_error(['message' => 'Akses tidak sah!']);
  }

  global $wpdb;
  $table_name = $wpdb->prefix . 'absensi';
  $user_id = get_current_user_id();
  $lat = sanitize_text_field($_POST['lat']);
  $lng = sanitize_text_field($_POST['lng']);
  $type = sanitize_text_field($_POST['type']);

  if ($type == 'masuk') {
    $wpdb->insert($table_name, [
      'user_id' => $user_id,
      'waktu_masuk' => current_time('mysql'),
      'lat_masuk' => $lat,
      'lng_masuk' => $lng
    ]);
    wp_send_json_success(['message' => 'Berhasil check-in!']);
  } elseif ($type == 'pulang') {
    $wpdb->update($table_name, [
      'waktu_pulang' => current_time('mysql'),
      'lat_pulang' => $lat,
      'lng_pulang' => $lng
    ], ['user_id' => $user_id, 'waktu_pulang' => null]);
    wp_send_json_success(['message' => 'Berhasil check-out!']);
  }
}
add_action('wp_ajax_save_absensi', 'save_absensi');

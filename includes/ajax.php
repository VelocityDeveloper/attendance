<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_save_absensi', 'handle_save_absensi');
add_action('wp_ajax_get_absensi_status', 'handle_get_absensi_status');

function handle_save_absensi()
{
  if (!wp_verify_nonce($_POST['_wpnonce'], 'absensi_nonce')) {
    wp_send_json_error(['message' => 'Nonce tidak valid.']);
  }

  global $wpdb;
  $table_name = $wpdb->prefix . 'absensi';
  $user_id    = get_current_user_id();
  $type       = sanitize_text_field($_POST['type']);
  $lat        = floatval($_POST['lat']);
  $lng        = floatval($_POST['lng']);
  $today      = current_time('Y-m-d');

  // Periksa apakah sudah ada absen masuk atau pulang hari ini
  $existing_absensi = $wpdb->get_col(
    $wpdb->prepare(
      "SELECT type FROM $table_name WHERE user_id = %d AND DATE(time) = %s",
      $user_id,
      $today
    )
  );

  if ($type === 'masuk' && in_array('masuk', $existing_absensi)) {
    wp_send_json_error([
      'message' => 'Anda sudah absen masuk hari ini.',
      'sudahAbsen' => ['masuk' => true],
    ]);
  }

  if ($type === 'pulang' && !in_array('masuk', $existing_absensi)) {
    wp_send_json_error([
      'message' => 'Anda belum absen masuk hari ini.',
      'sudahAbsen' => ['masuk' => false],
    ]);
  }

  if ($type === 'pulang' && in_array('pulang', $existing_absensi)) {
    wp_send_json_error([
      'message' => 'Anda sudah absen pulang hari ini.',
      'sudahAbsen' => ['masuk' => false, 'pulang' => true],
    ]);
  }

  // Simpan absen ke database
  $wpdb->insert(
    $table_name,
    [
      'user_id' => $user_id,
      'type'    => $type,
      'lat'     => $lat,
      'lng'     => $lng,
      'time'    => current_time('mysql'),
    ]
  );

  wp_send_json_success([
    'message' => "Absensi $type berhasil.",
    'sudahAbsen' => ['masuk' => true, 'pulang' => false],
  ]);
}


function handle_get_absensi_status()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'absensi';
  $user_id    = get_current_user_id();
  $today      = current_time('Y-m-d');

  $absensi_today = $wpdb->get_col(
    $wpdb->prepare(
      "SELECT type FROM $table_name WHERE user_id = %d AND DATE(time) = %s",
      $user_id,
      $today
    )
  );

  if (in_array('masuk', $absensi_today) && in_array('pulang', $absensi_today)) {
    $status = "Anda sudah absen masuk & pulang hari ini.";
  } elseif (in_array('masuk', $absensi_today)) {
    $status = "Anda sudah absen masuk, silakan absen pulang.";
  } else {
    $status = "Belum ada absensi hari ini.";
  }

  wp_send_json_success([
    'status' => $status,
    'sudahAbsen' => ['masuk' => in_array('masuk', $absensi_today), 'pulang' => in_array('pulang', $absensi_today)],
  ]);
}


function get_users_ajax()
{
  if (!is_user_logged_in()) {
    wp_send_json_error(['message' => 'Anda harus login.']);
  }

  global $wpdb;
  $users = $wpdb->get_results("SELECT ID as id, display_name as name FROM $wpdb->users", ARRAY_A);

  wp_send_json_success(['users' => $users]);
}
add_action('wp_ajax_get_users', 'get_users_ajax');

function get_absensi_list_ajax()
{
  if (!is_user_logged_in() || !isset($_GET['user_id'])) {
    wp_send_json_error(['message' => 'Data tidak valid.']);
  }

  global $wpdb;
  $user_id = intval($_GET['user_id']);
  $table_name = $wpdb->prefix . 'absensi';

  // ambil data dari database dan tampilkan data 30 hari terakhir
  $query = "SELECT * FROM $table_name WHERE user_id = $user_id ORDER BY time DESC LIMIT 30";
  $absensi = $wpdb->get_results($query, ARRAY_A);

  if (empty($absensi)) {
    error_log("Tidak ada data absensi untuk user_id: " . $user_id);
  }

  wp_send_json_success(['user_id' => $user_id, 'query' => $query, 'absensi' => $absensi, 'nama_table' => $table_name]);
}
add_action('wp_ajax_get_absensi_list', 'get_absensi_list_ajax');


add_action('wp_ajax_get_shifts', function () {
  $shifts = get_option('work_shifts', []);
  wp_send_json_success($shifts);
});

add_action('wp_ajax_save_shift', function () {
  $shifts = get_option('work_shifts', []);
  $new_shift = json_decode(stripslashes($_POST['shift']), true);
  $shifts[] = $new_shift;
  update_option('work_shifts', $shifts);
  wp_send_json_success();
});

add_action('wp_ajax_update_shift', function () {
  $shifts = get_option('work_shifts', []);
  $index = intval($_POST['index']);
  $updated_shift = json_decode(stripslashes($_POST['shift']), true);

  if (isset($shifts[$index])) {
    $shifts[$index] = $updated_shift;
    update_option('work_shifts', $shifts);
    wp_send_json_success();
  } else {
    wp_send_json_error();
  }
});

add_action('wp_ajax_delete_shift', function () {
  $shifts = get_option('work_shifts', []);
  $index = intval($_POST['index']);

  if (isset($shifts[$index])) {
    array_splice($shifts, $index, 1);
    update_option('work_shifts', $shifts);
    wp_send_json_success();
  } else {
    wp_send_json_error();
  }
});

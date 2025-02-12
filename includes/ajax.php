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

  // Ambil data absensi 30 hari terakhir
  $query = "SELECT * FROM $table_name WHERE user_id = $user_id ORDER BY time DESC LIMIT 30";
  $absensi = $wpdb->get_results($query, ARRAY_A);

  // Ambil shift yang ditugaskan ke user ini
  $assignments = get_option('shift_assignments', []);
  $shifts = get_option('work_shifts', []);
  $user_shift = null;

  foreach ($assignments as $assignment) {
    if ($assignment['user_id'] == $user_id) {
      $user_shift = $shifts[$assignment['shift']] ?? null;
      break;
    }
  }

  // Proses keterlambatan dan pulang sebelum waktunya
  foreach ($absensi as &$absen) {
    if ($user_shift) {
      $absen_time = strtotime($absen['time']);
      $shift_start = strtotime(date('Y-m-d', $absen_time) . ' ' . $user_shift['start']);
      $shift_end = strtotime(date('Y-m-d', $absen_time) . ' ' . $user_shift['end']);

      if ($absen['type'] == 'masuk') {
        $absen['status'] = ($absen_time > $shift_start) ? 'Terlambat' : 'Masuk Tepat Waktu';
      } elseif ($absen['type'] == 'pulang') {
        $absen['status'] = ($absen_time < $shift_end) ? 'Pulang Sebelum Waktunya' : 'Pulang Tepat Waktu';
      } else {
        $absen['status'] = ($absen_time < $shift_end) ? 'Pulang Sebelum Waktunya' : 'Pulang Tepat Waktu';
      }
      // jika masuk dan setelah jam masuk maka selisih waktu minus dam format - hh:mm
      if ($absen['type'] == 'masuk' && $absen_time > $shift_start) {
        $selisih_waktu = ($absen_time - $shift_start) / 60;
        $absen['selisih_waktu'] = " - " . date('H:i', $selisih_waktu);
      } else if ($absen['type'] == 'pulang' && $absen_time < $shift_end) {
        $selisih_waktu = ($shift_end - $absen_time) / 60;
        $absen['selisih_waktu'] = " - " . date('H:i', $selisih_waktu);
      }
    } else {
      $absen['status'] = 'Shift Tidak Ditemukan';
    }
  }

  wp_send_json_success(['user_id' => $user_id, 'absensi' => $absensi]);
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

add_action('wp_ajax_get_shift_assignments', function () {
  $assignments = get_option('shift_assignments', []);

  foreach ($assignments as &$assignment) {
    $user = get_user_by('ID', $assignment['user_id']);
    $shifts = get_option('work_shifts', []);
    $shift = $shifts[$assignment['shift']] ?? null;

    $assignment['user_name'] = $user ? $user->display_name : 'Unknown';
    $assignment['shift_name'] = $shift['name'] ?? 'Unknown';
    $assignment['shift_start'] = $shift['start'] ?? '00:00';
    $assignment['shift_end'] = $shift['end'] ?? '00:00';
  }

  wp_send_json_success($assignments);
});

add_action('wp_ajax_assign_shift', function () {
  $assignments = get_option('shift_assignments', []);
  $new_assignment = json_decode(stripslashes($_POST['assignment']), true);
  $assignments[] = $new_assignment;
  update_option('shift_assignments', $assignments);
  wp_send_json_success();
});

add_action('wp_ajax_remove_shift_assignment', function () {
  $assignments = get_option('shift_assignments', []);
  $index = intval($_POST['index']);

  if (isset($assignments[$index])) {
    array_splice($assignments, $index, 1);
    update_option('shift_assignments', $assignments);
    wp_send_json_success();
  } else {
    wp_send_json_error();
  }
});

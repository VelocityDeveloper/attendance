<?php
if (!defined('ABSPATH')) exit;

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

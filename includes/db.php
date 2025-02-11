<?php
if (!defined('ABSPATH')) exit;

function absensi_create_table()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'absensi';
  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        waktu_masuk DATETIME NULL,
        lat_masuk DECIMAL(10, 8) NULL,
        lng_masuk DECIMAL(11, 8) NULL,
        waktu_pulang DATETIME NULL,
        lat_pulang DECIMAL(10, 8) NULL,
        lng_pulang DECIMAL(11, 8) NULL
    ) $charset_collate;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
}
register_activation_hook(__FILE__, 'absensi_create_table');

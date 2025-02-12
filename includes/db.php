<?php
if (!defined('ABSPATH')) exit;

function create_absensi_table()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'absensi';
  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        type varchar(50) NOT NULL,
        lat FLOAT(10,6) NOT NULL,
        lng FLOAT(10,6) NOT NULL,
        time DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);

  // Debugging
  if ($wpdb->last_error) {
    error_log('Error creating table: ' . $wpdb->last_error);
  }
}

register_activation_hook(__FILE__, 'create_absensi_table');

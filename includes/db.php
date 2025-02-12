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
        lat float NOT NULL,
        lng float NOT NULL,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_absensi_table');

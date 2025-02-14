<?php
// Hook to register the custom post type
add_action('init', 'register_izin_post_type');

function register_izin_post_type()
{
  $labels = array(
    'name'                  => 'Izin',
    'singular_name'         => 'Izin',
    'menu_name'             => 'Izin',
    'name_admin_bar'        => 'Izin',
    'add_new'               => 'Tambah Baru',
    'add_new_item'          => 'Tambah Izin Baru',
    'new_item'              => 'Izin Baru',
    'edit_item'             => 'Edit Izin',
    'view_item'             => 'Lihat Izin',
    'all_items'             => 'Semua Izin',
    'search_items'          => 'Cari Izin',
    'not_found'             => 'Tidak ada izin ditemukan.',
    'not_found_in_trash'    => 'Tidak ada izin ditemukan di tempat sampah.',
  );

  $args = array(
    'labels'             => $labels,
    'public'             => true,
    'publicly_queryable' => true,
    'show_ui'            => true,
    'show_in_menu'       => true,
    'query_var'          => true,
    'rewrite'            => array('slug' => 'izin'),
    'capability_type'    => 'post',
    'has_archive'        => true,
    'hierarchical'       => false,
    'menu_position'      => 20,
    'supports'           => array('title'),
  );

  register_post_type('izin', $args);
}

// Hook to add custom meta boxes
add_action('add_meta_boxes', 'add_izin_meta_boxes');

function add_izin_meta_boxes()
{
  add_meta_box('izin_meta', 'Detail Izin', 'izin_meta_box_callback', 'izin', 'normal', 'high');
}

function izin_meta_box_callback($post)
{
  // Add a nonce field for security
  wp_nonce_field('izin_meta_box', 'izin_meta_box_nonce');

  // Get existing metadata if it exists
  $jenis_izin = get_post_meta($post->ID, '_jenis_izin', true);
  $deskripsi = get_post_meta($post->ID, '_deskripsi_izin', true);
  $lampiran_id = get_post_meta($post->ID, '_lampiran_id', true);

  // Output the form fields
  echo '<label for="jenis_izin">Jenis Izin:</label>';
  echo '<input type="text" id="jenis_izin" name="jenis_izin" value="' . esc_attr($jenis_izin) . '" class="widefat" />';

  echo '<label for="deskripsi_izin">Deskripsi:</label>';
  echo '<textarea id="deskripsi_izin" name="deskripsi_izin" class="widefat" rows="4">' . esc_textarea($deskripsi) . '</textarea>';

  if ($lampiran_id) {
    echo wp_get_attachment_image($lampiran_id, 'medium') . '<br>';
  }
}

// Save the custom meta data
add_action('save_post', 'save_izin_meta_data');

function save_izin_meta_data($post_id)
{
  // Check the nonce for security
  if (!isset($_POST['izin_meta_box_nonce']) || !wp_verify_nonce($_POST['izin_meta_box_nonce'], 'izin_meta_box')) {
    return;
  }

  // Check if this is an autosave and return if it is
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
  }

  // Check user permissions
  if (!current_user_can('edit_post', $post_id)) {
    return;
  }

  // Save the metadata
  if (isset($_POST['jenis_izin'])) {
    update_post_meta($post_id, '_jenis_izin', sanitize_text_field($_POST['jenis_izin']));
  }

  if (isset($_POST['deskripsi_izin'])) {
    update_post_meta($post_id, '_deskripsi_izin', sanitize_textarea_field($_POST['deskripsi_izin']));
  }
}

// Handle the form submission
add_action('wp_ajax_submit_izin', 'submit_izin_ajax');

function submit_izin_ajax()
{
  if (!is_user_logged_in()) {
    wp_send_json_error(['message' => 'Anda harus login untuk mengajukan izin.']);
  }

  if (!isset($_POST['jenis_izin'], $_POST['deskripsi']) || empty($_FILES['lampiran']['name'])) {
    wp_send_json_error(['message' => 'Data tidak valid.']);
  }

  global $wpdb;
  $user_id = get_current_user_id();
  $jenis_izin = sanitize_text_field($_POST['jenis_izin']);
  $deskripsi = sanitize_textarea_field($_POST['deskripsi']);

  // Handle file upload
  $uploaded_file = $_FILES['lampiran'];
  $upload_overrides = array('test_form' => false);

  // Upload the file
  $movefile = wp_handle_upload($uploaded_file, $upload_overrides);
  if ($movefile && !isset($movefile['error'])) {
    // Create a new post of type 'izin'
    $post_data = array(
      'post_title'   => 'Izin: ' . $jenis_izin,
      'post_content' => $deskripsi,
      'post_status'  => 'pending',
      'post_type'    => 'izin',
      'post_author'  => $user_id,
    );

    // Insert the post into the database
    $post_id = wp_insert_post($post_data);
    if ($post_id) {
      // Attach the file to the post
      $attachment = array(
        'guid' => $movefile['url'],
        'post_mime_type' => $movefile['type'],
        'post_title' => sanitize_file_name($uploaded_file['name']),
        'post_content' => '',
        'post_status' => 'inherit'
      );

      // Insert the attachment
      $attach_id = wp_insert_attachment($attachment, $movefile['file'], $post_id);
      require_once(ABSPATH . 'wp-admin/includes/image.php');
      $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
      wp_update_attachment_metadata($attach_id, $attach_data);

      // Save the attachment ID in post meta
      update_post_meta($post_id, '_lampiran_id', $attach_id);
      update_post_meta($post_id, '_jenis_izin', $jenis_izin);
      update_post_meta($post_id, '_deskripsi_izin', $deskripsi);

      wp_send_json_success(['message' => 'Pengajuan izin berhasil.']);
    } else {
      wp_send_json_error(['message' => 'Gagal membuat pengajuan izin.']);
    }
  } else {
    wp_send_json_error(['message' => 'Gagal mengupload lampiran.']);
  }
}

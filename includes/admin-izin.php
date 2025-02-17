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
    'supports'           => array('title', 'author')
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
  $start_date = get_post_meta($post->ID, '_start_date', true);
  $end_date = get_post_meta($post->ID, '_end_date', true);
  $jumlah_hari = get_post_meta($post->ID, '_jumlah_hari', true);
  $lampiran_id = get_post_meta($post->ID, '_lampiran_id', true);

  // Output the form fields
  echo '<label for="jenis_izin">Jenis Izin:</label>';
  echo '<input type="text" id="jenis_izin" name="jenis_izin" value="' . esc_attr($jenis_izin) . '" class="widefat" />';

  echo '<label for="deskripsi_izin">Deskripsi:</label>';
  echo '<textarea id="deskripsi_izin" name="deskripsi_izin" class="widefat" rows="4">' . esc_textarea($deskripsi) . '</textarea>';

  echo '<label for="start_date">Tanggal Mulai:</label>';
  echo '<input type="date" id="start_date" name="start_date" value="' . esc_attr($start_date) . '" class="widefat" />';

  echo '<label for="end_date">Tanggal Akhir:</label>';
  echo '<input type="date" id="end_date" name="end_date" value="' . esc_attr($end_date) . '" class="widefat" />';

  echo '<label for="jumlah_hari">Jumlah Hari Kerja:</label>';
  echo '<input type="number" id="jumlah_hari" name="jumlah_hari" value="' . esc_attr($jumlah_hari) . '" class="widefat" readonly />';

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

  if (isset($_POST['start_date'])) {
    update_post_meta($post_id, '_start_date', sanitize_text_field($_POST['start_date']));
  }

  if (isset($_POST['end_date'])) {
    update_post_meta($post_id, '_end_date', sanitize_text_field($_POST['end_date']));
  }

  if (isset($_POST['jumlah_hari'])) {
    update_post_meta($post_id, '_jumlah_hari', intval($_POST['jumlah_hari']));
  }
}

// Handle the form submission
add_action('wp_ajax_submit_izin', 'submit_izin_ajax');

function submit_izin_ajax()
{
  if (!is_user_logged_in()) {
    wp_send_json_error(['message' => 'Anda harus login untuk mengajukan izin.']);
  }

  if (!isset($_POST['jenis_izin'], $_POST['start_date'], $_POST['end_date'], $_POST['jumlah_hari'], $_POST['deskripsi']) || empty($_FILES['lampiran']['name'])) {
    wp_send_json_error(['message' => 'Data tidak valid.']);
  }

  global $wpdb;
  $user_id = get_current_user_id();
  $jenis_izin = sanitize_text_field($_POST['jenis_izin']);
  $deskripsi = sanitize_textarea_field($_POST['deskripsi']);
  $start_date = sanitize_text_field($_POST['start_date']);
  $end_date = sanitize_text_field($_POST['end_date']);
  $jumlah_hari = intval($_POST['jumlah_hari']);

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
      'post_status'  => 'publish',
      'post_type'    => 'izin',
      'post_author'  => $user_id,
    );

    // Insert the post into the database
    $post_id = wp_insert_post($post_data);
    if ($post_id) {
      // Save additional data
      update_post_meta($post_id, '_start_date', $start_date);
      update_post_meta($post_id, '_end_date', $end_date);
      update_post_meta($post_id, '_jumlah_hari', $jumlah_hari);
      update_post_meta($post_id, '_jenis_izin', $jenis_izin);
      update_post_meta($post_id, '_deskripsi_izin', $deskripsi);

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

      wp_send_json_success(['message' => 'Pengajuan izin berhasil.']);
    } else {
      wp_send_json_error(['message' => 'Gagal membuat pengajuan izin.']);
    }
  } else {
    wp_send_json_error(['message' => 'Gagal mengupload lampiran.']);
  }
}

// Add custom columns to the Izin post type list
add_filter('manage_izin_posts_columns', 'set_custom_edit_izin_columns');
function set_custom_edit_izin_columns($columns)
{
  $columns['date_start'] = __('Dari');
  $columns['date_end'] = __('Sampai');
  $columns['izin_status'] = __('Status');
  $columns['tindakan'] = __('Tindakan');
  return $columns;
}

// Populate the custom column with data
add_action('manage_izin_posts_custom_column', 'izin_custom_column', 10, 2);
function izin_custom_column($column, $post_id)
{
  if ($column === 'date_start') {
    $date = get_post_meta($post_id, '_start_date', true);
    if ($date) {
      $formatted_date = DateTime::createFromFormat('Y-m-d', $date)->format('d-m-Y');
      echo esc_html($formatted_date);
    } else {
      echo 'Tidak Ditetapkan';
    }
  }
  if ($column === 'date_end') {
    $date = get_post_meta($post_id, '_end_date', true);
    if ($date) {
      $formatted_date = DateTime::createFromFormat('Y-m-d', $date)->format('d-m-Y');
      echo esc_html($formatted_date);
    } else {
      echo 'Tidak Ditetapkan';
    }
  }
  if ($column === 'izin_status') {
    $status = get_post_meta($post_id, '_izin_status', true);
    echo $status ? esc_html($status) : 'Belum Ditentukan';
  }
  if ($column === 'tindakan') {
    echo '<div class="row mt-2">';
    echo '<button class="button button-primary" onclick="updateIzinStatus(' . $post_id . ', \'approved\')">Setujui</button>';
    echo '<button class="button button-primary" style="margin-left: 5px;" onclick="updateIzinStatus(' . $post_id . ', \'rejected\')">Tolak</button>';
    echo '</div>';
  }
}

// Handle the AJAX request to update the status
add_action('wp_ajax_update_izin_status', 'update_izin_status_callback');
function update_izin_status_callback()
{
  if (!current_user_can('edit_posts')) {
    wp_send_json_error(['message' => 'Anda tidak memiliki izin.']);
  }

  if (!isset($_POST['post_id']) || !isset($_POST['status'])) {
    wp_send_json_error(['message' => 'Data tidak valid.']);
  }

  $post_id = intval($_POST['post_id']);
  $status = sanitize_text_field($_POST['status']);

  if ($status === 'approved' || $status === 'rejected') {
    update_post_meta($post_id, '_izin_status', $status);
    wp_send_json_success(['message' => 'Status izin berhasil diperbarui.']);
  } else {
    wp_send_json_error(['message' => 'Status tidak valid.']);
  }
}

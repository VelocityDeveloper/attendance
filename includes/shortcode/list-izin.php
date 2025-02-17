<?php
function list_izin_shortcode()
{
  if (!is_user_logged_in()) {
    return '<div class="container mt-4"><div class="alert alert-warning">Silakan login untuk melihat daftar izin.</div></div>';
  }

  $user_id = get_current_user_id();
  $izin_list = get_user_izin($user_id);

  if (empty($izin_list)) {
    return '<div class="container mt-4"><div class="alert alert-info">Anda belum mengajukan izin pada tahun ini.</div></div>';
  }

  ob_start();
?>
  <div class="container mt-4">
    <h4 class="text-center mb-3">Daftar Izin Anda (Tahun <?= date('Y'); ?>)</h4>
    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>Jenis Izin</th>
          <th>Tanggal Mulai</th>
          <th>Jumlah Hari</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($izin_list as $izin) : ?>
          <tr>
            <td><?php echo esc_html($izin['jenis_izin']); ?></td>
            <td><?php echo esc_html($izin['start_date']); ?></td>
            <td><?php echo esc_html($izin['jumlah_hari']); ?></td>
            <td>
              <span class="badge 
                             <?php echo $izin['status'] === 'approved' ? 'bg-success' : ($izin['status'] === 'rejected' ? 'bg-danger' : 'bg-warning'); ?>">
                <?php echo esc_html($izin['status']); ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php
  return ob_get_clean();
}
add_shortcode('list_izin', 'list_izin_shortcode');


function get_user_izin($user_id)
{
  $current_year = date('Y');
  $args = array(
    'post_type' => 'izin',
    'author' => $user_id,
    'posts_per_page' => -1,
    'date_query' => array(
      array(
        'year' => $current_year,
      ),
    ),
    'meta_query' => array(
      array(
        'key' => '_start_date',
        'value' => $current_year,
        'compare' => 'LIKE',
      ),
    ),
  );

  $posts = get_posts($args);
  $izin_list = array();

  foreach ($posts as $post) {
    $izin_list[] = array(
      'id' => $post->ID,
      'jenis_izin' => get_post_meta($post->ID, '_jenis_izin', true),
      'start_date' => get_post_meta($post->ID, '_start_date', true),
      'end_date' => get_post_meta($post->ID, '_end_date', true),
      'jumlah_hari' => get_post_meta($post->ID, '_jumlah_hari', true),
      'deskripsi' => get_post_meta($post->ID, '_deskripsi', true),
      'lampiran' => get_post_meta($post->ID, '_lampiran', true),
      'status' => get_post_meta($post->ID, '_izin_status', true),
    );
  }

  return $izin_list;
}

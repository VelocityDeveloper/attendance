<?php
if (!defined('ABSPATH')) exit;

function absensi_shortcode()
{
  ob_start();

  if (!is_user_logged_in()) {
    echo '<div class="container mt-4">';
    echo '<div class="alert alert-warning">Silakan login untuk melakukan absensi.</div>';
    echo '<div class="border p-3 p-md-5 rounded my-4" style="max-width:30rem;margin:0 auto">';
    echo attendance_login_form();
    echo '</div>';
    echo '</div>';
    return ob_get_clean();
  }

?>
  <div class="container border p-4 rounded mt-4">

    <div class="text-end">
      <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="btn btn-sm btn-danger mb-2">Logout</a>
    </div>

    <div x-data="absensiHandler()">

      <!-- Status Absensi -->
      <div class="alert alert-info text-center" x-text="status"></div>

      <!-- Loading Spinner -->
      <div class="text-center my-3" x-show="loading">
        <div class="spinner-border spinner-border-sm text-primary" role="status">
          <span class="visually-hidden">Memeriksa status absensi...</span>
        </div>
      </div>

      <!-- Tombol Absen -->
      <div class="row justify-content-center">
        <div class="col-6 mb-2">
          <button @click="absen('masuk')" class="btn btn-primary w-100 py-4" x-bind:disabled="sudahAbsen.masuk || loading">
            <svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" fill="currentColor" class="bi bi-door-open" viewBox="0 0 16 16">
              <path d="M8.5 10c-.276 0-.5-.448-.5-1s.224-1 .5-1 .5.448.5 1-.224 1-.5 1" />
              <path d="M10.828.122A.5.5 0 0 1 11 .5V1h.5A1.5 1.5 0 0 1 13 2.5V15h1.5a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1H3V1.5a.5.5 0 0 1 .43-.495l7-1a.5.5 0 0 1 .398.117M11.5 2H11v13h1V2.5a.5.5 0 0 0-.5-.5M4 1.934V15h6V1.077z" />
            </svg>
            <br>
            Absen Masuk
          </button>
        </div>
        <div class="col-6">
          <button @click="absen('pulang')" class="btn btn-danger w-100 py-4" x-bind:disabled="sudahAbsen.pulang || loading">
            <svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" fill="currentColor" class="bi bi-door-closed" viewBox="0 0 16 16">
              <path d="M3 2a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v13h1.5a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1H3zm1 13h8V2H4z" />
              <path d="M9 9a1 1 0 1 0 2 0 1 1 0 0 0-2 0" />
            </svg>
            <br>
            Absen Pulang
          </button>
        </div>
      </div>
    </div>
  </div>
  <script>
    document.addEventListener("alpine:init", () => {
      Alpine.data("absensiHandler", () => ({
        status: "Memeriksa status absensi...",
        sudahAbsen: {
          masuk: false,
          pulang: false
        },
        loading: true,

        async fetchStatus() {
          this.loading = true;
          try {
            const response = await fetch(`${absensiAjax.ajaxurl}?action=get_absensi_status`);
            const result = await response.json();

            if (result.success) {
              this.status = result.data.status || "Belum ada absensi hari ini.";
              this.sudahAbsen.masuk = result.data.sudahAbsen?.masuk ?? false;
              this.sudahAbsen.pulang = result.data.sudahAbsen?.pulang ?? false;
            } else {
              this.status = "Terjadi kesalahan dalam mengambil data absensi.";
            }
          } catch (error) {
            this.status = "Terjadi kesalahan saat memeriksa status.";
          } finally {
            this.loading = false;
          }
        },

        async absen(type) {
          this.loading = true;

          try {
            const position = await new Promise((resolve, reject) => {
              navigator.geolocation.getCurrentPosition(resolve, reject);
            });

            const formData = new FormData();
            formData.append("action", "save_absensi");
            formData.append("type", type);
            formData.append("lat", position.coords.latitude);
            formData.append("lng", position.coords.longitude);
            formData.append("_wpnonce", absensiAjax.nonce);

            const response = await fetch(absensiAjax.ajaxurl, {
              method: "POST",
              body: formData,
            });

            const result = await response.json();
            if (result.success) {
              this.fetchStatus();
            } else {
              this.status = result.data.message;
            }
          } catch (error) {
            this.status = "Terjadi kesalahan saat melakukan absensi.";
          } finally {
            this.loading = false;
          }
        },

        init() {
          this.fetchStatus();
        },
      }));
    });
  </script>
<?php
  return ob_get_clean();
}
add_shortcode('absensi', 'absensi_shortcode');

add_action('wp_ajax_save_absensi', 'handle_save_absensi');
add_action('wp_ajax_get_absensi_status', 'handle_get_absensi_status');

function handle_save_absensi()
{
  if (!wp_verify_nonce($_POST['_wpnonce'], 'absensi_nonce')) {
    wp_send_json_error(['message' => 'Nonce tidak valid.']);
  }

  global $wpdb;
  $table_name = $wpdb->prefix . 'absensi';
  $user_id = get_current_user_id();
  $type = sanitize_text_field($_POST['type']);
  $lat = floatval($_POST['lat']);
  $lng = floatval($_POST['lng']);
  $today = current_time('Y-m-d');

  // Mendapatkan data perangkat dan IP
  $user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
  $user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);

  $wpdb->insert(
    $table_name,
    [
      'user_id' => $user_id,
      'type' => $type,
      'lat' => $lat,
      'lng' => $lng,
      'device' => $user_agent,
      'ip_address' => $user_ip,
      'time' => current_time('mysql')
    ],
    ['%d', '%s', '%f', '%f', '%s', '%s', '%s']
  );

  wp_send_json_success(['message' => 'Absensi berhasil disimpan.']);
}


function handle_get_absensi_status()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'absensi';
  $user_id = get_current_user_id();
  $today = current_time('Y-m-d');

  $absensi_today = $wpdb->get_col(
    $wpdb->prepare(
      "SELECT type FROM $table_name WHERE user_id = %d AND DATE(time) = %s",
      $user_id,
      $today
    )
  );

  if (in_array('masuk', $absensi_today) && in_array('pulang', $absensi_today)) {
    $status = "Sampai jumpa lagi!";
  } elseif (in_array('masuk', $absensi_today)) {
    $status = "Tetap semangat & Fokus!";
  } else {
    $status = "Silakan absen masuk.";
  }

  wp_send_json_success([
    'status' => $status,
    'sudahAbsen' => [
      'masuk' => in_array('masuk', $absensi_today),
      'pulang' => in_array('pulang', $absensi_today)
    ],
  ]);
}

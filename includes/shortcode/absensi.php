<?php
if (!defined('ABSPATH')) exit;

function absensi_shortcode()
{
  if (!is_user_logged_in()) {
    return '<div class="container mt-4"><div class="alert alert-warning">Silakan login untuk melakukan absensi.</div>';
  }

  ob_start();
?>
  <div class="container mt-4">
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
            console.log(result);

            if (result.success) {
              this.status = result.data.status || "Belum ada absensi hari ini.";
              this.sudahAbsen.masuk = result.data.sudahAbsen?.masuk ?? false;
              this.sudahAbsen.pulang = result.data.sudahAbsen?.pulang ?? false;
            } else {
              this.status = "Terjadi kesalahan dalam mengambil data absensi.";
            }
          } catch (error) {
            console.error("Gagal memeriksa status absensi:", error);
            this.status = "Terjadi kesalahan saat memeriksa status.";
          } finally {
            this.loading = false;
          }
        },

        async absen(type) {
          this.loading = true;
          if ((type === 'masuk' && this.sudahAbsen.masuk) || (type === 'pulang' && this.sudahAbsen.pulang)) {
            console.log(`Anda sudah absen ${type} hari ini.`);
            this.loading = false;
            return;
          }

          if (!navigator.geolocation) {
            console.log("Geolocation tidak didukung di browser ini.");
            this.loading = false;
            return;
          }

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
              console.log(result.data.message);
              this.fetchStatus();
            } else {
              console.log(result.data.message || "Gagal melakukan absensi.");
              this.status = result.data.message; // Tampilkan pesan error
            }
          } catch (error) {
            console.error("Gagal melakukan absensi:", error);
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

  // Ambil koordinat dari leaflet_coordinates
  $leaflet_coordinates = json_decode(get_option('leaflet_coordinates'), true);

  // Jika tidak ada koordinat, batalkan absensi
  if (empty($leaflet_coordinates)) {
    wp_send_json_error([
      'message' => 'Tidak ada koordinat yang diizinkan untuk absensi.',
    ]);
  }

  // Validasi jarak ke semua koordinat
  $is_within_radius = false;
  foreach ($leaflet_coordinates as $coord) {
    $distance = calculate_distance($lat, $lng, $coord['lat'], $coord['lng']);
    if ($distance <= 100) {
      $is_within_radius = true;
      break; // Cukup satu koordinat yang memenuhi
    }
  }

  // Jika tidak ada koordinat yang memenuhi, batalkan absensi
  if (!$is_within_radius) {
    wp_send_json_error([
      'message' => 'Anda berada di luar radius 100 meter dari lokasi yang diizinkan.',
    ]);
  }

  // Cek apakah pengguna sudah absen
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
      'type' => $type,
      'lat' => $lat,
      'lng' => $lng,
      'time' => current_time('mysql'),
    ]
  );

  wp_send_json_success([
    'message' => "Absensi $type berhasil.",
    'sudahAbsen' => [
      'masuk' => $type === 'masuk',
      'pulang' => $type === 'pulang',
    ],
  ]);
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


function calculate_distance($lat1, $lng1, $lat2, $lng2)
{
  $earth_radius = 6371000; // Radius bumi dalam meter

  $lat1 = deg2rad($lat1);
  $lng1 = deg2rad($lng1);
  $lat2 = deg2rad($lat2);
  $lng2 = deg2rad($lng2);

  $delta_lat = $lat2 - $lat1;
  $delta_lng = $lng2 - $lng1;

  $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
    cos($lat1) * cos($lat2) *
    sin($delta_lng / 2) * sin($delta_lng / 2);

  $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

  $distance = $earth_radius * $c;
  return $distance; // Jarak dalam meter
}

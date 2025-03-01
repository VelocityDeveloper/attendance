<?php
if (!defined('ABSPATH')) exit;

function daftar_absensi_shortcode()
{
  if (!is_user_logged_in()) {
    return '<div class="container mt-4"><div class="alert alert-warning">Silakan login untuk melihat daftar absensi.</div></div>';
  }

  $current_user_id = get_current_user_id(); // Ambil ID pengguna yang sedang login
  $is_admin = current_user_can('administrator'); // Periksa apakah pengguna adalah admin
  $disable = !$is_admin ? 'disabled' : '';
  ob_start();
?>
  <div class="container mt-4">
    <div x-data="absensiListHandler()">
      <h4 class="text-center mb-3">Riwayat Absensi 30 Hari Terakhir</h4>

      <!-- Pilihan Karyawan -->
      <div class="mb-3">
        <label for="karyawanSelect" class="form-label">Pilih Karyawan:</label>
        <select id="karyawanSelect" class="form-select" x-model="selectedUser" @change="fetchAbsensi()" <?php echo $disable; ?>>
          <option value="0">-</option>
          <template x-for="user in users" :key="user.id">
            <option :value="user.id" x-text="user.name" x-bind:selected="selectedUser === user.id"></option>
          </template>
        </select>
      </div>

      <!-- Loading Spinner -->
      <div class="text-center my-3" x-show="loading">
        <div class="spinner-border spinner-border-sm text-primary" role="status">
          <span class="visually-hidden">Mengambil data absensi...</span>
        </div>
      </div>

      <!-- Tabel Absensi -->
      <div class="table-responsive">
        <div class="container mt-4">
          <div class="row bg-dark text-white py-2">
            <div class="col-3">Tanggal</div>
            <div class="col-3">Masuk</div>
            <div class="col-3">Pulang</div>
            <div class="col">Keterangan</div>
            <div class="col-2"></div>
          </div>

          <template x-for="absen in absensi" :key="absen.id">
            <div class="row border-bottom py-2">
              <div class="col-3">
                <span class="text-nowrap" x-text="formatTanggal(absen.masuk.time)"></span>
              </div>
              <div class="col-3">
                <template x-if="absen.masuk">
                  <span>
                    <span x-text="formatJam(absen.masuk.time)"></span>
                    <template x-if="absen.masuk.status == 'Terlambat'">
                      <span class="text-danger" x-bind:title="absen.masuk.status">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-exclamation-circle-fill" viewBox="0 0 16 16">
                          <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"></path>
                        </svg>
                      </span>
                    </template>
                  </span>
                </template>
              </div>
              <div class="col-3">
                <template x-if="absen.pulang">
                  <span>
                    <span x-text="formatJam(absen.pulang.time)"></span>
                    <template x-if="absen.pulang.status === 'Pulang awal'">
                      <span class="text-danger" x-bind:title="absen.pulang.status">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-exclamation-circle-fill" viewBox="0 0 16 16">
                          <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"></path>
                        </svg>
                      </span>
                    </template>
                  </span>
                </template>
              </div>
              <div class="col">
                <span x-text="absen.status"></span>
                <span class="text-danger" x-text="absen.selisih_waktu"></span>
              </div>
              <div class="col-2 text-end px-0">
                <div class="d-flex justify-content-end">
                  <template x-if="absen.masuk">
                    <button @click="deleteAbsensi(absen.masuk.id)" class="btn btn-danger btn-sm">
                      <template x-if="singleLoading[absen.masuk.id]">
                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                      </template>
                      <template x-if="!singleLoading[absen.masuk.id]">
                        <span>
                          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16">
                            <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5" />
                          </svg>
                          Masuk
                        </span>
                      </template>
                    </button>
                  </template>
                  <template x-if="absen.pulang">
                    <button @click="deleteAbsensi(absen.pulang.id)" class="btn btn-danger btn-sm ms-1">
                      <template x-if="singleLoading[absen.pulang.id]">
                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                      </template>
                      <template x-if="!singleLoading[absen.pulang.id]">
                        <span>
                          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16">
                            <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5" />
                          </svg>
                          Pulang
                        </span>
                      </template>
                    </button>
                  </template>
                </div>
              </div>

              <!-- hanya tampil untuk admin -->
              <?php if (current_user_can('administrator')) : ?>
                <div class="col-12 text-secondary">
                  <b>IP:</b> <span x-text="absen.masuk.ip_address"></span>
                  <b>DEVICE:</b> <span x-text="absen.masuk.device"></span>
                  <b>Lokasi Absen:</b> <a target="_blank" :href="`https://maps.google.com/?q=${absen.masuk.lat},${absen.masuk.lng}`">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-geo" viewBox="0 0 16 16">
                      <path fill-rule="evenodd" d="M8 1a3 3 0 1 0 0 6 3 3 0 0 0 0-6M4 4a4 4 0 1 1 4.5 3.969V13.5a.5.5 0 0 1-1 0V7.97A4 4 0 0 1 4 3.999zm2.493 8.574a.5.5 0 0 1-.411.575c-.712.118-1.28.295-1.655.493a1.3 1.3 0 0 0-.37.265.3.3 0 0 0-.057.09V14l.002.008.016.033a.6.6 0 0 0 .145.15c.165.13.435.27.813.395.751.25 1.82.414 3.024.414s2.273-.163 3.024-.414c.378-.126.648-.265.813-.395a.6.6 0 0 0 .146-.15l.015-.033L12 14v-.004a.3.3 0 0 0-.057-.09 1.3 1.3 0 0 0-.37-.264c-.376-.198-.943-.375-1.655-.493a.5.5 0 1 1 .164-.986c.77.127 1.452.328 1.957.594C12.5 13 13 13.4 13 14c0 .426-.26.752-.544.977-.29.228-.68.413-1.116.558-.878.293-2.059.465-3.34.465s-2.462-.172-3.34-.465c-.436-.145-.826-.33-1.116-.558C3.26 14.752 3 14.426 3 14c0-.599.5-1 .961-1.243.505-.266 1.187-.467 1.957-.594a.5.5 0 0 1 .575.411" />
                    </svg>
                    <span x-text="absen.lat"></span>,<span x-text="absen.lng"></span>
                  </a>
                </div>
              <?php endif; ?>

            </div>
          </template>
        </div>
      </div>

      <!-- Jika Tidak Ada Data -->
      <p class="text-center text-muted mt-3" x-show="absensi.length === 0 && !loading">Tidak ada data absensi.</p>
    </div>
  </div>
  <style>
    .col-2 {
      width: 10%;
    }
  </style>
  <script>
    document.addEventListener("alpine:init", () => {
      Alpine.data("absensiListHandler", () => ({
        users: [],
        selectedUser: "<?php echo $current_user_id; ?>", // Default ke ID user yang sedang login
        absensi: [],
        loading: true,
        singleLoading: {}, // Use an object to track loading state

        async fetchUsers() {
          try {
            const response = await fetch(`${absensiAjax.ajaxurl}?action=get_users`);
            const result = await response.json();

            if (result.success) {
              this.users = result.data.users;
              this.fetchAbsensi();
            }
          } catch (error) {
            console.error("Gagal mengambil daftar karyawan:", error);
          }
        },

        async fetchAbsensi() {
          if (!this.selectedUser) return; // Jangan ambil data absensi jika tidak ada user terpilih

          this.loading = true;
          try {
            const response = await fetch(`${absensiAjax.ajaxurl}?action=get_absensi_list&user_id=${this.selectedUser}`);
            const result = await response.json();

            if (result.success) {
              this.absensi = result.data.absensi;
            } else {
              this.absensi = [];
            }
          } catch (error) {
            console.error("Gagal mengambil data absensi:", error);
          } finally {
            this.loading = false;
          }
        },

        async deleteAbsensi(absenId) {
          this.singleLoading[absenId] = true; // Set loading state for specific button
          if (confirm("Apakah Anda yakin ingin menghapus absensi ini?")) {
            try {
              const response = await fetch(`${absensiAjax.ajaxurl}?action=delete_absensi`, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                  id: absenId
                }),
              });
              const result = await response.json();

              if (result.success) {
                this.fetchAbsensi(); // Refresh the list after deletion
              } else {
                alert(result.message || "Gagal menghapus absensi.");
              }
            } catch (error) {
              console.error("Gagal menghapus absensi:", error);
            } finally {
              this.singleLoading[absenId] = false; // Reset loading state
            }
          } else {
            this.singleLoading[absenId] = false; // Reset loading state if canceled
          }
        },

        formatTanggal(tanggal) {
          const options = {
            day: "numeric",
            month: "short",
            year: "numeric"
          };
          return new Date(tanggal).toLocaleDateString("id-ID", options);
        },

        formatJam(jam) {
          const options = {
            hour: "numeric",
            minute: "numeric"
          };
          return new Date(jam).toLocaleTimeString("id-ID", options);
        },

        init() {
          this.fetchUsers();
        },
      }));
    });
  </script>
<?php
  return ob_get_clean();
}
add_shortcode('daftar_absensi', 'daftar_absensi_shortcode');

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

  //kelompokkan data absensi berdasarkan tanggal
  $grouped_absensi = [];
  foreach ($absensi as $absen) {
    $tanggal = date('Y-m-d', strtotime($absen['time']));

    // Proses keterlambatan dan pulang sebelum waktunya
    if ($user_shift) {
      $absen_time = strtotime($absen['time']);
      $shift_start = strtotime(date('Y-m-d', $absen_time) . ' ' . $user_shift['start']);
      $shift_end = strtotime(date('Y-m-d', $absen_time) . ' ' . $user_shift['end']);

      if ($absen['type'] == 'masuk') {
        $absen['status'] = ($absen_time > $shift_start) ? 'Terlambat' : 'Masuk Tepat Waktu';
      } elseif ($absen['type'] == 'pulang') {
        $absen['status'] = ($absen_time < $shift_end) ? 'Pulang awal' : 'Pulang Tepat Waktu';
      } else {
        $absen['status'] = ($absen_time < $shift_end) ? 'Pulang awal' : 'Pulang Tepat Waktu';
      }

      $selish_masuk = $absen_time > $shift_start ? $absen_time - $shift_start : 0;
      $selish_pulang = $absen_time < $shift_end ? $shift_end - $absen_time : 0;
      $absen['selisih_waktu'] = ($absen['type'] == 'masuk') ? gmdate('H:i', $selish_masuk) : gmdate('H:i', $selish_pulang);
    } else {
      $absen['status'] = 'Shift Tidak Ditemukan';
    }

    $grouped_absensi[$tanggal][$absen['type']] = $absen;
  }

  wp_send_json_success(['user_id' => $user_id, 'absensi' => $grouped_absensi], 200);
}
add_action('wp_ajax_get_absensi_list', 'get_absensi_list_ajax');

function delete_absensi_ajax()
{
  // Read the raw input from the request body
  $input = json_decode(file_get_contents('php://input'), true);

  // Check if the 'id' is set in the decoded input
  if (!isset($input['id'])) {
    wp_send_json_error([
      'message' => 'Data tidak valid.',
      'data' => $input
    ]);
  }

  if (!is_user_logged_in()) {
    wp_send_json_error(['message' => 'Anda harus login.']);
  }

  global $wpdb;
  $table_name = $wpdb->prefix . 'absensi';
  $absen_id = intval($input['id']); // Use the id from the decoded input

  // Menghapus data absensi
  $deleted = $wpdb->delete($table_name, ['id' => $absen_id]);

  if ($deleted) {
    wp_send_json_success(['message' => 'Absensi berhasil dihapus.']);
  } else {
    wp_send_json_error(['message' => 'Gagal menghapus absensi.']);
  }
}
add_action('wp_ajax_delete_absensi', 'delete_absensi_ajax');

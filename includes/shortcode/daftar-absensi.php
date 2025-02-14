<?php
if (!defined('ABSPATH')) exit;

function daftar_absensi_shortcode()
{
  if (!is_user_logged_in()) {
    return '<div class="container mt-4"><div class="alert alert-warning">Silakan login untuk melihat daftar absensi.</div></div>';
  }

  ob_start();
?>
  <div class="container mt-4">
    <div x-data="absensiListHandler()" class="card shadow-sm p-4">
      <h4 class="text-center mb-3">Riwayat Absensi 30 Hari Terakhir</h4>

      <!-- Pilihan Karyawan -->
      <div class="mb-3">
        <label for="karyawanSelect" class="form-label">Pilih Karyawan:</label>
        <select id="karyawanSelect" class="form-select" x-model="selectedUser" @change="fetchAbsensi()">
          <option value="0">Semua Karyawan</option>
          <template x-for="user in users" :key="user.id">
            <option :value="user.id" x-text="user.name"></option>
          </template>
        </select>
      </div>

      <!-- Loading Spinner -->
      <div class="text-center my-3" x-show="loading">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Mengambil data absensi...</span>
        </div>
      </div>

      <!-- Tabel Absensi -->
      <div class="table-responsive">
        <table class="table table-striped">
          <thead class="table-dark">
            <tr>
              <th>Tanggal</th>
              <th>Jam</th>
              <th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="absen in absensi" :key="absen.id">
              <tr>
                <td>
                  <span class="text-nowrap" x-text="formatTanggal(absen.time)"></span>
                </td>
                <td>
                  <span x-text="formatJam(absen.time)"></span>
                  <br>
                </td>
                <td>
                  <span x-text="absen.status"></span> <span class="text-danger" x-text="absen.selisih_waktu"></span>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Jika Tidak Ada Data -->
      <p class="text-center text-muted mt-3" x-show="absensi.length === 0 && !loading">Tidak ada data absensi.</p>
    </div>
  </div>

  <script>
    document.addEventListener("alpine:init", () => {
      Alpine.data("absensiListHandler", () => ({
        users: [],
        selectedUser: "",
        absensi: [],
        loading: true,

        async fetchUsers() {
          try {
            const response = await fetch(`${absensiAjax.ajaxurl}?action=get_users`);
            const result = await response.json();

            if (result.success) {
              this.users = result.data.users;
              this.selectedUser = this.users.length ? this.users[0].id : "";
              this.fetchAbsensi();
            }
          } catch (error) {
            console.error("Gagal mengambil daftar karyawan:", error);
          }
        },

        async fetchAbsensi() {
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

  // Proses keterlambatan dan pulang sebelum waktunya
  foreach ($absensi as &$absen) {
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

      $selish_masuk = $absen_time > $shift_start ? $absen_time - $shift_start : '00:00:00';
      $selish_pulang = $absen_time < $shift_end ? $shift_end - $absen_time : '00:00:00';
      $absen['selisih_waktu'] = ($absen['type'] == 'masuk') ? gmdate('H:i', $selish_masuk) : gmdate('H:i', $selish_pulang);
    } else {
      $absen['status'] = 'Shift Tidak Ditemukan';
    }
  }

  wp_send_json_success(['user_id' => $user_id, 'absensi' => $absensi]);
}
add_action('wp_ajax_get_absensi_list', 'get_absensi_list_ajax');

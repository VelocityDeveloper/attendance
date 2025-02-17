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
    <div x-data="absensiListHandler()">
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
        <div class="spinner-border spinner-border-sm text-primary" role="status">
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
              <th>Aksi</th>
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
                  <span x-text="absen.status"></span>
                  <span class="text-danger" x-text="absen.selisih_waktu"></span>
                </td>
                <td>
                  <button @click="deleteAbsensi(absen.id)" class="btn btn-danger btn-sm">
                    <template x-if="singleLoading[absen.id]">
                      <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                    </template>
                    <template x-if="!singleLoading[absen.id]">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16">
                        <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5" />
                      </svg>
                    </template>
                  </button>
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
        singleLoading: {}, // Use an object to track loading state

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

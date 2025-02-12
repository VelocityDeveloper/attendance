<?php
if (!defined('ABSPATH')) exit;

function absensi_shortcode()
{
  if (!is_user_logged_in()) {
    return '<p class="text-center">Silakan login untuk melakukan absensi.</p>';
  }

  ob_start();
?>
  <div class="container mt-4">
    <div x-data="absensiHandler()" class="card shadow-sm p-4">

      <!-- Status Absensi -->
      <div class="alert alert-info text-center" x-text="status"></div>

      <!-- Loading Spinner -->
      <div class="text-center my-3" x-show="loading">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Memeriksa status absensi...</span>
        </div>
      </div>

      <!-- Tombol Absen -->
      <div class="row justify-content-center">
        <div class="col-6 mb-2">
          <button @click="absen('masuk')" class="btn btn-primary w-100" x-bind:disabled="sudahAbsen.masuk">
            <svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" fill="currentColor" class="bi bi-door-open" viewBox="0 0 16 16">
              <path d="M8.5 10c-.276 0-.5-.448-.5-1s.224-1 .5-1 .5.448.5 1-.224 1-.5 1" />
              <path d="M10.828.122A.5.5 0 0 1 11 .5V1h.5A1.5 1.5 0 0 1 13 2.5V15h1.5a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1H3V1.5a.5.5 0 0 1 .43-.495l7-1a.5.5 0 0 1 .398.117M11.5 2H11v13h1V2.5a.5.5 0 0 0-.5-.5M4 1.934V15h6V1.077z" />
            </svg>
            <br>
            Absen Masuk
          </button>
        </div>
        <div class="col-6">
          <button @click="absen('pulang')" class="btn btn-danger w-100" x-bind:disabled="sudahAbsen.pulang">
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
          keluar: false
        }, // Default nilai

        async fetchStatus() {
          try {
            const response = await fetch(
              `${absensiAjax.ajaxurl}?action=get_absensi_status`
            );
            const result = await response.json();

            console.log(result); // Debugging untuk melihat struktur respons

            if (result.success) {
              this.status = result.data.status || "Belum ada absensi hari ini.";

              this.sudahAbsen.masuk = result.data.sudahAbsen.masuk;
              this.sudahAbsen.pulang = result.data.sudahAbsen.pulang;
            } else {
              this.status = "Terjadi kesalahan dalam mengambil data absensi.";
            }
          } catch (error) {
            console.error("Gagal memeriksa status absensi:", error);
            this.status = "Terjadi kesalahan saat memeriksa status.";
          }
        },

        async absen(type) {
          // Cek apakah sudah absen masuk atau keluar
          if (this.sudahAbsen.masuk && type === "masuk") {
            console.log("Anda sudah absen masuk hari ini.");
            return;
          }

          if (this.sudahAbsen.keluar && type === "keluar") {
            console.log("Anda sudah absen keluar hari ini.");
            return;
          }

          // Cek apakah browser mendukung geolocation
          if (!navigator.geolocation) {
            console.log("Geolocation tidak didukung di browser ini.");
            return;
          }

          try {
            // Ambil lokasi pengguna
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
            }
          } catch (error) {
            console.error("Gagal melakukan absensi:", error);
            console.log("Terjadi kesalahan saat melakukan absensi.");
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
        <table class="table table-bordered">
          <thead class="table-dark text-center">
            <tr>
              <th>Tanggal</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="absen in absensi" :key="absen.id">
              <tr>
                <td x-text="formatTanggal(absen.time)"></td>
                <td>
                  <span class="badge" :class="absen.type === 'masuk' ? 'bg-success' : 'bg-danger'" x-text="absen.masuk ? 'Masuk' : 'Pulang'"></span>
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
            month: "long",
            year: "numeric"
          };
          return new Date(tanggal).toLocaleDateString("id-ID", options);
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

function shift_settings_shortcode()
{
  if (!current_user_can('manage_options')) {
    return '<p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>';
  }

  ob_start();
?>
  <div class="container mt-4" x-data="shiftManager()">
    <!-- List Shift -->
    <div class="card p-3">
      <h5>Tambah Shift</h5>
      <div class="row">
        <div class="col-4 px-1">
          <input type="text" class="form-control" placeholder="Nama Shift" x-model="newShift.name">
        </div>
        <div class="col-3 px-1">
          <input type="time" class="form-control" x-model="newShift.start">
        </div>
        <div class="col-3 px-1">
          <input type="time" class="form-control" x-model="newShift.end">
        </div>
        <div class="col-2 px-1">
          <button class="btn btn-primary w-100" @click="addShift()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
              <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4" />
            </svg>
          </button>
        </div>
      </div>

      <h5 class="mt-3">Daftar Shift</h5>
      <table class="table table-striped mt-2">
        <thead class="table-dark text-center">
          <tr>
            <th>Shift</th>
            <th>Jam</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <template x-for="(shift, index) in shifts" :key="index">
            <tr>
              <td><input type="text" class="form-control" x-model="shift.name"></td>
              <td>
                <input type="time" class="form-control mb-1" x-model="shift.start">
                <input type="time" class="form-control" x-model="shift.end">
              </td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-success btn-sm" @click="updateShift(index)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-floppy2" viewBox="0 0 16 16">
                      <path d="M1.5 0h11.586a1.5 1.5 0 0 1 1.06.44l1.415 1.414A1.5 1.5 0 0 1 16 2.914V14.5a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13A1.5 1.5 0 0 1 1.5 0M1 1.5v13a.5.5 0 0 0 .5.5H2v-4.5A1.5 1.5 0 0 1 3.5 9h9a1.5 1.5 0 0 1 1.5 1.5V15h.5a.5.5 0 0 0 .5-.5V2.914a.5.5 0 0 0-.146-.353l-1.415-1.415A.5.5 0 0 0 13.086 1H13v3.5A1.5 1.5 0 0 1 11.5 6h-7A1.5 1.5 0 0 1 3 4.5V1H1.5a.5.5 0 0 0-.5.5m9.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5z" />
                    </svg>
                  </button>
                  <button class="btn btn-danger btn-sm" @click="deleteShift(index)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                      <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z" />
                      <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    document.addEventListener("alpine:init", () => {
      Alpine.data("shiftManager", () => ({
        shifts: [],
        newShift: {
          name: '',
          start: '',
          end: ''
        },

        async fetchShifts() {
          const response = await fetch("<?= admin_url('admin-ajax.php?action=get_shifts') ?>");
          const result = await response.json();
          this.shifts = result.data || [];
        },

        async addShift() {
          if (!this.newShift.name || !this.newShift.start || !this.newShift.end) {
            alert("Mohon isi semua data shift!");
            return;
          }

          const formData = new FormData();
          formData.append("action", "save_shift");
          formData.append("shift", JSON.stringify(this.newShift));

          const response = await fetch("<?= admin_url('admin-ajax.php') ?>", {
            method: "POST",
            body: formData
          });

          const result = await response.json();
          if (result.success) {
            this.fetchShifts();
            this.newShift = {
              name: '',
              start: '',
              end: ''
            };
          } else {
            alert("Gagal menambahkan shift.");
          }
        },

        async updateShift(index) {
          const formData = new FormData();
          formData.append("action", "update_shift");
          formData.append("index", index);
          formData.append("shift", JSON.stringify(this.shifts[index]));

          const response = await fetch("<?= admin_url('admin-ajax.php') ?>", {
            method: "POST",
            body: formData
          });

          const result = await response.json();
          if (result.success) {
            alert("Shift berhasil diperbarui!");
          } else {
            alert("Gagal memperbarui shift.");
          }
        },

        async deleteShift(index) {
          if (!confirm("Yakin ingin menghapus shift ini?")) return;

          const formData = new FormData();
          formData.append("action", "delete_shift");
          formData.append("index", index);

          const response = await fetch("<?= admin_url('admin-ajax.php') ?>", {
            method: "POST",
            body: formData
          });

          const result = await response.json();
          if (result.success) {
            this.fetchShifts();
          } else {
            alert("Gagal menghapus shift.");
          }
        },

        init() {
          this.fetchShifts();
        }
      }));
    });
  </script>
<?php
  return ob_get_clean();
}
add_shortcode('shift_settings', 'shift_settings_shortcode');

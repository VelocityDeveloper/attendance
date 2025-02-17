<?php
if (!defined('ABSPATH')) exit;

function shift_settings_shortcode()
{
  if (!current_user_can('manage_options')) {
    return '';
  }

  ob_start();
?>
  <div class="container mt-4" x-data="shiftManager()">
    <!-- List Shift -->
    <div>
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

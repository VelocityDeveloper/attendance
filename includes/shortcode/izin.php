<?php
if (!defined('ABSPATH')) exit;

function izin_form_shortcode()
{
  if (!is_user_logged_in()) {
    return '<div class="container mt-4"><div class="alert alert-warning">Silakan login untuk mengajukan izin.</div></div>';
  }

  ob_start();
?>
  <div class="container mt-4">
    <div x-data="izinFormHandler()">
      <h4 class="text-center mb-3">Form Pengajuan Izin</h4>
      <form @submit.prevent="submitForm">
        <div class="mb-3">
          <label for="jenisIzin" class="form-label">Jenis Izin:</label>
          <select id="jenisIzin" class="form-select" x-model="jenisIzin" required>
            <option value="">Pilih Jenis Izin</option>
            <option value="cuti">Cuti</option>
            <option value="sakit">Sakit</option>
            <option value="alfa">Alfa</option>
          </select>
        </div>

        <div class="mb-3">
          <label for="startDate" class="form-label">Tanggal Mulai Izin:</label>
          <input type="date" id="startDate" class="form-control" x-model="startDate" required>
        </div>

        <div class="mb-3">
          <label for="endDate" class="form-label">Tanggal Akhir Izin:</label>
          <input type="date" id="endDate" class="form-control" x-model="endDate" @change="calculateDays()" required>
        </div>

        <div class="mb-3">
          <label for="jumlahHari" class="form-label">Jumlah Hari Kerja:</label>
          <input type="number" id="jumlahHari" class="form-control" x-model="jumlahHari" min="1" readonly>
        </div>

        <div class="mb-3">
          <label for="deskripsi" class="form-label">Deskripsi:</label>
          <textarea id="deskripsi" class="form-control" x-model="deskripsi" rows="3" required></textarea>
        </div>

        <div class="mb-3">
          <label for="lampiran" class="form-label">Lampiran Gambar:</label>
          <input type="file" id="lampiran" class="form-control" @change="handleFileUpload" accept="image/*" required>
        </div>

        <div class="text-center">
          <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
        </div>

        <div class="text-center my-3" x-show="loading">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Mengirim...</span>
          </div>
        </div>

        <p class="text-success text-center" x-show="successMessage" x-text="successMessage"></p>
        <p class="text-danger text-center" x-show="errorMessage" x-text="errorMessage"></p>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener("alpine:init", () => {
      Alpine.data("izinFormHandler", () => ({
        jenisIzin: "",
        startDate: "",
        endDate: "",
        jumlahHari: 1,
        deskripsi: "",
        lampiran: null,
        loading: false,
        successMessage: "",
        errorMessage: "",

        handleFileUpload(event) {
          this.lampiran = event.target.files[0];
        },

        calculateDays() {
          if (this.startDate && this.endDate) {
            const start = new Date(this.startDate);
            const end = new Date(this.endDate);
            const timeDiff = end - start;
            const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; // Include start day
            this.jumlahHari = dayDiff > 0 ? dayDiff : 0;
          }
        },

        async submitForm() {
          this.loading = true;
          this.successMessage = "";
          this.errorMessage = "";

          const formData = new FormData();
          formData.append("action", "submit_izin");
          formData.append("jenis_izin", this.jenisIzin);
          formData.append("start_date", this.startDate);
          formData.append("end_date", this.endDate);
          formData.append("jumlah_hari", this.jumlahHari);
          formData.append("deskripsi", this.deskripsi);
          formData.append("lampiran", this.lampiran);

          try {
            const response = await fetch(<?= json_encode(admin_url('admin-ajax.php')); ?>, {
              method: 'POST',
              body: formData,
            });

            const result = await response.json();

            if (result.success) {
              this.successMessage = "Pengajuan izin berhasil.";
              this.resetForm();
            } else {
              this.errorMessage = result.data.message || "Gagal mengajukan izin.";
            }
          } catch (error) {
            this.errorMessage = "Terjadi kesalahan saat mengirim pengajuan.";
            console.error("Error:", error);
          } finally {
            this.loading = false;
          }
        },

        resetForm() {
          this.jenisIzin = "";
          this.startDate = "";
          this.endDate = "";
          this.jumlahHari = 1;
          this.deskripsi = "";
          this.lampiran = null;
        },
      }));
    });
  </script>
<?php
  return ob_get_clean();
}
add_shortcode('izin_form', 'izin_form_shortcode');

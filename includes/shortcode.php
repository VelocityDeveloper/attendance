<?php
if (!defined('ABSPATH')) exit;

function absensi_shortcode()
{
  if (!is_user_logged_in()) {
    return '<p>Silakan login untuk melakukan absensi.</p>';
  }

  ob_start();
?>
  <div x-data="absensiHandler()">
    <p x-text="status"></p>
    <button @click="absen('masuk')" x-bind:disabled="sudahAbsen.masuk">Absen Masuk</button>
    <button @click="absen('pulang')" x-bind:disabled="sudahAbsen.pulang">Absen pulang</button>
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
            alert("Anda sudah absen masuk hari ini.");
            return;
          }

          if (this.sudahAbsen.keluar && type === "keluar") {
            alert("Anda sudah absen keluar hari ini.");
            return;
          }

          // Cek apakah browser mendukung geolocation
          if (!navigator.geolocation) {
            alert("Geolocation tidak didukung di browser ini.");
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
              alert(result.data.message);
              this.fetchStatus(); // Perbarui status setelah absen
            } else {
              alert(result.data.message || "Gagal melakukan absensi.");
            }
          } catch (error) {
            console.error("Gagal melakukan absensi:", error);
            alert("Terjadi kesalahan saat melakukan absensi.");
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

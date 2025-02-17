<?php
function leaflet_map_shortcode()
{
  ob_start();
  $coordinates = get_option("leaflet_coordinates", "[]");
?>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />

  <div x-data="mapComponent()" class="container mt-4">
    <h5 class=" mb-3">Peta Lokasi</h5>
    <div id="map" style="height: 400px;" class="rounded"></div>
    <ul class="list-group mt-3">
      <template x-for="(coord, index) in coordinates" :key="index">
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span class="text-truncate w-25" x-text="coord.lat"></span>
          <span class="text-truncate w-25" x-text="coord.lng"></span>
          <button @click="removeCoordinate(index)" class="btn btn-sm btn-danger">Hapus</button>
        </li>
      </template>
    </ul>
    <button @click="saveCoordinates()" class="btn btn-success mt-3">Simpan Koordinat</button>
    <input type="hidden" id="coordinates-input" x-model="coordinates">
  </div>

  <script>
    document.addEventListener("alpine:init", () => {
      Alpine.data("mapComponent", () => ({
        coordinates: JSON.parse('<?php echo addslashes($coordinates); ?>'),
        map: null,
        markers: [], // Array untuk menyimpan referensi marker

        init() {
          this.map = L.map('map').setView([-2.5489, 118.0149], 5);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
          }).addTo(this.map);

          console.log('Koordinat awal:', this.coordinates);

          // Tambahkan marker dari koordinat awal
          this.coordinates.forEach(coord => {
            this.addMarker(coord.lat, coord.lng);
          });

          // Tambahkan marker baru saat peta diklik
          this.map.on('click', (e) => {
            this.coordinates.push({
              lat: e.latlng.lat,
              lng: e.latlng.lng
            });
            this.addMarker(e.latlng.lat, e.latlng.lng);
          });
        },

        // Fungsi untuk menambahkan marker
        addMarker(lat, lng) {
          const marker = L.marker([lat, lng]).addTo(this.map)
            .bindPopup(`Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`)
            .openPopup();
          this.markers.push(marker); // Simpan referensi marker
        },

        // Fungsi untuk menghapus koordinat dan marker
        removeCoordinate(index) {
          // Hapus marker dari peta
          const marker = this.markers[index];
          this.map.removeLayer(marker);

          // Hapus marker dari array markers
          this.markers.splice(index, 1);

          // Hapus koordinat dari array coordinates
          this.coordinates.splice(index, 1);
        },

        // Fungsi untuk menyimpan koordinat
        saveCoordinates() {
          fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
              method: "POST",
              headers: {
                "Content-Type": "application/x-www-form-urlencoded"
              },
              body: new URLSearchParams({
                action: "save_leaflet_coordinates",
                coordinates: JSON.stringify(this.coordinates),
                _ajax_nonce: "<?php echo wp_create_nonce('save_leaflet_coordinates_nonce'); ?>"
              })
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                alert(data.data.message);
              } else {
                alert(data.data.message);
              }
            })
            .catch(error => console.error("Error:", error));
        }
      }));
    });
  </script>

  <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<?php
  return ob_get_clean();
}
add_shortcode('leaflet_map', 'leaflet_map_shortcode');

function save_leaflet_coordinates()
{
  check_ajax_referer('save_leaflet_coordinates_nonce', '_ajax_nonce');

  if (isset($_POST['coordinates'])) {
    $coordinates = json_decode(stripslashes($_POST['coordinates']), true);
    if (json_last_error() === JSON_ERROR_NONE) {
      update_option('leaflet_coordinates', json_encode($coordinates));
      wp_send_json_success(['message' => 'Koordinat berhasil disimpan!']);
    } else {
      wp_send_json_error(['message' => 'Data koordinat tidak valid.']);
    }
  } else {
    wp_send_json_error(['message' => 'Gagal menyimpan koordinat.']);
  }
}
add_action('wp_ajax_save_leaflet_coordinates', 'save_leaflet_coordinates');
add_action('wp_ajax_nopriv_save_leaflet_coordinates', 'save_leaflet_coordinates');

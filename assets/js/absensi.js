function absensiHandler() {
  return {
    status: "Memeriksa status absensi...",

    async fetchStatus() {
      let response = await fetch(
        absensiAjax.ajaxurl + "?action=get_absensi_status"
      );
      let result = await response.json();
      this.status = result.status || "Belum ada absensi hari ini.";
    },

    async absen(type) {
      if (!navigator.geolocation) {
        alert("Geolocation tidak didukung di browser ini.");
        return;
      }

      navigator.geolocation.getCurrentPosition(async (position) => {
        let formData = new FormData();
        formData.append("action", "save_absensi");
        formData.append("type", type);
        formData.append("lat", position.coords.latitude);
        formData.append("lng", position.coords.longitude);
        formData.append("_wpnonce", absensiAjax.nonce);

        let response = await fetch(absensiAjax.ajaxurl, {
          method: "POST",
          body: formData,
        });

        let result = await response.json();
        alert(result.message);
        this.fetchStatus(); // Perbarui status setelah absen
      });
    },

    init() {
      this.fetchStatus();
    },
  };
}

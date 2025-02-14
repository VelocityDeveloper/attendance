function updateIzinStatus(postId, status) {
  if (!confirm("Apakah Anda yakin ingin mengubah status izin ini?")) return;

  jQuery.ajax({
    url: absensiAjax.ajaxurl,
    type: "POST",
    data: {
      action: "update_izin_status",
      post_id: postId,
      status: status,
    },
    success: function (response) {
      if (response.success) {
        location.reload(); // Reload the page to see the updated status
      } else {
        alert(response.data.message || "Gagal mengubah status izin.");
      }
    },
    error: function () {
      alert("Terjadi kesalahan saat mengubah status izin.");
    },
  });
}

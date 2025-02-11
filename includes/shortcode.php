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
    <button @click="absen('masuk')" class="button">Check In</button>
    <button @click="absen('pulang')" class="button">Check Out</button>
    <p x-text="status"></p>
  </div>
<?php
  return ob_get_clean();
}
add_shortcode('absensi', 'absensi_shortcode');
?>
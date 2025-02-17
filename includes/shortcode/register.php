<?php
function registration_shortcode()
{
  if (is_user_logged_in()) {
    return '<div class="alert alert-success">Anda telah login.</div>';
  }

  ob_start();
?>
  <div class="container mt-4">
    <h4>Register</h4>
    <form method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
      <div class="mb-3">
        <label for="username" class="form-label">Username:</label>
        <input type="text" name="username" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email:</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password:</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" name="register" class="btn btn-primary">Register</button>
    </form>
    <?php
    if (isset($_POST['register'])) {
      $username = sanitize_text_field($_POST['username']);
      $email = sanitize_email($_POST['email']);
      $password = sanitize_text_field($_POST['password']);

      $user_id = wp_create_user($username, $password, $email);
      if (is_wp_error($user_id)) {
        echo '<div class="alert alert-danger">' . $user_id->get_error_message() . '</div>';
      } else {
        echo '<div class="alert alert-success">Registrasi berhasil. Silakan login.</div>';
      }
    }
    ?>
  </div>
<?php
  return ob_get_clean();
}
add_shortcode('custom_registration', 'registration_shortcode');

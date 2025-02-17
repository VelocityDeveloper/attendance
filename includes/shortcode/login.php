<?php
function login_shortcode()
{
    if (is_user_logged_in()) {
        return '<div class="alert alert-success">Anda telah login.</div>';
    }
    $site_key = get_theme_mod('captcha_velocity__sitekey', '');

    ob_start();
?>
    <div class="container mt-4">
        <h4>Login</h4>
        <form method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <!-- Google reCAPTCHA -->
            <div class="mb-3">
                <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($site_key); ?>"></div>
            </div>

            <button type="submit" name="login" class="btn btn-primary">Login</button>
        </form>
        <?php
        if (isset($_POST['login'])) {
            $creds = array(
                'user_login' => sanitize_text_field($_POST['username']),
                'user_password' => sanitize_text_field($_POST['password']),
                'remember' => true,
            );

            // Verify reCAPTCHA
            $recaptcha_secret = get_theme_mod('captcha_velocity__secretkey', '');
            $response = $_POST['g-recaptcha-response'];
            $remote_ip = $_SERVER['REMOTE_ADDR'];
            $recaptcha_response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$response}&remoteip={$remote_ip}");
            $recaptcha_data = json_decode($recaptcha_response);

            if ($recaptcha_data->success) {
                $user = wp_signon($creds, false);
                if (is_wp_error($user)) {
                    echo '<div class="alert alert-danger">' . $user->get_error_message() . '</div>';
                } else {
                    wp_redirect(home_url());
                    exit;
                }
            } else {
                echo '<div class="alert alert-danger">reCAPTCHA validation failed. Please try again.</div>';
            }
        }
        ?>
    </div>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php
    return ob_get_clean();
}
add_shortcode('custom_login', 'login_shortcode');

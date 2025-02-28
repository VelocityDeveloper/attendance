<?php
function attendance_login_form()
{
    ob_start();
    $args = array(
        'echo'            => true,
        'redirect'        => get_home_url(),
        'remember'        => true,
        'value_remember'  => true,
    );
    echo wp_login_form($args);
    $form = ob_get_clean();

    $form = str_replace('input', 'input form-control', $form);
    $form = str_replace('login-submit', 'login-submit text-end', $form);
    $form = str_replace('button button-primary', 'button button-primary btn btn-primary px-4', $form);
    $form = str_replace('<p class="login-submit text-end">', '<p class="login-submit text-end"><a class="btn rounded-0 btn-outline-primary" href="' . get_site_url() . '/register">Daftar</a>', $form);

    return $form;
}


function login_shortcode()
{

    ob_start();

    if (is_user_logged_in()) {
        echo '<div class="alert alert-success">Anda telah login.</div>';
    }

    echo attendance_login_form();

    return ob_get_clean();
}
add_shortcode('custom_login', 'login_shortcode');

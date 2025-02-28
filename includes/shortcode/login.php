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
        echo '<a class="btn btn-primary" href="' . wp_logout_url() . '/logout">Logout <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16">
        <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/>
        <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/>
        </svg></a>';
    } else {
        echo attendance_login_form();
    }

    return ob_get_clean();
}
add_shortcode('custom_login', 'login_shortcode');

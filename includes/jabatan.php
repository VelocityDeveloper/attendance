<?php
// Add Jabatan field to user profile
add_action('show_user_profile', 'add_jabatan_field');
add_action('edit_user_profile', 'add_jabatan_field');

function add_jabatan_field($user)
{
?>
  <h3>Informasi Tambahan</h3>
  <table class="form-table">
    <tr>
      <th><label for="jabatan">Jabatan</label></th>
      <td>
        <input type="text" name="jabatan" id="jabatan" value="<?php echo esc_attr(get_user_meta($user->ID, 'jabatan', true)); ?>" class="regular-text" />
      </td>
    </tr>
  </table>
<?php
}

// Save the Jabatan field
add_action('personal_options_update', 'save_jabatan_field');
add_action('edit_user_profile_update', 'save_jabatan_field');

function save_jabatan_field($user_id)
{
  if (!current_user_can('edit_user', $user_id)) {
    return false;
  }

  update_user_meta($user_id, 'jabatan', sanitize_text_field($_POST['jabatan']));
}

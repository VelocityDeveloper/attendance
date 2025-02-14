<?php
if (!defined('ABSPATH')) exit;


function shift_assignment_shortcode()
{
  if (!current_user_can('manage_options')) {
    return '<p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>';
  }

  global $wpdb;
  $users = get_users(['role__in' => ['subscriber', 'editor', 'author', 'contributor', 'administrator']]);
  $shifts = get_option('work_shifts', []);

  ob_start();
?>
  <div class="container mt-4" x-data="shiftAssignment()">
    <!-- List Shift Karyawan -->
    <div class="card p-3">
      <h5>Shift Karyawan</h5>
      <div class="row">
        <div class="col-md-5">
          <select class="form-select" x-model="newAssignment.user_id">
            <option value="">Pilih Karyawan</option>
            <?php foreach ($users as $user) : ?>
              <option value="<?= $user->ID ?>"><?= $user->display_name ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-5">
          <select class="form-select" x-model="newAssignment.shift">
            <option value="">Pilih Shift</option>
            <?php foreach ($shifts as $index => $shift) : ?>
              <option value="<?= $index ?>"><?= esc_html($shift['name']) ?> (<?= esc_html($shift['start']) ?> - <?= esc_html($shift['end']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary w-100" @click="assignShift()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
              <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4" />
            </svg>
          </button>
        </div>
      </div>
      <table class="table table-striped mt-2">
        <thead class="table-dark">
          <tr>
            <th>Karyawan</th>
            <th>Shift</th>
            <th>Jam Kerja</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <template x-for="(assignment, index) in assignments" :key="index">
            <tr>
              <td x-text="assignment.user_name"></td>
              <td x-text="assignment.shift_name"></td>
              <td x-text="assignment.shift_start + ' - ' + assignment.shift_end"></td>
              <td>
                <button class="btn btn-danger btn-sm" @click="removeAssignment(index)">Hapus</button>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    document.addEventListener("alpine:init", () => {
      Alpine.data("shiftAssignment", () => ({
        assignments: [],
        newAssignment: {
          user_id: '',
          shift: ''
        },

        async fetchAssignments() {
          const response = await fetch("<?= admin_url('admin-ajax.php?action=get_shift_assignments') ?>");
          const result = await response.json();
          this.assignments = result.data || [];
        },

        async assignShift() {
          if (!this.newAssignment.user_id || !this.newAssignment.shift) {
            alert("Mohon pilih karyawan dan shift!");
            return;
          }

          const formData = new FormData();
          formData.append("action", "assign_shift");
          formData.append("assignment", JSON.stringify(this.newAssignment));

          const response = await fetch("<?= admin_url('admin-ajax.php') ?>", {
            method: "POST",
            body: formData
          });

          const result = await response.json();
          if (result.success) {
            this.fetchAssignments();
            this.newAssignment = {
              user_id: '',
              shift: ''
            };
          } else {
            alert("Gagal menyimpan shift.");
          }
        },

        async removeAssignment(index) {
          if (!confirm("Yakin ingin menghapus shift ini?")) return;

          const formData = new FormData();
          formData.append("action", "remove_shift_assignment");
          formData.append("index", index);

          const response = await fetch("<?= admin_url('admin-ajax.php') ?>", {
            method: "POST",
            body: formData
          });

          const result = await response.json();
          if (result.success) {
            this.fetchAssignments();
          } else {
            alert("Gagal menghapus shift.");
          }
        },

        init() {
          this.fetchAssignments();
        }
      }));
    });
  </script>
<?php
  return ob_get_clean();
}
add_shortcode('shift_assignment', 'shift_assignment_shortcode');


add_action('wp_ajax_get_shift_assignments', function () {
  $assignments = get_option('shift_assignments', []);

  foreach ($assignments as &$assignment) {
    $user = get_user_by('ID', $assignment['user_id']);
    $shifts = get_option('work_shifts', []);
    $shift = $shifts[$assignment['shift']] ?? null;

    $assignment['user_name'] = $user ? $user->display_name : 'Unknown';
    $assignment['shift_name'] = $shift['name'] ?? 'Unknown';
    $assignment['shift_start'] = $shift['start'] ?? '00:00';
    $assignment['shift_end'] = $shift['end'] ?? '00:00';
  }

  wp_send_json_success($assignments);
});

add_action('wp_ajax_assign_shift', function () {
  $assignments = get_option('shift_assignments', []);
  $new_assignment = json_decode(stripslashes($_POST['assignment']), true);
  $assignments[] = $new_assignment;
  update_option('shift_assignments', $assignments);
  wp_send_json_success();
});

add_action('wp_ajax_remove_shift_assignment', function () {
  $assignments = get_option('shift_assignments', []);
  $index = intval($_POST['index']);

  if (isset($assignments[$index])) {
    array_splice($assignments, $index, 1);
    update_option('shift_assignments', $assignments);
    wp_send_json_success();
  } else {
    wp_send_json_error();
  }
});

add_action('wp_ajax_delete_shift', function () {
  $shifts = get_option('work_shifts', []);
  $index = intval($_POST['index']);

  if (isset($shifts[$index])) {
    array_splice($shifts, $index, 1);
    update_option('work_shifts', $shifts);
    wp_send_json_success();
  } else {
    wp_send_json_error();
  }
});

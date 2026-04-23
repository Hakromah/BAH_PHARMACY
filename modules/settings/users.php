<?php
require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$error = null;
$success = null;

// Aksiyonlar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], post('csrf_token')))
        die(__('error'));

    $action = post('action');

    if ($action === 'add_user') {
        $user = post('username');
        $pw = post('password');
        $fname = post('first_name');
        $lname = post('last_name');
        $timeout = (int) post('timeout', 30);

        if (empty($user) || empty($pw)) {
            $error = __('username_password_required');
        } else {
            try {
                $hash = password_hash($pw, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, first_name, last_name, session_timeout) VALUES (:u, :p, :f, :l, :t)");
                $stmt->execute([':u' => $user, ':p' => $hash, ':f' => $fname, ':l' => $lname, ':t' => $timeout]);
                $success = __('user_added');
                logAction('User Added', "New user created: $user");
            } catch (Exception $e) {
                $error = __('username_taken');
            }
        }
    } elseif ($action === 'delete_user') {
        $uid = (int) post('user_id');
        if ($uid === (int) $_SESSION['user_id']) {
            $error = __('self_delete_error');
        } else {
            $pdo->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $uid]);
            $success = __('user_deleted');
            logAction('User Deleted', "ID: $uid");
        }
    } elseif ($action === 'update_profile') {
        // Mevcut kullanıcı kendi profilini güncelliyor
        $uid = (int) $_SESSION['user_id'];
        $user = post('username');
        $fname = post('first_name');
        $lname = post('last_name');
        $timeout = (int) post('timeout', 30);
        $newPw = post('new_password');

        if (empty($user)) {
            $error = __('username_required');
        } else {
            try {
                $pdo->prepare("UPDATE users SET username = :u, first_name = :f, last_name = :l, session_timeout = :t WHERE id = :id")
                    ->execute([':u' => $user, ':f' => $fname, ':l' => $lname, ':t' => $timeout, ':id' => $uid]);

                if (!empty($newPw)) {
                    $hash = password_hash($newPw, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET password = :p WHERE id = :id")
                        ->execute([':p' => $hash, ':id' => $uid]);
                }

                $_SESSION['username'] = $user;
                $_SESSION['user_name'] = $fname . ' ' . $lname;
                $_SESSION['user_timeout'] = $timeout;
                $success = __('profile_updated');
                logAction('Profile Updated', "User #$uid ($user)");
            } catch (Exception $e) {
                $error = __('username_taken');
            }
        }
    } elseif ($action === 'edit_user') {
        // Bir kullanıcının bilgilerini admin/başka biri düzenliyor
        $uid = (int) post('user_id');
        $user = post('username');
        $fname = post('first_name');
        $lname = post('last_name');
        $timeout = (int) post('timeout', 30);
        $newPw = post('new_password');

        if ($uid > 0 && !empty($user)) {
            try {
                $pdo->prepare("UPDATE users SET username = :u, first_name = :f, last_name = :l, session_timeout = :t WHERE id = :id")
                    ->execute([':u' => $user, ':f' => $fname, ':l' => $lname, ':t' => $timeout, ':id' => $uid]);

                if (!empty($newPw)) {
                    $hash = password_hash($newPw, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET password = :p WHERE id = :id")
                        ->execute([':p' => $hash, ':id' => $uid]);
                }

                if ($uid === (int) $_SESSION['user_id']) {
                    $_SESSION['username'] = $user;
                    $_SESSION['user_name'] = $fname . ' ' . $lname;
                }

                $success = __('user_updated');
                logAction('User Updated', "ID: $uid (@$user)");
            } catch (Exception $e) {
                $error = __('username_taken');
            }
        }
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();

$pageTitle = __('user_management');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>

<div class="row g-4">
    <!-- Profilim -->
    <div class="col-lg-4">
        <div class="panel h-100">
            <div class="panel-header">
                <h5><i class="bi bi-person-badge me-2"></i><?= __('my_profile') ?></h5>
            </div>
            <div class="panel-body">
                <?php
                $me = $pdo->prepare("SELECT * FROM users WHERE id = :id");
                $me->execute([':id' => $_SESSION['user_id']]);
                $myData = $me->fetch();
                ?>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="mb-3">
                        <label class="form-label-dark small"><?= __('username') ?></label>
                        <input type="text" name="username" class="form-control-dark"
                            value="<?= e($myData['username']) ?>" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label-dark small"><?= __('first_name') ?></label>
                            <input type="text" name="first_name" class="form-control-dark"
                                value="<?= e($myData['first_name']) ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label-dark small"><?= __('last_name') ?></label>
                            <input type="text" name="last_name" class="form-control-dark"
                                value="<?= e($myData['last_name']) ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-dark small"><?= __('session_timeout_mins') ?></label>
                        <input type="number" name="timeout" class="form-control-dark"
                            value="<?= e($myData['session_timeout']) ?>" min="1" max="1440">
                        <div class="text-muted" style="font-size:11px;margin-top:4px;"><?= __('auto_logout_help') ?>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label-dark small"><?= __('new_password_help') ?></label>
                        <input type="password" name="new_password" class="form-control-dark" placeholder="******">
                    </div>
                    <button type="submit" class="btn-accent w-100"><?= __('save_changes') ?></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Kullanıcı Listesi -->
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-people me-2"></i><?= __('system_users') ?></h5>
                <button class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-plus-lg me-1"></i><?= __('add_new_user') ?>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table-dark-custom">
                    <thead>
                        <tr>
                            <th><?= __('username') ?></th>
                            <th><?= __('first_name') ?> <?= __('last_name') ?></th>
                            <th><?= __('time') ?></th>
                            <th><?= __('registration_date') ?></th>
                            <th class="text-end"><?= __('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><strong class="text-accent">@<?= e($u['username']) ?></strong></td>
                                <td><?= e($u['first_name'] . ' ' . $u['last_name']) ?></td>
                                <td><?= $u['session_timeout'] ?>     <?= __('min') ?></td>
                                <td class="small text-muted"><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button class="btn-sm-icon btn-edit-user" title="<?= __('edit') ?>" data-bs-toggle="modal"
                                            data-bs-target="#editUserModal" data-id="<?= $u['id'] ?>"
                                            data-username="<?= e($u['username']) ?>" data-fname="<?= e($u['first_name']) ?>"
                                            data-lname="<?= e($u['last_name']) ?>"
                                            data-timeout="<?= $u['session_timeout'] ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" action=""
                                                onsubmit="return confirm('<?= __('confirm_delete') ?>')"
                                                style="display:inline;">
                                                <input type="hidden" name="csrf_token"
                                                    value="<?= e($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn-sm-icon btn-delete" title="<?= __('delete') ?>"><i
                                                        class="bi bi-trash"></i></button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-secondary p-2" style="font-size:10px;"><?= __('you') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Yeni Kullanıcı Modalı -->
<div class="modal fade modal-dark" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= __('add_new_user') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="add_user">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-dark"><?= __('username') ?>*</label>
                        <input type="text" name="username" class="form-control-dark" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-dark"><?= __('password') ?>*</label>
                        <input type="password" name="password" class="form-control-dark" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label-dark"><?= __('first_name') ?></label>
                            <input type="text" name="first_name" class="form-control-dark">
                        </div>
                        <div class="col-6">
                            <label class="form-label-dark"><?= __('last_name') ?></label>
                            <input type="text" name="last_name" class="form-control-dark">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-dark"><?= __('session_timeout_mins') ?></label>
                        <input type="number" name="timeout" class="form-control-dark" value="30" min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal"><?= __('cancel') ?></button>
                    <button type="submit" class="btn-accent"><?= __('create_user') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Düzenle Modalı -->
<div class="modal fade modal-dark" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= __('edit_user') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-dark"><?= __('username') ?></label>
                        <input type="text" name="username" id="edit_username" class="form-control-dark" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label-dark"><?= __('first_name') ?></label>
                            <input type="text" name="first_name" id="edit_first_name" class="form-control-dark">
                        </div>
                        <div class="col-6">
                            <label class="form-label-dark"><?= __('last_name') ?></label>
                            <input type="text" name="last_name" id="edit_last_name" class="form-control-dark">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-dark"><?= __('session_timeout_mins') ?></label>
                        <input type="number" name="timeout" id="edit_timeout" class="form-control-dark" min="1">
                    </div>
                    <div class="mb-0">
                        <label class="form-label-dark"><?= __('new_password_help') ?></label>
                        <input type="password" name="new_password" class="form-control-dark" placeholder="******">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal"><?= __('cancel') ?></button>
                    <button type="submit" class="btn-accent"><?= __('update') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.btn-edit-user').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('edit_user_id').value = this.dataset.id;
            document.getElementById('edit_username').value = this.dataset.username;
            document.getElementById('edit_first_name').value = this.dataset.fname;
            document.getElementById('edit_last_name').value = this.dataset.lname;
            document.getElementById('edit_timeout').value = this.dataset.timeout;
        });
    });
</script>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>
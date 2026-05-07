<?php
require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

// Only ADMINs can access this page
requireAdmin();

$pdo = Database::getInstance();
$error = null;
$success = null;

// ─────────────────────────────────────────────────
// Helper: validate uniqueness of email/phone for a user
// ─────────────────────────────────────────────────
function isFieldUnique(PDO $pdo, string $field, string $value, int $excludeId = 0): bool
{
    if (empty($value)) return true; // empty = skip uniqueness check
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE `$field` = :v AND id != :id");
    $stmt->execute([':v' => $value, ':id' => $excludeId]);
    return (int) $stmt->fetchColumn() === 0;
}

// ─────────────────────────────────────────────────
// POST Actions
// ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], post('csrf_token')))
        die(__('error'));

    $action = post('action');

    // ── ADD USER ──────────────────────────────────
    if ($action === 'add_user') {
        $user    = post('username');
        $pw      = post('password');
        $fname   = post('first_name');
        $lname   = post('last_name');
        $timeout = (int) post('timeout', 30);
        $role    = in_array(post('role'), ['ADMIN', 'USER']) ? post('role') : 'USER';
        $email   = post('email');
        $phone   = post('phone');

        if (empty($user) || empty($pw)) {
            $error = __('username_password_required');
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = __('email_invalid');
        } elseif (!isFieldUnique($pdo, 'email', $email)) {
            $error = __('email_taken');
        } elseif (!isFieldUnique($pdo, 'phone', $phone)) {
            $error = __('phone_taken');
        } else {
            try {
                $hash = password_hash($pw, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    "INSERT INTO users (username, password, role, email, phone, first_name, last_name, session_timeout)
                     VALUES (:u, :p, :r, :e, :ph, :f, :l, :t)"
                );
                $stmt->execute([
                    ':u'  => $user,
                    ':p'  => $hash,
                    ':r'  => $role,
                    ':e'  => $email ?: null,
                    ':ph' => $phone ?: null,
                    ':f'  => $fname,
                    ':l'  => $lname,
                    ':t'  => $timeout,
                ]);
                $success = __('user_added');
                logAction('User Added', "New user created: $user (Role: $role)");
            } catch (Exception $e) {
                $error = __('username_taken');
            }
        }

    // ── DELETE USER ───────────────────────────────
    } elseif ($action === 'delete_user') {
        $uid = (int) post('user_id');
        if ($uid === (int) $_SESSION['user_id']) {
            $error = __('self_delete_error');
        } else {
            $pdo->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $uid]);
            $success = __('user_deleted');
            logAction('User Deleted', "ID: $uid");
        }

    // ── UPDATE OWN PROFILE ────────────────────────
    } elseif ($action === 'update_profile') {
        $uid     = (int) $_SESSION['user_id'];
        $user    = post('username');
        $fname   = post('first_name');
        $lname   = post('last_name');
        $timeout = (int) post('timeout', 30);
        $newPw   = post('new_password');
        $email   = post('email');
        $phone   = post('phone');

        if (empty($user)) {
            $error = __('username_required');
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = __('email_invalid');
        } elseif (!isFieldUnique($pdo, 'email', $email, $uid)) {
            $error = __('email_taken');
        } elseif (!isFieldUnique($pdo, 'phone', $phone, $uid)) {
            $error = __('phone_taken');
        } else {
            try {
                $pdo->prepare(
                    "UPDATE users SET username = :u, first_name = :f, last_name = :l,
                     session_timeout = :t, email = :e, phone = :ph WHERE id = :id"
                )->execute([
                    ':u'  => $user,
                    ':f'  => $fname,
                    ':l'  => $lname,
                    ':t'  => $timeout,
                    ':e'  => $email ?: null,
                    ':ph' => $phone ?: null,
                    ':id' => $uid,
                ]);

                if (!empty($newPw)) {
                    $pdo->prepare("UPDATE users SET password = :p WHERE id = :id")
                        ->execute([':p' => password_hash($newPw, PASSWORD_DEFAULT), ':id' => $uid]);
                }

                $_SESSION['username']     = $user;
                $_SESSION['user_name']    = $fname . ' ' . $lname;
                $_SESSION['user_timeout'] = $timeout;
                $success = __('profile_updated');
                logAction('Profile Updated', "User #$uid ($user)");
            } catch (Exception $e) {
                $error = __('username_taken');
            }
        }

    // ── EDIT ANOTHER USER (Admin) ─────────────────
    } elseif ($action === 'edit_user') {
        $uid     = (int) post('user_id');
        $user    = post('username');
        $fname   = post('first_name');
        $lname   = post('last_name');
        $timeout = (int) post('timeout', 30);
        $newPw   = post('new_password');
        $role    = in_array(post('role'), ['ADMIN', 'USER']) ? post('role') : 'USER';
        $email   = post('email');
        $phone   = post('phone');

        if ($uid > 0 && !empty($user)) {
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = __('email_invalid');
            } elseif (!isFieldUnique($pdo, 'email', $email, $uid)) {
                $error = __('email_taken');
            } elseif (!isFieldUnique($pdo, 'phone', $phone, $uid)) {
                $error = __('phone_taken');
            } else {
                try {
                    $pdo->prepare(
                        "UPDATE users SET username = :u, first_name = :f, last_name = :l,
                         session_timeout = :t, role = :r, email = :e, phone = :ph WHERE id = :id"
                    )->execute([
                        ':u'  => $user,
                        ':f'  => $fname,
                        ':l'  => $lname,
                        ':t'  => $timeout,
                        ':r'  => $role,
                        ':e'  => $email ?: null,
                        ':ph' => $phone ?: null,
                        ':id' => $uid,
                    ]);

                    if (!empty($newPw)) {
                        $pdo->prepare("UPDATE users SET password = :p WHERE id = :id")
                            ->execute([':p' => password_hash($newPw, PASSWORD_DEFAULT), ':id' => $uid]);
                    }

                    if ($uid === (int) $_SESSION['user_id']) {
                        $_SESSION['username']     = $user;
                        $_SESSION['user_name']    = $fname . ' ' . $lname;
                        $_SESSION['user_role']    = $role;
                        $_SESSION['user_timeout'] = $timeout;
                    }

                    $success = __('user_updated');
                    logAction('User Updated', "ID: $uid (@$user, Role: $role)");
                } catch (Exception $e) {
                    $error = __('username_taken');
                }
            }
        }
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();

// Current user's data
$me = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$me->execute([':id' => $_SESSION['user_id']]);
$myData = $me->fetch();

$pageTitle = __('user_management');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>

<div class="row g-4">
    <!-- My Profile -->
    <div class="col-lg-4">
        <div class="panel h-100">
            <div class="panel-header">
                <h5><i class="bi bi-person-badge me-2"></i><?= __('my_profile') ?></h5>
            </div>
            <div class="panel-body">
                <?php if ($error): ?>
                <div class="alert-field-error mb-3" id="profile-error-box" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
                </div>
                <?php endif; ?>
                <?php if ($success): ?>
                <div class="alert-field-success mb-3" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= e($success) ?>
                </div>
                <?php endif; ?>
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
                        <label class="form-label-dark small"><?= __('email') ?></label>
                        <input type="email" name="email" class="form-control-dark"
                            value="<?= e($myData['email'] ?? '') ?>" placeholder="user@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label-dark small"><?= __('phone_label') ?></label>
                        <input type="text" name="phone" class="form-control-dark"
                            value="<?= e($myData['phone'] ?? '') ?>" placeholder="+1 555 000 0000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label-dark small"><?= __('role') ?></label>
                        <input type="text" class="form-control-dark" readonly
                            value="<?= e($myData['role'] ?? 'USER') ?>"
                            style="opacity:0.6;cursor:not-allowed;">
                        <div class="text-muted" style="font-size:11px;margin-top:4px;">Role can only be changed by an admin.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-dark small"><?= __('session_timeout_mins') ?></label>
                        <input type="number" name="timeout" class="form-control-dark"
                            value="<?= e($myData['session_timeout']) ?>" min="1" max="1440">
                        <div class="text-muted" style="font-size:11px;margin-top:4px;"><?= __('auto_logout_help') ?></div>
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

    <!-- System Users List -->
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
                            <th><?= __('email') ?></th>
                            <th><?= __('phone_label') ?></th>
                            <th><?= __('role') ?></th>
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
                                <td class="small text-muted"><?= e($u['email'] ?? '—') ?></td>
                                <td class="small text-muted"><?= e($u['phone'] ?? '—') ?></td>
                                <td>
                                    <?php if (($u['role'] ?? 'USER') === 'ADMIN'): ?>
                                        <span class="badge" style="background:rgba(239,68,68,0.2);color:#f87171;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;">
                                            <i class="bi bi-shield-fill-check me-1"></i>ADMIN
                                        </span>
                                    <?php else: ?>
                                        <span class="badge" style="background:rgba(14,165,233,0.15);color:#38bdf8;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;">
                                            <i class="bi bi-person me-1"></i>USER
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $u['session_timeout'] ?> <?= __('min') ?></td>
                                <td class="small text-muted"><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button class="btn-sm-icon btn-edit-user" title="<?= __('edit') ?>"
                                            data-bs-toggle="modal" data-bs-target="#editUserModal"
                                            data-id="<?= $u['id'] ?>"
                                            data-username="<?= e($u['username']) ?>"
                                            data-fname="<?= e($u['first_name']) ?>"
                                            data-lname="<?= e($u['last_name']) ?>"
                                            data-email="<?= e($u['email'] ?? '') ?>"
                                            data-phone="<?= e($u['phone'] ?? '') ?>"
                                            data-role="<?= e($u['role'] ?? 'USER') ?>"
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
                                                <button type="submit" class="btn-sm-icon btn-delete" title="<?= __('delete') ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
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

<!-- Add User Modal -->
<div class="modal fade modal-dark" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i><?= __('add_new_user') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="addUserForm">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="add_user">
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Username -->
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('username') ?>*</label>
                            <input type="text" name="username" class="form-control-dark" required autocomplete="off">
                        </div>
                        <!-- Password -->
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('password') ?>*</label>
                            <input type="password" name="password" class="form-control-dark" required autocomplete="new-password">
                        </div>
                        <!-- First Name -->
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('first_name') ?></label>
                            <input type="text" name="first_name" class="form-control-dark">
                        </div>
                        <!-- Last Name -->
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('last_name') ?></label>
                            <input type="text" name="last_name" class="form-control-dark">
                        </div>
                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('email') ?></label>
                            <input type="email" name="email" class="form-control-dark" placeholder="user@example.com">
                        </div>
                        <!-- Phone -->
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('phone_label') ?></label>
                            <input type="text" name="phone" class="form-control-dark" placeholder="+1 555 000 0000">
                        </div>
                        <!-- Role -->
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('role') ?>*</label>
                            <select name="role" class="form-control-dark" required>
                                <option value="USER">👤 USER — Limited Access</option>
                                <option value="ADMIN">🛡️ ADMIN — Full Access</option>
                            </select>
                        </div>
                        <!-- Session Timeout -->
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('session_timeout_mins') ?></label>
                            <input type="number" name="timeout" class="form-control-dark" value="30" min="1" max="1440">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                    <button type="submit" class="btn-accent" id="addUserBtn"><i class="bi bi-person-plus me-1"></i><?= __('create_user') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade modal-dark" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i><?= __('edit_user') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="editUserForm">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Username -->
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('username') ?></label>
                            <input type="text" name="username" id="edit_username" class="form-control-dark" required>
                        </div>
                        <!-- New Password -->
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('new_password_help') ?></label>
                            <input type="password" name="new_password" class="form-control-dark" placeholder="******" autocomplete="new-password">
                        </div>
                        <!-- First Name -->
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('first_name') ?></label>
                            <input type="text" name="first_name" id="edit_first_name" class="form-control-dark">
                        </div>
                        <!-- Last Name -->
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('last_name') ?></label>
                            <input type="text" name="last_name" id="edit_last_name" class="form-control-dark">
                        </div>
                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('email') ?></label>
                            <input type="email" name="email" id="edit_email" class="form-control-dark" placeholder="user@example.com">
                        </div>
                        <!-- Phone -->
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('phone_label') ?></label>
                            <input type="text" name="phone" id="edit_phone" class="form-control-dark" placeholder="+1 555 000 0000">
                        </div>
                        <!-- Role -->
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('role') ?>*</label>
                            <select name="role" id="edit_role" class="form-control-dark" required>
                                <option value="USER">👤 USER — Limited Access</option>
                                <option value="ADMIN">🛡️ ADMIN — Full Access</option>
                            </select>
                        </div>
                        <!-- Session Timeout -->
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('session_timeout_mins') ?></label>
                            <input type="number" name="timeout" id="edit_timeout" class="form-control-dark" min="1" max="1440">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                    <button type="submit" class="btn-accent" id="editUserBtn"><i class="bi bi-check-lg me-1"></i><?= __('update') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.alert-field-error{
    background:rgba(239,68,68,0.12);
    border:1px solid rgba(239,68,68,0.45);
    color:#fca5a5;
    border-radius:10px;
    padding:12px 16px;
    font-size:13px;
    animation:fadeInDown .25s ease;
}
.alert-field-success{
    background:rgba(34,197,94,0.12);
    border:1px solid rgba(34,197,94,0.4);
    color:#86efac;
    border-radius:10px;
    padding:12px 16px;
    font-size:13px;
    animation:fadeInDown .25s ease;
}
.field-error-msg{
    color:#fca5a5;
    font-size:11.5px;
    margin-top:5px;
    display:flex;
    align-items:center;
    gap:5px;
    animation:fadeInDown .2s ease;
}
.input-field-error{
    border-color:#ef4444!important;
    box-shadow:0 0 0 3px rgba(239,68,68,0.18)!important;
}
@keyframes fadeInDown{
    from{opacity:0;transform:translateY(-6px)}
    to  {opacity:1;transform:translateY(0)}
}
</style>

<script>
// ── Populate Edit Modal ───────────────────────────────────────────────────────
document.querySelectorAll('.btn-edit-user').forEach(btn => {
    btn.addEventListener('click', function () {
        clearModalErrors('editUserModal');
        document.getElementById('edit_user_id').value    = this.dataset.id;
        document.getElementById('edit_username').value   = this.dataset.username;
        document.getElementById('edit_first_name').value = this.dataset.fname;
        document.getElementById('edit_last_name').value  = this.dataset.lname;
        document.getElementById('edit_email').value      = this.dataset.email;
        document.getElementById('edit_phone').value      = this.dataset.phone;
        document.getElementById('edit_timeout').value    = this.dataset.timeout;
        const rs = document.getElementById('edit_role');
        for (let o of rs.options) o.selected = (o.value === this.dataset.role);
    });
});

// ── Clear all validation state inside a modal ─────────────────────────────────
function clearModalErrors(modalId) {
    const m = document.getElementById(modalId);
    m.querySelectorAll('.input-field-error').forEach(el => el.classList.remove('input-field-error'));
    m.querySelectorAll('.field-error-msg').forEach(el => el.remove());
    const banner = m.querySelector('.modal-error-banner');
    if (banner) banner.remove();
}

// ── Show inline error under a specific field ──────────────────────────────────
function showFieldError(modalId, fieldName, msg) {
    const m   = document.getElementById(modalId);
    const inp = m.querySelector(`[name="${fieldName}"]`);
    if (!inp) { showModalBanner(modalId, msg); return; }
    inp.classList.add('input-field-error');
    // Remove any existing msg for this field
    inp.parentNode.querySelectorAll('.field-error-msg').forEach(e => e.remove());
    const div = document.createElement('div');
    div.className = 'field-error-msg';
    div.innerHTML = `<i class="bi bi-exclamation-circle"></i>${msg}`;
    inp.insertAdjacentElement('afterend', div);
    inp.scrollIntoView({behavior:'smooth', block:'nearest'});
}

// ── Show banner at top of modal body ─────────────────────────────────────────
function showModalBanner(modalId, msg) {
    const m = document.getElementById(modalId);
    let banner = m.querySelector('.modal-error-banner');
    if (!banner) {
        banner = document.createElement('div');
        banner.className = 'modal-error-banner alert-field-error mb-3';
        const body = m.querySelector('.modal-body');
        body.prepend(banner);
    }
    banner.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i>${msg}`;
}

// ── Show success banner inside modal, then reload ────────────────────────────
function showModalSuccess(modalId, msg) {
    const m = document.getElementById(modalId);
    let banner = m.querySelector('.modal-error-banner');
    if (!banner) {
        banner = document.createElement('div');
        banner.className = 'modal-error-banner';
        m.querySelector('.modal-body').prepend(banner);
    }
    banner.className = 'modal-error-banner alert-field-success mb-3';
    banner.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i>${msg}`;
    setTimeout(() => location.reload(), 900);
}

// ── AJAX form submit factory ──────────────────────────────────────────────────
function bindAjaxForm(formId, modalId, submitBtnId) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        clearModalErrors(modalId);
        const btn = document.getElementById(submitBtnId);
        const origHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>...';
        try {
            const res  = await fetch('user_action.php', {
                method  : 'POST',
                body    : new FormData(form),
                headers : {'X-Requested-With': 'XMLHttpRequest'}
            });
            const data = await res.json();
            if (data.success) {
                showModalSuccess(modalId, data.message);
            } else if (data.error && data.error !== 'general') {
                showFieldError(modalId, data.error, data.message);
            } else {
                showModalBanner(modalId, data.message);
            }
        } catch(err) {
            showModalBanner(modalId, 'Network error. Please try again.');
        } finally {
            btn.disabled  = false;
            btn.innerHTML = origHtml;
        }
    });
}

bindAjaxForm('addUserForm',  'addUserModal',  'addUserBtn');
bindAjaxForm('editUserForm', 'editUserModal', 'editUserBtn');

// Clear errors when modals open
['addUserModal','editUserModal'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('show.bs.modal', () => clearModalErrors(id));
});
</script>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>
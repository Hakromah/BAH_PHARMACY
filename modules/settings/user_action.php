<?php
/**
 * User Action — AJAX/JSON endpoint
 * Handles add_user, edit_user, delete_user.
 * Always returns JSON: { success, error, field?, message? }
 */
require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

requireAdmin();

header('Content-Type: application/json; charset=utf-8');

$pdo    = Database::getInstance();
$action = post('action');

// ── CSRF ──────────────────────────────────────────────────────────────────────
if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
    echo json_encode(['success' => false, 'error' => 'general', 'message' => __('error')]);
    exit;
}

// ── Helper: uniqueness check ──────────────────────────────────────────────────
function fieldUnique(PDO $pdo, string $col, string $val, int $excludeId = 0): bool
{
    if ($val === '') return true;
    $s = $pdo->prepare("SELECT COUNT(*) FROM users WHERE `$col` = :v AND id != :id");
    $s->execute([':v' => $val, ':id' => $excludeId]);
    return (int)$s->fetchColumn() === 0;
}

// ── Helper: build structured validation error ─────────────────────────────────
function validErr(string $field, string $msg): string
{
    return json_encode(['success' => false, 'error' => $field, 'message' => $msg]);
}

// =============================================================================
// ADD USER
// =============================================================================
if ($action === 'add_user') {
    $username = trim(post('username'));
    $password = post('password');
    $fname    = trim(post('first_name'));
    $lname    = trim(post('last_name'));
    $email    = trim(post('email'));
    $phone    = trim(post('phone'));
    $role     = in_array(post('role'), ['ADMIN', 'USER']) ? post('role') : 'USER';
    $timeout  = max(1, (int) post('timeout', 30));

    if ($username === '')
        exit(validErr('username', __('username_required')));
    if ($password === '')
        exit(validErr('password', __('username_password_required')));
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
        exit(validErr('email', __('email_invalid')));
    if (!fieldUnique($pdo, 'email', $email))
        exit(validErr('email', __('email_taken')));
    if (!fieldUnique($pdo, 'phone', $phone))
        exit(validErr('phone', __('phone_taken')));

    try {
        $pdo->prepare(
            "INSERT INTO users (username, password, role, email, phone, first_name, last_name, session_timeout)
             VALUES (:u, :p, :r, :e, :ph, :f, :l, :t)"
        )->execute([
            ':u'  => $username,
            ':p'  => password_hash($password, PASSWORD_DEFAULT),
            ':r'  => $role,
            ':e'  => $email  ?: null,
            ':ph' => $phone  ?: null,
            ':f'  => $fname,
            ':l'  => $lname,
            ':t'  => $timeout,
        ]);
        logAction('User Added', "New user: $username (Role: $role)");
        echo json_encode(['success' => true, 'message' => __('user_added')]);
    } catch (Exception $e) {
        exit(validErr('username', __('username_taken')));
    }
    exit;
}

// =============================================================================
// EDIT USER
// =============================================================================
if ($action === 'edit_user') {
    $uid      = (int) post('user_id');
    $username = trim(post('username'));
    $fname    = trim(post('first_name'));
    $lname    = trim(post('last_name'));
    $email    = trim(post('email'));
    $phone    = trim(post('phone'));
    $role     = in_array(post('role'), ['ADMIN', 'USER']) ? post('role') : 'USER';
    $timeout  = max(1, (int) post('timeout', 30));
    $newPw    = post('new_password');

    if ($uid <= 0 || $username === '')
        exit(validErr('username', __('username_required')));
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
        exit(validErr('email', __('email_invalid')));
    if (!fieldUnique($pdo, 'email', $email, $uid))
        exit(validErr('email', __('email_taken')));
    if (!fieldUnique($pdo, 'phone', $phone, $uid))
        exit(validErr('phone', __('phone_taken')));

    try {
        $pdo->prepare(
            "UPDATE users SET username=:u, first_name=:f, last_name=:l,
             session_timeout=:t, role=:r, email=:e, phone=:ph WHERE id=:id"
        )->execute([
            ':u'  => $username,
            ':f'  => $fname,
            ':l'  => $lname,
            ':t'  => $timeout,
            ':r'  => $role,
            ':e'  => $email  ?: null,
            ':ph' => $phone  ?: null,
            ':id' => $uid,
        ]);

        if (!empty($newPw)) {
            $pdo->prepare("UPDATE users SET password=:p WHERE id=:id")
                ->execute([':p' => password_hash($newPw, PASSWORD_DEFAULT), ':id' => $uid]);
        }

        // Sync session if editing own account
        if ($uid === (int)$_SESSION['user_id']) {
            $_SESSION['username']     = $username;
            $_SESSION['user_name']    = trim("$fname $lname");
            $_SESSION['user_role']    = $role;
            $_SESSION['user_timeout'] = $timeout;
        }

        logAction('User Updated', "ID: $uid (@$username, Role: $role)");
        echo json_encode(['success' => true, 'message' => __('user_updated')]);
    } catch (Exception $e) {
        exit(validErr('username', __('username_taken')));
    }
    exit;
}

// =============================================================================
// DELETE USER
// =============================================================================
if ($action === 'delete_user') {
    $uid = (int) post('user_id');
    if ($uid === (int)$_SESSION['user_id']) {
        exit(json_encode(['success' => false, 'error' => 'general', 'message' => __('self_delete_error')]));
    }
    $pdo->prepare("DELETE FROM users WHERE id=:id")->execute([':id' => $uid]);
    logAction('User Deleted', "ID: $uid");
    echo json_encode(['success' => true, 'message' => __('user_deleted')]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'general', 'message' => __('error')]);

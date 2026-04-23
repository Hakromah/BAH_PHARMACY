<?php
require_once __DIR__ . '/../core/bootstrap.php';

// Zaten giriş yapılmışsa ana sayfaya yönlendir
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post('username');
    $password = post('password');

    if (empty($username) || empty($password)) {
        $error = __('username_password_required');
    } else {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_timeout'] = $user['session_timeout'] ?: 30;
            $_SESSION['last_activity'] = time();

            logAction('Login Successful', "User {$username} logged in.");
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        } else {
            $error = __('invalid_login_error');
            logAction('Failed Login', "Username: {$username}");
        }
    }
}
$pageTitle = __('system_login');
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLang() ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= e($pageTitle) ?> | BAH Pharmacy
    </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f172a;
            --panel: #1e293b;
            --accent: #0ea5e9;
            --text: #f8fafc;
            --text-muted: #94a3b8;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
        }

        .login-card {
            background: var(--panel);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-box {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-box i {
            font-size: 48px;
            color: var(--accent);
            filter: drop-shadow(0 0 15px rgba(14, 165, 233, 0.4));
        }

        .form-control-dark {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            padding: 12px 16px;
            border-radius: 12px;
            transition: all 0.2s;
        }

        .form-control-dark:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15);
            color: white;
        }

        .btn-login {
            background: var(--accent);
            border: none;
            color: white;
            font-weight: 600;
            padding: 14px;
            border-radius: 12px;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s;
        }

        .btn-login:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(14, 165, 233, 0.3);
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="logo-box">
            <i class="bi bi-capsule-pill"></i>
            <h4 class="mt-3 fw-bold">BAH Pharmacy</h4>
            <p class="text-muted small"><?= __('login_subtitle') ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2" style="font-size:13px;border-radius:10px;">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if (get('timeout')): ?>
            <div class="alert alert-warning py-2" style="font-size:13px;border-radius:10px;">
                <i class="bi bi-clock-history me-2"></i><?= __('session_timeout_message') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label class="form-label small text-muted"><?= __('username') ?></label>
                <input type="text" name="username" class="form-control-dark w-100" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label small text-muted"><?= __('password') ?></label>
                <input type="password" name="password" class="form-control-dark w-100" required>
            </div>
            <button type="submit" class="btn-login"><?= __('login_button') ?></button>
        </form>

        <div class="mt-4 text-center">
            <p class="text-muted small mb-0">&copy; 2026 BAH Software</p>
        </div>
    </div>

</body>

</html>
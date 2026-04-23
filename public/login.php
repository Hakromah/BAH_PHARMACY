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
    <title><?= e($pageTitle) ?> | BAH Pharmacy</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #0f172a;
            color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .login-card {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-box {
            text-align: center;
            margin-bottom: 28px;
        }

        .logo-icon {
            font-size: 48px;
            line-height: 1;
            margin-bottom: 12px;
        }

        .logo-box h4 {
            font-size: 20px;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 6px;
        }

        .logo-box p {
            font-size: 13px;
            color: #94a3b8;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 7px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-control {
            width: 100%;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #f8fafc;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.25);
        }

        .btn-login {
            background: #0ea5e9;
            border: none;
            color: white;
            font-weight: 600;
            font-size: 15px;
            padding: 14px;
            border-radius: 12px;
            width: 100%;
            margin-top: 8px;
            cursor: pointer;
            transition: filter 0.2s, transform 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }

        .btn-login:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(14, 165, 233, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fcd34d;
        }

        .footer-text {
            text-align: center;
            font-size: 12px;
            color: #475569;
            margin-top: 24px;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="logo-box">
            <div class="logo-icon">💊</div>
            <h4>BAH Pharmacy</h4>
            <p><?= __('login_subtitle') ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                &#9888; <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if (get('timeout')): ?>
            <div class="alert alert-warning">
                &#9200; <?= __('session_timeout_message') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label class="form-label" for="username"><?= __('username') ?></label>
                <input type="text" id="username" name="username" class="form-control" required autofocus
                    autocomplete="username">
            </div>
            <div class="form-group">
                <label class="form-label" for="password"><?= __('password') ?></label>
                <input type="password" id="password" name="password" class="form-control" required
                    autocomplete="current-password">
            </div>
            <button type="submit" class="btn-login"><?= __('login_button') ?></button>
        </form>

        <div class="footer-text">
            &copy; 2026 BAH Software
        </div>
    </div>
</body>

</html>
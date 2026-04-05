<?php
session_start();

// Prevent browser caching — must re-validate with server on every visit
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

require_once 'config.php';

if (!isset($_SESSION['frontend_user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['frontend_user'];
$userId = (int) ($user['id'] ?? 0);
$message = '';
$messageType = '';
$welcomeBackground = 'img/contactus.jpg';
$homePageResult = mysqli_query($conn, "SELECT bannerimage FROM home_page WHERE 1 LIMIT 1");
$homePage = $homePageResult ? mysqli_fetch_assoc($homePageResult) : null;

if (!empty($homePage['bannerimage'])) {
    $welcomeBackground = $homePage['bannerimage'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $message = 'Please fill in all password fields.';
        $messageType = 'danger';
    } elseif (strlen($newPassword) < 6) {
        $message = 'New password must be at least 6 characters long.';
        $messageType = 'danger';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'New password and confirm password do not match.';
        $messageType = 'danger';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT password_hash FROM frontend_users WHERE id = ? LIMIT 1');

        if (!$stmt) {
            $message = 'We could not verify your account right now.';
            $messageType = 'danger';
        } else {
            mysqli_stmt_bind_param($stmt, 'i', $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $account = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);

            if (!$account) {
                unset($_SESSION['frontend_user']);
                header('Location: login.php');
                exit;
            }

            if (!password_verify($currentPassword, $account['password_hash'])) {
                $message = 'Current password is incorrect.';
                $messageType = 'danger';
            } elseif (password_verify($newPassword, $account['password_hash'])) {
                $message = 'Please choose a different password from your current one.';
                $messageType = 'danger';
            } else {
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = mysqli_prepare($conn, 'UPDATE frontend_users SET password_hash = ? WHERE id = ?');

                if (!$updateStmt) {
                    $message = 'We could not update your password right now.';
                    $messageType = 'danger';
                } else {
                    mysqli_stmt_bind_param($updateStmt, 'si', $newPasswordHash, $userId);

                    if (mysqli_stmt_execute($updateStmt)) {
                        $message = 'Password changed successfully.';
                        $messageType = 'success';
                    } else {
                        $message = 'We could not save your new password right now.';
                        $messageType = 'danger';
                    }

                    mysqli_stmt_close($updateStmt);
                }
            }
        }
    }
}

include 'header2.php';
?>

<style>
    .password-page {
        position: relative;
        min-height: calc(100vh - 84px);
        padding: 48px 0 64px;
        overflow: hidden;
        isolation: isolate;
    }

    .password-page::before {
        content: "";
        position: absolute;
        inset: -24px;
        background-image: url('<?php echo htmlspecialchars($welcomeBackground, ENT_QUOTES, 'UTF-8'); ?>');
        background-size: cover;
        background-position: center;
        filter: blur(5px);
        transform: scale(1.08);
        z-index: -2;
    }

    .password-page::after {
        content: "";
        position: absolute;
        inset: 0;
        background:
            linear-gradient(120deg, rgba(10, 26, 46, 0.76), rgba(13, 110, 253, 0.34)),
            radial-gradient(circle at top left, rgba(255, 255, 255, 0.10), transparent 28%);
        z-index: -1;
    }

    .password-page .container {
        display: flex;
        align-items: stretch;
    }

    .password-card {
        max-width: 680px;
        margin: 0 auto;
        padding: 34px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(10px);
        box-shadow: 0 24px 60px rgba(15, 39, 66, 0.22);
    }

    .password-title {
        margin-bottom: 8px;
        color: #15314b;
        font-size: clamp(2rem, 4vw, 2.5rem);
        font-weight: 800;
    }

    .password-subtitle {
        margin-bottom: 26px;
        color: #5f7389;
        font-size: 16px;
    }

    .password-alert {
        margin-bottom: 22px;
        padding: 14px 16px;
        border-radius: 16px;
        font-weight: 600;
    }

    .password-alert-success {
        background: #eaf8ef;
        color: #18794e;
        border: 1px solid #b9e3c8;
    }

    .password-alert-danger {
        background: #fff0f0;
        color: #b42318;
        border: 1px solid #f5c2c7;
    }

    .password-form-group {
        margin-bottom: 18px;
    }

    .password-form-label {
        display: block;
        margin-bottom: 8px;
        color: #17324d;
        font-weight: 700;
    }

    .password-form-control {
        width: 100%;
        min-height: 52px;
        padding: 0 16px;
        border: 1px solid #d7e3ef;
        border-radius: 16px;
        background: #f9fbfe;
        color: #17324d;
        outline: none;
        transition: 0.2s ease;
    }

    .password-form-control:focus {
        border-color: #0d6efd;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.12);
    }

    .password-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 28px;
    }

    .password-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 48px;
        padding: 0 22px;
        border: 0;
        border-radius: 999px;
        text-decoration: none;
        font-weight: 700;
        transition: 0.2s ease;
    }

    .password-btn-primary {
        background: #0d6efd;
        color: #fff;
    }

    .password-btn-primary:hover {
        background: #0b5ed7;
        color: #fff;
    }

    .password-btn-secondary {
        background: #edf4fb;
        color: #17324d;
    }

    .password-btn-secondary:hover {
        background: #dbe9f7;
        color: #17324d;
    }

    @media (max-width: 767.98px) {
        .password-page {
            padding: 28px 0 48px;
        }

        .password-card {
            padding: 24px;
        }

        .password-btn {
            width: 100%;
        }
    }
</style>

<main class="password-page">
    <div class="container">
        <section class="password-card">
            <h1 class="password-title">Change Password</h1>
            <p class="password-subtitle">Keep your account secure by setting a strong password.</p>

            <?php if ($message !== ''): ?>
                <div class="password-alert password-alert-<?php echo htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="password-form-group">
                    <label for="current_password" class="password-form-label">Current Password</label>
                    <input type="password" name="current_password" id="current_password" class="password-form-control" required>
                </div>

                <div class="password-form-group">
                    <label for="new_password" class="password-form-label">New Password</label>
                    <input type="password" name="new_password" id="new_password" class="password-form-control" required>
                </div>

                <div class="password-form-group">
                    <label for="confirm_password" class="password-form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="password-form-control" required>
                </div>

                <div class="password-actions">
                    <button type="submit" class="password-btn password-btn-primary">Update Password</button>
                    <a href="welcome.php" class="password-btn password-btn-secondary">Back </a>
                </div>
            </form>
        </section>
    </div>
</main>

<?php include 'footer.php'; ?>

<?php
session_start();
require_once 'config.php';
require_once 'lib/email_helper.php';

function mbj_ensure_frontend_users_table(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS frontend_users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        gender ENUM('Male', 'Female', 'Other') NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!mysqli_query($conn, $sql)) {
        throw new RuntimeException('Unable to prepare user accounts table.');
    }

    $passwordHashColumn = mysqli_query($conn, "SHOW COLUMNS FROM frontend_users LIKE 'password_hash'");
    if (!$passwordHashColumn) {
        throw new RuntimeException('Unable to inspect user accounts table.');
    }

    if (mysqli_num_rows($passwordHashColumn) === 0) {
        $legacyPasswordColumn = mysqli_query($conn, "SHOW COLUMNS FROM frontend_users LIKE 'password'");

        if (!$legacyPasswordColumn) {
            throw new RuntimeException('Unable to inspect legacy password column.');
        }

        if (mysqli_num_rows($legacyPasswordColumn) > 0) {
            if (!mysqli_query($conn, "ALTER TABLE frontend_users CHANGE `password` `password_hash` VARCHAR(255) NOT NULL")) {
                throw new RuntimeException('Unable to upgrade user accounts table.');
            }
        } else {
            if (!mysqli_query($conn, "ALTER TABLE frontend_users ADD COLUMN password_hash VARCHAR(255) NOT NULL AFTER gender")) {
                throw new RuntimeException('Unable to add password column to user accounts table.');
            }
        }
    }
}

function mbj_redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

$message = '';
$message_type = '';
$active_tab = 'login';
$login_data = [
    'email' => '',
];
$register_data = [
    'name' => '',
    'email' => '',
    'gender' => '',
];
$allowed_genders = ['Male', 'Female', 'Other'];
$authBackground = 'img/contactus.jpg';
$homePageResult = mysqli_query($conn, "SELECT bannerimage FROM home_page WHERE 1 LIMIT 1");
$homePage = $homePageResult ? mysqli_fetch_assoc($homePageResult) : null;

if (!empty($homePage['bannerimage'])) {
    $authBackground = $homePage['bannerimage'];
}

try {
    mbj_ensure_frontend_users_table($conn);
} catch (Throwable $throwable) {
    $message = 'We could not load the login system right now. Please try again shortly.';
    $message_type = 'danger';
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['frontend_user']);
    mbj_redirect('login.php?logged_out=1');
}

if (isset($_GET['logged_out']) && $_GET['logged_out'] === '1') {
    $message = 'You have been logged out successfully.';
    $message_type = 'success';
}

$logged_in_user = $_SESSION['frontend_user'] ?? null;

if ($logged_in_user && $_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['logged_out'])) {
    mbj_redirect('welcome.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $message_type !== 'danger') {
    $form_type = $_POST['form_type'] ?? '';

    if ($form_type === 'login') {
        $active_tab = 'login';
        $login_data['email'] = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($login_data['email'] === '' || $password === '') {
            $message = 'Please enter your email and password.';
            $message_type = 'danger';
        } elseif (!filter_var($login_data['email'], FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $message_type = 'danger';
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id, name, email, gender, password_hash FROM frontend_users WHERE email = ? LIMIT 1");

            if (!$stmt) {
                $message = 'We could not process your login right now.';
                $message_type = 'danger';
            } else {
                mysqli_stmt_bind_param($stmt, 's', $login_data['email']);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = $result ? mysqli_fetch_assoc($result) : null;
                mysqli_stmt_close($stmt);

                if (!$user || !password_verify($password, $user['password_hash'])) {
                    $message = 'Invalid email or password.';
                    $message_type = 'danger';
                } else {
                    $_SESSION['frontend_user'] = [
                        'id' => (int) $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'gender' => $user['gender'],
                    ];

                    mbj_redirect('welcome.php');
                }
            }
        }
    } elseif ($form_type === 'register') {
        $active_tab = 'register';
        $register_data['name'] = trim((string) ($_POST['name'] ?? ''));
        $register_data['email'] = trim((string) ($_POST['email'] ?? ''));
        $register_data['gender'] = trim((string) ($_POST['gender'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm_password = (string) ($_POST['confirm_password'] ?? '');

        if (
            $register_data['name'] === '' ||
            $register_data['email'] === '' ||
            $register_data['gender'] === '' ||
            $password === '' ||
            $confirm_password === ''
        ) {
            $message = 'Please fill in all registration fields.';
            $message_type = 'danger';
        } elseif (!filter_var($register_data['email'], FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $message_type = 'danger';
        } elseif (!in_array($register_data['gender'], $allowed_genders, true)) {
            $message = 'Please select a valid gender.';
            $message_type = 'danger';
        } elseif (strlen($password) < 6) {
            $message = 'Password must be at least 6 characters long.';
            $message_type = 'danger';
        } elseif ($password !== $confirm_password) {
            $message = 'Password and confirm password do not match.';
            $message_type = 'danger';
        } else {
            $check_stmt = mysqli_prepare($conn, "SELECT id FROM frontend_users WHERE email = ? LIMIT 1");

            if (!$check_stmt) {
                $message = 'We could not validate your registration right now.';
                $message_type = 'danger';
            } else {
                mysqli_stmt_bind_param($check_stmt, 's', $register_data['email']);
                mysqli_stmt_execute($check_stmt);
                $existing_user = mysqli_stmt_get_result($check_stmt);
                $email_exists = $existing_user && mysqli_fetch_assoc($existing_user);
                mysqli_stmt_close($check_stmt);

                if ($email_exists) {
                    $message = 'An account with this email already exists.';
                    $message_type = 'danger';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $insert_stmt = mysqli_prepare($conn, "INSERT INTO frontend_users (name, email, gender, password_hash) VALUES (?, ?, ?, ?)");

                    if (!$insert_stmt) {
                        $message = 'We could not create your account right now.';
                        $message_type = 'danger';
                    } else {
                        mysqli_stmt_bind_param(
                            $insert_stmt,
                            'ssss',
                            $register_data['name'],
                            $register_data['email'],
                            $register_data['gender'],
                            $hashed_password
                        );

                        if (mysqli_stmt_execute($insert_stmt)) {
                            mysqli_stmt_close($insert_stmt);

                            try {
                                mbj_send_registration_email($conn, [
                                    'name' => $register_data['name'],
                                    'email' => $register_data['email'],
                                    'gender' => $register_data['gender'],
                                ], $password);

                                $message = 'Registration successful. We have sent your email and password to your email address.';
                            } catch (Throwable $throwable) {
                                $message = 'Registration successful, but we could not send the welcome email right now. Please check SMTP settings in admin.';
                            }

                            $message_type = 'success';
                            $active_tab = 'login';
                            $login_data['email'] = $register_data['email'];
                            $register_data = [
                                'name' => '',
                                'email' => '',
                                'gender' => '',
                            ];
                        } else {
                            mysqli_stmt_close($insert_stmt);
                            $message = 'We could not save your account right now.';
                            $message_type = 'danger';
                        }
                    }
                }
            }
        }
    }
}

include 'header.php';
?>

<style>
    .auth-hero {
        min-height: 100vh;
        position: relative;
        overflow: hidden;
        isolation: isolate;
    }

    .auth-hero::before {
        content: "";
        position: absolute;
        inset: -24px;
        background-image: url('<?php echo htmlspecialchars($authBackground, ENT_QUOTES, 'UTF-8'); ?>');
        background-size: cover;
        background-position: center;
        filter: blur(5px);
        transform: scale(1.08);
        z-index: -3;
    }

    .auth-hero::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(19, 41, 75, 0.88), rgba(255, 111, 97, 0.72));
        z-index: -2;
    }

    .auth-shell {
        position: relative;
        z-index: 1;
    }

    .auth-shell::before,
    .auth-shell::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        filter: blur(10px);
        opacity: 0.28;
        pointer-events: none;
    }

    .auth-shell::before {
        width: 320px;
        height: 320px;
        background: #ffffff;
        top: 12%;
        left: -90px;
    }

    .auth-shell::after {
        width: 260px;
        height: 260px;
        background: #ffd369;
        right: -70px;
        bottom: 8%;
    }

    .auth-popup {
        background: rgba(255, 255, 255, 0.94);
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 25px 70px rgba(10, 24, 46, 0.24);
        backdrop-filter: blur(12px);
    }

    .auth-aside {
        background: linear-gradient(160deg, #0f3d62 0%, #1b6ca8 55%, #ff7f50 100%);
        color: #fff;
        padding: 48px 36px;
        height: 100%;
    }

    .auth-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.16);
        font-size: 13px;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .auth-title {
        font-size: clamp(2rem, 3vw, 2.8rem);
        line-height: 1.15;
        margin: 24px 0 14px;
        color: #fff;
    }

    .auth-copy {
        color: rgba(255, 255, 255, 0.85);
        font-size: 15px;
        margin-bottom: 28px;
    }

    .auth-points {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .auth-points li {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
        font-size: 15px;
    }

    .auth-points i {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.16);
    }

    .auth-content {
        padding: 40px 34px;
    }

    .auth-switcher {
        background: #eef3f8;
        border-radius: 999px;
        padding: 6px;
        display: inline-flex;
        gap: 6px;
        margin-bottom: 24px;
    }

    .auth-switch {
        border: 0;
        background: transparent;
        color: #4c6075;
        padding: 10px 20px;
        border-radius: 999px;
        font-weight: 700;
        transition: 0.25s ease;
    }

    .auth-switch.active {
        background: linear-gradient(135deg, #0d6efd, #ff7f50);
        color: #fff;
        box-shadow: 0 10px 25px rgba(13, 110, 253, 0.22);
    }

    .auth-panel {
        display: none;
    }

    .auth-panel.active {
        display: block;
    }

    .auth-content h2 {
        color: #13294b;
        font-size: 30px;
        margin-bottom: 8px;
    }

    .auth-subtitle {
        color: #66788a;
        margin-bottom: 24px;
    }

    .auth-form .form-control,
    .auth-form .form-select {
        min-height: 54px;
        border-radius: 14px;
        border: 1px solid #dbe4ee;
        padding-left: 16px;
        color: #233142;
        box-shadow: none;
    }

    .auth-form .form-control:focus,
    .auth-form .form-select:focus {
        border-color: #1b6ca8;
        box-shadow: 0 0 0 0.2rem rgba(27, 108, 168, 0.12);
    }

    .auth-btn {
        min-height: 54px;
        border-radius: 14px;
        font-weight: 700;
        border: 0;
        background: linear-gradient(135deg, #0d6efd, #ff7f50);
        box-shadow: 0 15px 30px rgba(13, 110, 253, 0.2);
    }

    .auth-btn:hover {
        opacity: 0.95;
    }

    .auth-note {
        color: #6f7f90;
        font-size: 14px;
        margin-top: 16px;
        text-align: center;
    }

    .auth-welcome {
        background: #fff;
        border-radius: 24px;
        padding: 34px;
        box-shadow: 0 25px 70px rgba(10, 24, 46, 0.2);
    }

    .auth-welcome-card {
        border-radius: 22px;
        background: linear-gradient(145deg, #13294b, #1b6ca8);
        color: #fff;
        padding: 28px;
    }

    .auth-detail {
        padding: 14px 18px;
        background: #f5f8fc;
        border-radius: 16px;
        color: #435466;
    }

    @media (max-width: 991.98px) {
        .auth-aside {
            padding: 36px 28px;
        }

        .auth-content {
            padding: 32px 24px;
        }
    }
</style>

        <div class="container-fluid auth-hero py-5 mb-5">
            <div class="container py-5 auth-shell">
                <div class="row justify-content-center align-items-center min-vh-100 py-lg-5">
                    <div class="col-xl-10">
                        <?php if ($logged_in_user): ?>
                        <div class="auth-welcome">
                            <?php if ($message !== ''): ?>
                            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> mb-4">
                                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <?php endif; ?>
                            <div class="row g-4 align-items-center">
                                <div class="col-lg-7">
                                    <div class="auth-welcome-card">
                                        <span class="auth-badge"><i class="fa fa-check-circle"></i> Logged In</span>
                                        <h1 class="auth-title mb-3">Hello, <?php echo htmlspecialchars($logged_in_user['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                                        <p class="auth-copy mb-0">Your traveler account is active. You can keep this page as your account welcome screen or connect it to bookings, packages, and profile tools next.</p>
                                    </div>
                                </div>
                                <div class="col-lg-5">
                                    <div class="d-grid gap-3">
                                        <div class="auth-detail">
                                            <strong>Name:</strong><br>
                                            <?php echo htmlspecialchars($logged_in_user['name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                        <div class="auth-detail">
                                            <strong>Email:</strong><br>
                                            <?php echo htmlspecialchars($logged_in_user['email'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                        <div class="auth-detail">
                                            <strong>Gender:</strong><br>
                                            <?php echo htmlspecialchars($logged_in_user['gender'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                        <a href="login.php?action=logout" class="btn btn-danger btn-lg rounded-pill">Logout</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="auth-popup">
                            <div class="row g-0">
                                <div class="col-lg-5">
                                    <div class="auth-aside">
                                        <span class="auth-badge"><i class="fa fa-map-marked-alt"></i> Travel Access</span>
                                        <h1 class="auth-title">Login and register Here.</h1>
                                        
                                        <ul class="auth-points">
                                            <li><i class="fa fa-user"></i> Register with name, email and gender</li>
                                            <li><i class="fa fa-lock"></i> Secure passwords stored with hashing</li>
                                            <li><i class="fa fa-sign-in-alt"></i> Quick switch between login and register</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="auth-content">
                                        <?php if ($message !== ''): ?>
                                        <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> mb-4">
                                            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                        <?php endif; ?>

                                        <div class="auth-switcher" role="tablist" aria-label="Login and register forms">
                                            <button type="button" class="auth-switch <?php echo $active_tab === 'login' ? 'active' : ''; ?>" data-tab-target="login-panel">Login</button>
                                            <button type="button" class="auth-switch <?php echo $active_tab === 'register' ? 'active' : ''; ?>" data-tab-target="register-panel">Register</button>
                                        </div>

                                        <div id="login-panel" class="auth-panel <?php echo $active_tab === 'login' ? 'active' : ''; ?>">
                                            <h2>Welcome back</h2>
                                            <p class="auth-subtitle">Use your registered email and password to continue.</p>
                                            <form method="POST" action="" class="auth-form">
                                                <input type="hidden" name="form_type" value="login">
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <input type="email" name="email" class="form-control" placeholder="Email Address" value="<?php echo htmlspecialchars($login_data['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                    </div>
                                                    <div class="col-12">
                                                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                                                    </div>
                                                    <div class="col-12 d-grid">
                                                        <button type="submit" class="btn auth-btn text-white">Login</button>
                                                    </div>
                                                </div>
                                            </form>
                                            <p class="auth-note">New here? Tap the register button above to create your account.</p>
                                        </div>

                                        <div id="register-panel" class="auth-panel <?php echo $active_tab === 'register' ? 'active' : ''; ?>">
                                            <h2>Create account</h2>
                                            <p class="auth-subtitle">Register with the exact fields you requested.</p>
                                            <form method="POST" action="" class="auth-form">
                                                <input type="hidden" name="form_type" value="register">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <input type="text" name="name" class="form-control" placeholder="Name" value="<?php echo htmlspecialchars($register_data['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <input type="email" name="email" class="form-control" placeholder="Email" value="<?php echo htmlspecialchars($register_data['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                    </div>
                                                    <div class="col-12">
                                                        <select name="gender" class="form-select" required>
                                                            <option value="">Select Gender</option>
                                                            <?php foreach ($allowed_genders as $gender_option): ?>
                                                            <option value="<?php echo htmlspecialchars($gender_option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $register_data['gender'] === $gender_option ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($gender_option, ENT_QUOTES, 'UTF-8'); ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <input type="password" name="password" class="form-control" placeholder="Password" required minlength="6">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required minlength="6">
                                                    </div>
                                                    <div class="col-12 d-grid">
                                                        <button type="submit" class="btn auth-btn text-white">Register</button>
                                                    </div>
                                                </div>
                                            </form>
                                            <p class="auth-note">After registration, the form switches back to login with your email ready.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Navbar & Hero End -->

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var switchButtons = document.querySelectorAll('[data-tab-target]');
        var panels = document.querySelectorAll('.auth-panel');

        switchButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var targetId = button.getAttribute('data-tab-target');

                switchButtons.forEach(function (btn) {
                    btn.classList.remove('active');
                });

                panels.forEach(function (panel) {
                    panel.classList.remove('active');
                });

                button.classList.add('active');

                var targetPanel = document.getElementById(targetId);
                if (targetPanel) {
                    targetPanel.classList.add('active');
                }
            });
        });
    });
</script>

<?php include 'footer.php'; ?>

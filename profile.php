<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['frontend_user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['frontend_user'];
$userName = trim((string) ($user['name'] ?? 'Traveler'));
$userEmail = trim((string) ($user['email'] ?? ''));
$userGender = trim((string) ($user['gender'] ?? 'Not specified'));
$welcomeBackground = 'img/contactus.jpg';
$homePageResult = mysqli_query($conn, "SELECT bannerimage FROM home_page WHERE 1 LIMIT 1");
$homePage = $homePageResult ? mysqli_fetch_assoc($homePageResult) : null;

if (!empty($homePage['bannerimage'])) {
    $welcomeBackground = $homePage['bannerimage'];
}

include 'header2.php';
?>

<style>
    .account-page {
        position: relative;
        min-height: calc(100vh - 84px);
        padding: 48px 0 64px;
        overflow: hidden;
        isolation: isolate;
    }

    .account-page::before {
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

    .account-page::after {
        content: "";
        position: absolute;
        inset: 0;
        background:
            linear-gradient(120deg, rgba(10, 26, 46, 0.76), rgba(13, 110, 253, 0.34)),
            radial-gradient(circle at top left, rgba(255, 255, 255, 0.10), transparent 28%);
        z-index: -1;
    }

    .account-page .container {
        display: flex;
        align-items: stretch;
    }

    .account-card {
        max-width: 760px;
        margin: 0 auto;
        padding: 34px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(10px);
        box-shadow: 0 24px 60px rgba(15, 39, 66, 0.22);
    }

    .account-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 78px;
        height: 78px;
        margin-bottom: 22px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0d6efd, #ff7f50);
        color: #fff;
        font-size: 30px;
        font-weight: 800;
    }

    .account-title {
        margin-bottom: 8px;
        color: #15314b;
        font-size: clamp(2rem, 4vw, 2.7rem);
        font-weight: 800;
    }

    .account-subtitle {
        margin-bottom: 28px;
        color: #5f7389;
        font-size: 16px;
    }

    .account-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .account-field {
        padding: 18px 20px;
        border-radius: 18px;
        background: #f7faff;
        border: 1px solid #e1eaf3;
    }

    .account-field-label {
        display: block;
        margin-bottom: 6px;
        color: #6b7d90;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .account-field-value {
        color: #17324d;
        font-size: 18px;
        font-weight: 700;
        word-break: break-word;
    }

    .account-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 28px;
    }

    .account-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 48px;
        padding: 0 22px;
        border-radius: 999px;
        text-decoration: none;
        font-weight: 700;
        transition: 0.2s ease;
    }

    .account-btn-primary {
        background: #0d6efd;
        color: #fff;
    }

    .account-btn-primary:hover {
        background: #0b5ed7;
        color: #fff;
    }

    .account-btn-secondary {
        background: #edf4fb;
        color: #17324d;
    }

    .account-btn-secondary:hover {
        background: #dbe9f7;
        color: #17324d;
    }

    @media (max-width: 767.98px) {
        .account-page {
            padding: 28px 0 48px;
        }

        .account-card {
            padding: 24px;
        }

        .account-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="account-page">
    <div class="container">
        <section class="account-card">
            <div class="account-badge">
                <?php echo htmlspecialchars(strtoupper(substr($userName !== '' ? $userName : 'T', 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <h1 class="account-title">My Profile</h1>
            <p class="account-subtitle">View your account details here.</p>

            <div class="account-grid">
                <div class="account-field">
                    <span class="account-field-label">Full Name</span>
                    <span class="account-field-value"><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="account-field">
                    <span class="account-field-label">Email Address</span>
                    <span class="account-field-value"><?php echo htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="account-field">
                    <span class="account-field-label">Gender</span>
                    <span class="account-field-value"><?php echo htmlspecialchars($userGender, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="account-field">
                    <span class="account-field-label">Account Status</span>
                    <span class="account-field-value">Active</span>
                </div>
            </div>

            <div class="account-actions">
                <a href="welcome.php" class="account-btn account-btn-secondary">Back to Home</a>
            </div>
        </section>
    </div>
</main>

<?php include 'footer.php'; ?>

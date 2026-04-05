<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once 'config.php';
}

$header_result = mysqli_query($conn, "SELECT * FROM header_settings WHERE id = 1");
$header_settings = $header_result ? mysqli_fetch_assoc($header_result) : [];

$website_name = $header_settings['website_name'] ?? 'Tourist';
$logo = $header_settings['logo'] ?? '';
$profile_user = $_SESSION['frontend_user'] ?? [];
$profile_name = trim((string) ($profile_user['name'] ?? 'Traveler'));
$profile_email = trim((string) ($profile_user['email'] ?? ''));
$profile_initial = strtoupper(substr($profile_name !== '' ? $profile_name : 'T', 0, 1));
$current_page = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
$home_active = in_array($current_page, ['welcome.php', 'profile.php', 'change-password.php'], true) ? 'active' : '';
$destination_active = in_array($current_page, ['destination.php', 'alldestination.php'], true) ? 'active' : '';
$guides_active = $current_page === 'ourguides.php' ? 'active' : '';
$transport_active = $current_page === 'ourtransport.php' ? 'active' : '';
$hotels_active = $current_page === 'ourhotels.php' ? 'active' : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($website_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

    <style>
        body.user-dashboard-page {
            font-family: 'Heebo', sans-serif;
            background: #f7f9fc;
        }

        .header2 {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: #ffffff;
            border-bottom: 1px solid #e3ebf3;
        }

        .header2-inner {
            min-height: 84px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .header2-brand {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: #15314b;
            font-weight: 800;
            font-size: 24px;
        }

        .header2-brand img {
            height: 52px;
            width: auto;
            object-fit: contain;
        }

        .header2-nav {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .header2-nav a {
            text-decoration: none;
            color: #486076;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .header2-nav a:hover,
        .header2-nav a.active {
            color: #0d6efd;
        }

        .header2-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .header2-book-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 22px;
            border-radius: 999px;
            background: linear-gradient(135deg, #0d6efd, #ff7f50);
            box-shadow: 0 14px 30px rgba(13, 110, 253, 0.22);
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .header2-book-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 34px rgba(13, 110, 253, 0.28);
            color: #fff;
        }

        .header2-profile-dropdown {
            position: relative;
        }

        .header2-profile-toggle {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px 8px 8px;
            border: 1px solid #d8e3ef;
            border-radius: 999px;
            background: #f2f7fc;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .header2-profile-toggle:hover,
        .header2-profile-toggle:focus {
            border-color: #0d6efd;
            box-shadow: 0 10px 24px rgba(13, 110, 253, 0.16);
            outline: none;
        }

        .header2-profile-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0d6efd, #ff7f50);
            color: #fff;
            font-weight: 800;
            font-size: 18px;
        }

        .header2-profile-text {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-width: 0;
        }

        .header2-profile-name {
            color: #15314b;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.2;
        }

        .header2-profile-email {
            max-width: 180px;
            overflow: hidden;
            color: #6b7d90;
            font-size: 12px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .header2-profile-caret {
            color: #486076;
            font-size: 12px;
        }

        .header2-profile-menu {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            min-width: 220px;
            padding: 12px;
            border: 1px solid #dfe8f1;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 20px 45px rgba(17, 37, 63, 0.14);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: 0.2s ease;
        }

        .header2-profile-dropdown.open .header2-profile-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .header2-profile-summary {
            padding: 4px 6px 12px;
            border-bottom: 1px solid #edf2f7;
            margin-bottom: 8px;
        }

        .header2-profile-summary strong {
            display: block;
            color: #15314b;
            font-size: 15px;
        }

        .header2-profile-summary span {
            display: block;
            color: #6b7d90;
            font-size: 13px;
            word-break: break-word;
        }

        .header2-profile-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 12px;
            border-radius: 12px;
            color: #17324d;
            text-decoration: none;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .header2-profile-link:hover {
            background: #f2f7fc;
            color: #0d6efd;
        }

        @media (max-width: 991.98px) {
            .header2-inner {
                min-height: auto;
                padding-top: 16px;
                padding-bottom: 16px;
                flex-direction: column;
                align-items: stretch;
            }

            .header2-nav,
            .header2-actions {
                justify-content: center;
                flex-wrap: wrap;
            }
        }

        @media (max-width: 575.98px) {
            .header2-book-btn {
                width: 100%;
            }

            .header2-profile-dropdown,
            .header2-profile-toggle {
                width: 100%;
            }

            .header2-profile-menu {
                position: static;
                margin-top: 12px;
                min-width: 100%;
            }
        }
    </style>
</head>

<body class="user-dashboard-page">
    <header class="header2">
        <div class="container header2-inner">
            <a href="welcome.php" class="header2-brand">
                <?php if ($logo !== ''): ?>
                <img src="uploads/<?php echo htmlspecialchars($logo, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($website_name, ENT_QUOTES, 'UTF-8'); ?>">
                <?php else: ?>
                <span><?php echo htmlspecialchars($website_name, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </a>

            <nav class="header2-nav">
                <a href="welcome.php" class="<?php echo $home_active; ?>">Home</a>
                <a href="alldestination.php" class="<?php echo $destination_active; ?>">Destinations</a>
                <a href="ourguides.php" class="<?php echo $guides_active; ?>">Guides</a>
                <a href="ourtransport.php" class="<?php echo $transport_active; ?>">Transport</a>
                <a href="ourhotels.php" class="<?php echo $hotels_active; ?>">Hotels</a>
            </nav>

            <div class="header2-actions">
                <a href="booktrip.php" class="header2-book-btn">
                    <i class="fas fa-calendar-check"></i>
                    <span>Book Now</span>
                </a>
                <div class="header2-profile-dropdown" data-profile-dropdown>
                    <button type="button" class="header2-profile-toggle" data-profile-toggle aria-expanded="false">
                        <span class="header2-profile-icon"><?php echo htmlspecialchars($profile_initial, ENT_QUOTES, 'UTF-8'); ?></span>
                       
                    </button>
                    <div class="header2-profile-menu">
                        <div class="header2-profile-summary">
                            <strong><?php echo htmlspecialchars($profile_name, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span><?php echo htmlspecialchars($profile_email !== '' ? $profile_email : 'Traveler account', ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <a href="profile.php" class="header2-profile-link">
                            <i class="fas fa-user-circle"></i>
                            <span>Profile</span>
                        </a>
                        <a href="change-password.php" class="header2-profile-link">
                            <i class="fas fa-key"></i>
                            <span>Change Password</span>
                        </a>
                        <a href="login.php?action=logout" class="header2-profile-link">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var dropdown = document.querySelector('[data-profile-dropdown]');
            if (!dropdown) {
                return;
            }

            var toggle = dropdown.querySelector('[data-profile-toggle]');

            toggle.addEventListener('click', function(event) {
                event.stopPropagation();
                var isOpen = dropdown.classList.toggle('open');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            document.addEventListener('click', function(event) {
                if (!dropdown.contains(event.target)) {
                    dropdown.classList.remove('open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    dropdown.classList.remove('open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        });
    </script>

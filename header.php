<?php
include 'config.php';

$header_result = mysqli_query($conn, "SELECT * FROM header_settings WHERE id = 1");
$header_settings = mysqli_fetch_assoc($header_result);

$website_name = $header_settings['website_name'] ?? 'Tourist';
$logo = $header_settings['logo'] ?? '';
$email = $header_settings['email'] ?? '';
$mobile = $header_settings['mobile'] ?? '';
$address = $header_settings['address'] ?? '';
$social_twitter = $header_settings['social_twitter'] ?? '';
$social_facebook = $header_settings['social_facebook'] ?? '';
$social_linkedin = '';
$social_instagram = $header_settings['social_instagram'] ?? '';
$social_youtube = $header_settings['social_youtube'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($website_name); ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->


    <!-- Topbar Start -->
    <div class="container-fluid bg-dark px-5 d-none d-lg-block">
        <div class="row gx-0">
            <div class="col-lg-8 text-center text-lg-start mb-2 mb-lg-0">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <small class="me-3 text-light"><i class="fa fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($address); ?></small>
                    <small class="me-3 text-light"><i class="fa fa-phone-alt me-2"></i><?php echo htmlspecialchars($mobile); ?></small>
                    <small class="text-light"><i class="fa fa-envelope-open me-2"></i><?php echo htmlspecialchars($email); ?></small>
                </div>
            </div>

            
            <div class="col-lg-4 text-center text-lg-end">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <?php if (!empty($social_twitter)): ?>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href="<?php echo htmlspecialchars($social_twitter); ?>" target="_blank"><i class="fab fa-twitter fw-normal"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($social_facebook)): ?>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href="<?php echo htmlspecialchars($social_facebook); ?>" target="_blank"><i class="fab fa-facebook-f fw-normal"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($social_linkedin)): ?>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href="<?php echo htmlspecialchars($social_linkedin); ?>" target="_blank"><i class="fab fa-linkedin-in fw-normal"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($social_instagram)): ?>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href="<?php echo htmlspecialchars($social_instagram); ?>" target="_blank"><i class="fab fa-instagram fw-normal"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($social_youtube)): ?>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle" href="<?php echo htmlspecialchars($social_youtube); ?>" target="_blank"><i class="fab fa-youtube fw-normal"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Topbar End -->


    <!-- Navbar & Hero Start -->
    <div class="container-fluid position-relative p-0">
        <nav class="navbar navbar-expand-lg navbar-light px-4 px-lg-5 py-3 py-lg-0">
            <a href="" class="navbar-brand p-0">
                <?php if (!empty($logo)): ?>
                <img src="uploads/<?php echo htmlspecialchars($logo); ?>" alt="Logo" style="height: 150px;">
                <?php else: ?>
                <h1 class="text-primary m-0"><i class="fa fa-map-marker-alt me-3"></i><?php echo htmlspecialchars($website_name); ?></h1>
                <?php endif; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto py-0">
                    <a href="index.php" class="nav-item nav-link active">Home</a>
                    <a href="about.php" class="nav-item nav-link">About</a>
                    <a href="destination.php" class="nav-item nav-link">Destination</a>
                    <!-- <a href="service.php" class="nav-item nav-link">Services</a> -->
                    <a href="contact.php" class="nav-item nav-link">Contact</a>
                </div>
                <a href="#" class="btn btn-primary rounded-pill py-2 px-4">Login</a>
            </div>
        </nav>

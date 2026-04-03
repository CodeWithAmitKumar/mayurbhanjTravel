<?php 
include 'config.php';
$about = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM about_page LIMIT 1"));
include 'header.php'; 
?>

        <div class="container-fluid bg-primary py-5 mb-5 hero-header" <?php if (!empty($about['bannerimage'])): ?>style="background: url('<?php echo $about['bannerimage']; ?>') center/cover no-repeat;"<?php endif; ?>>
            <div class="container py-5">
                <div class="row justify-content-center py-5">
                    <div class="col-lg-10 pt-lg-5 mt-lg-5 text-center">
                        <h1 class="display-3 text-white animated slideInDown"><?php echo !empty($about['pageheading']) ? htmlspecialchars($about['pageheading']) : 'About Us'; ?></h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb justify-content-center">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item text-white active" aria-current="page">About</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Navbar & Hero End -->


    <!-- About Start -->
    <div class="container-fluid py-5">
        <div class="container">
            <div class="row g-5 align-items-start">
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="position-relative">
                        <img class="img-fluid w-100" src="<?php echo !empty($about['aboutimage']) ? $about['aboutimage'] : 'img/about.jpg'; ?>" alt="" style="object-fit: cover; border-radius: 10px;">
                    </div>
                </div>
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.3s">

                    <h6 class="section-title bg-white text-start text-primary pe-3"><?php echo !empty($about['pageheading']) ? htmlspecialchars($about['pageheading']) : 'About Us'; ?></h6>

                    <h1 class="mb-4"><?php if (!empty($about['highlight_heading'])): ?>Welcome to <span class="text-primary"><?php echo htmlspecialchars($about['highlight_heading']); ?></span><?php else: ?>Welcome to <span class="text-primary">Tourist</span><?php endif; ?></h1>
                    <?php if (!empty($about['description'])): ?>
                    <?php echo $about['description']; ?>
                    <?php else: ?>
                    <p class="mb-4">Tempor erat elitr rebum at clita. Diam dolor diam ipsum sit. Aliqu diam amet diam et eos. Clita erat ipsum et lorem et sit.</p>
                    <p class="mb-4">Tempor erat elitr rebum at clita. Diam dolor diam ipsum sit. Aliqu diam amet diam et eos. Clita erat ipsum et lorem et sit, sed stet lorem sit clita duo justo magna dolore erat amet</p>
                    <?php endif; ?>
                  
                    <a class="btn btn-primary py-3 px-5 mt-2" href="">Read More</a>
                </div>
            </div>
        </div>
    </div>
    <!-- About End -->

    <!-- Map Start -->
    <?php 
    $map_settings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT map_url FROM header_settings LIMIT 1"));
    if (!empty($map_settings['map_url'])): 
    ?>
    <div class="container-fluid py-5">
        <div class="container">
            <div class="text-center mb-4">
                <h6 class="section-title bg-white text-center text-primary px-3">Location</h6>
                <h1 class="mb-0">Map of Mayurbhanj</h1>
            </div>
            <div class="row">
                <div class="col-12">
                    <iframe class="w-100" src="<?php echo htmlspecialchars($map_settings['map_url']); ?>" width="100%" height="450" style="border:0; border-radius: 10px;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <!-- Map End -->
         
    <?php include 'footer.php'; ?>
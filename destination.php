<?php include 'header.php'; ?>
<?php $destination = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM destination_page LIMIT 1")); ?>
        <div class="container-fluid bg-primary py-5 mb-5 hero-header" style="<?php echo !empty($destination['destinationbanner']) ? 'background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url(\'' . htmlspecialchars($destination['destinationbanner']) . '\'); background-size: cover; background-position: center;' : 'background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url(img/hero.jpg); background-size: cover; background-position: center;'; ?>">
            <div class="container py-5">
                <div class="row justify-content-center py-5">
                    <div class="col-lg-10 pt-lg-5 mt-lg-5 text-center">
                        <h1 class="display-3 text-white animated slideInDown"><?php echo !empty($destination['pageheading']) ? htmlspecialchars($destination['pageheading']) : 'Destination'; ?></h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb justify-content-center">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item text-white active" aria-current="page">Destination</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Navbar & Hero End -->


    <!-- Destination Start -->
    <div class="container-fluid py-5 destination">
        <div class="container">
            <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                <h6 class="section-title bg-white text-center text-primary px-3"><?php echo !empty($destination['pageheading']) ? htmlspecialchars($destination['pageheading']) : 'Destination'; ?></h6>
                <h1 class="mb-5"><?php echo !empty($destination['main_heading']) ? htmlspecialchars($destination['main_heading']) : 'Popular Destination'; ?></h1>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="destination-item rounded overflow-hidden position-relative">
                        <img class="img-fluid w-100" src="<?php echo !empty($destination['image1']) ? htmlspecialchars($destination['image1']) : 'img/destination-1.jpg'; ?>" alt="" style="height: 250px; object-fit: cover;">
                        <div class="destination-overlay">
                            <h5 class="text-white mb-0"><?php echo !empty($destination['image1_name']) ? htmlspecialchars($destination['image1_name']) : 'Thailand'; ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="destination-item rounded overflow-hidden position-relative">
                        <img class="img-fluid w-100" src="<?php echo !empty($destination['image2']) ? htmlspecialchars($destination['image2']) : 'img/destination-2.jpg'; ?>" alt="" style="height: 250px; object-fit: cover;">
                        <div class="destination-overlay">
                            <h5 class="text-white mb-0"><?php echo !empty($destination['image2_name']) ? htmlspecialchars($destination['image2_name']) : 'Malaysia'; ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.5s">
                    <div class="destination-item rounded overflow-hidden position-relative">
                        <img class="img-fluid w-100" src="<?php echo !empty($destination['image3']) ? htmlspecialchars($destination['image3']) : 'img/destination-3.jpg'; ?>" alt="" style="height: 250px; object-fit: cover;">
                        <div class="destination-overlay">
                            <h5 class="text-white mb-0"><?php echo !empty($destination['image3_name']) ? htmlspecialchars($destination['image3_name']) : 'Australia'; ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.7s">
                    <div class="destination-item rounded overflow-hidden position-relative">
                        <img class="img-fluid w-100" src="<?php echo !empty($destination['image4']) ? htmlspecialchars($destination['image4']) : 'img/destination-4.jpg'; ?>" alt="" style="height: 250px; object-fit: cover;">
                        <div class="destination-overlay">
                            <h5 class="text-white mb-0"><?php echo !empty($destination['image4_name']) ? htmlspecialchars($destination['image4_name']) : 'Indonesia'; ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Destination Start -->

    <?php include 'footer.php'; ?>
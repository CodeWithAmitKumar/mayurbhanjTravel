<?php
include 'config.php';
require_once 'lib/contact_query_helper.php';

$form_message = '';
$form_message_type = '';
$form_data = [
    'name' => '',
    'email' => '',
    'subject' => '',
    'message' => '',
];

try {
    mbj_ensure_contact_queries_table($conn);
} catch (Throwable $throwable) {
    $form_message = 'We could not load the contact form right now. Please try again in a moment.';
    $form_message_type = 'danger';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['name'] = trim((string) ($_POST['name'] ?? ''));
    $form_data['email'] = trim((string) ($_POST['email'] ?? ''));
    $form_data['subject'] = trim((string) ($_POST['subject'] ?? ''));
    $form_data['message'] = trim((string) ($_POST['message'] ?? ''));

    if ($form_data['name'] === '' || $form_data['email'] === '' || $form_data['subject'] === '' || $form_data['message'] === '') {
        $form_message = 'Please fill in all fields before sending your message.';
        $form_message_type = 'danger';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $form_message = 'Please enter a valid email address.';
        $form_message_type = 'danger';
    } else {
        try {
            $queryId = mbj_store_contact_query($conn, $form_data);
            $storedQuery = mbj_get_contact_query_by_id($conn, $queryId);

            if (!$storedQuery) {
                throw new RuntimeException('Unable to read the saved query.');
            }

            $replyResult = [
                'sent' => false,
                'subject' => '',
                'error' => '',
            ];

            try {
                $replyResult = mbj_send_contact_auto_reply($conn, $storedQuery);
            } catch (Throwable $throwable) {
                $replyResult['error'] = $throwable->getMessage();
            }

            mbj_update_contact_query_reply_status(
                $conn,
                $queryId,
                (bool) $replyResult['sent'],
                (string) ($replyResult['subject'] ?? ''),
                (string) ($replyResult['error'] ?? '')
            );

            $form_message = $replyResult['sent']
                ? 'Your query has been sent successfully. We have also emailed you a confirmation message.'
                : 'Your query has been sent successfully. We will contact you soon.';
            $form_message_type = 'success';
            $form_data = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
        } catch (Throwable $throwable) {
            $form_message = 'We could not send your query right now. Please try again.';
            $form_message_type = 'danger';
        }
    }
}
?>
<?php include 'header.php'; ?>
<?php $hero = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_page LIMIT 1")); ?>
        <div class="container-fluid py-5 mb-5 hero-header" style="<?php echo !empty($hero['bannerimage']) ? 'background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url(\'' . $hero['bannerimage'] . '\'); background-size: cover; background-position: center;' : 'background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url(img/hero.jpg); background-size: cover; background-position: center;'; ?>">
            <div class="container py-5">
                <div class="row justify-content-center py-5">
                    <div class="col-lg-10 pt-lg-5 mt-lg-5 text-center">
                        <h1 class="display-3 text-white mb-3 animated slideInDown"><?php echo !empty($hero['heading']) ? htmlspecialchars($hero['heading']) : 'Enjoy Your Vacation With Us'; ?></h1>
                        <p class="fs-4 text-white mb-4 animated slideInDown"><?php echo !empty($hero['short_heading']) ? htmlspecialchars($hero['short_heading']) : 'Tempor erat elitr rebum at clita diam amet diam et eos erat ipsum lorem sit'; ?></p>
                        <!-- <div class="position-relative w-75 mx-auto animated slideInDown">
                            <input class="form-control border-0 rounded-pill w-100 py-3 ps-4 pe-5" type="text" placeholder="Eg: Thailand">
                            <button type="button" class="btn btn-primary rounded-pill py-2 px-4 position-absolute top-0 end-0 me-2" style="margin-top: 7px;">Search</button> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Navbar & Hero End -->

<?php $about = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM about_page LIMIT 1")); ?>
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


    <!-- Service Start -->
    <?php
    $services = mysqli_query($conn, "SELECT * FROM services ORDER BY sort_order ASC");
    $service_heading = mysqli_fetch_assoc(mysqli_query($conn, "SELECT page_heading FROM services LIMIT 1"));
    ?>
    <div class="container-fluid py-5">
        <div class="container">
            <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                <h6 class="section-title bg-white text-center text-primary px-3"><?php echo !empty($service_heading['page_heading']) ? htmlspecialchars($service_heading['page_heading']) : 'Services'; ?></h6>
                <h1 class="mb-5">Our Services</h1>
            </div>
            <div class="row g-4">
                <?php $delay = 0.1; while($service = mysqli_fetch_assoc($services)): ?>
                <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="<?php echo $delay; ?>s">
                    <div class="service-item rounded pt-3">
                        <div class="p-4">
                            <?php if (!empty($service['service_logo'])): ?>
                            <img src="<?php echo htmlspecialchars($service['service_logo']); ?>" class="mb-4" style="width: 48px; height: 48px; object-fit: contain;">
                            <?php else: ?>
                            <i class="fa fa-3x fa-globe text-primary mb-4"></i>
                            <?php endif; ?>
                            <h5><?php echo htmlspecialchars($service['service_name']); ?></h5>
                            <p><?php echo htmlspecialchars($service['service_desc']); ?></p>
                        </div>
                    </div>
                </div>
                <?php $delay = $delay + 0.2; if($delay > 0.7) $delay = 0.1; endwhile; ?>
            </div>
        </div>
    </div>
    <!-- Service End -->
<?php $destination = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM destination_page LIMIT 1")); ?>

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

    <?php
    $contact_settings = mysqli_query($conn, "SELECT address, mobile, email, map_url FROM header_settings WHERE id = 1");
    $contact = mysqli_fetch_assoc($contact_settings);
    ?>
    <!-- Contact Start -->
    <div class="container-fluid py-5">
        <div class="container">
            <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                <h6 class="section-title bg-white text-center text-primary px-3">Contact Us</h6>
                <h1 class="mb-5">Contact For Any Query</h1>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                    <h5>Get In Touch</h5>
                    <h6 class="mb-4">Feel free to contact us for any inquiries or questions.</h6>
                    <div class="d-flex align-items-center mb-4">
                        <div class="d-flex align-items-center justify-content-center flex-shrink-0 bg-primary" style="width: 50px; height: 50px;">
                            <i class="fa fa-map-marker-alt text-white"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="text-primary">Office</h5>
                            <p class="mb-0"><?php echo $contact['address']; ?></p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-4">
                        <div class="d-flex align-items-center justify-content-center flex-shrink-0 bg-primary" style="width: 50px; height: 50px;">
                            <i class="fa fa-phone-alt text-white"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="text-primary">Mobile</h5>
                            <p class="mb-0"><?php echo $contact['mobile']; ?></p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="d-flex align-items-center justify-content-center flex-shrink-0 bg-primary" style="width: 50px; height: 50px;">
                            <i class="fa fa-envelope-open text-white"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="text-primary">Email</h5>
                            <p class="mb-0"><?php echo $contact['email']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
                    <iframe class="position-relative rounded w-100 h-100"
                        src="<?php echo $contact['map_url']; ?>"
                        frameborder="0" style="min-height: 300px; border:0;" allowfullscreen="" aria-hidden="false"
                        tabindex="0"></iframe>
                </div>
                <div class="col-lg-4 col-md-12 wow fadeInUp" data-wow-delay="0.5s" id="homepage-contact-form">
                    <?php if ($form_message !== ''): ?>
                    <div class="alert alert-<?php echo $form_message_type === 'success' ? 'success' : 'danger'; ?>">
                        <?php echo htmlspecialchars($form_message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <?php endif; ?>
                    <form method="POST" action="#homepage-contact-form">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="name" name="name" placeholder="Your Name" value="<?php echo htmlspecialchars($form_data['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    <label for="name">Your Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Your Email" value="<?php echo htmlspecialchars($form_data['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    <label for="email">Your Email</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="subject" name="subject" placeholder="Subject" value="<?php echo htmlspecialchars($form_data['subject'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    <label for="subject">Subject</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" placeholder="Leave a message here" id="message" name="message" style="height: 100px" required><?php echo htmlspecialchars($form_data['message'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    <label for="message">Message</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary w-100 py-3" type="submit">Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Contact End -->

<?php include 'footer.php'; ?>

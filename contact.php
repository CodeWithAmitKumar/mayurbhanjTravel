<?php
include 'config.php';
require_once 'lib/contact_query_helper.php';

$settings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT `email`, `mobile`, `address`, `map_url` FROM `header_settings` WHERE 1 LIMIT 1"));

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

include 'header.php';
?>

        <div class="container-fluid bg-primary py-5 mb-5 hero-header">
            <div class="container py-5">
                <div class="row justify-content-center py-5">
                    <div class="col-lg-10 pt-lg-5 mt-lg-5 text-center">
                        <h1 class="display-3 text-white animated slideInDown">Contact Us</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb justify-content-center">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item text-white active" aria-current="page">Contact</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Navbar & Hero End -->


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
                            <p class="mb-0"><?php echo $settings['address']; ?></p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-4">
                        <div class="d-flex align-items-center justify-content-center flex-shrink-0 bg-primary" style="width: 50px; height: 50px;">
                            <i class="fa fa-phone-alt text-white"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="text-primary">Mobile</h5>
                            <p class="mb-0"><?php echo $settings['mobile']; ?></p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="d-flex align-items-center justify-content-center flex-shrink-0 bg-primary" style="width: 50px; height: 50px;">
                            <i class="fa fa-envelope-open text-white"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="text-primary">Email</h5>
                            <p class="mb-0"><?php echo $settings['email']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
                    <iframe class="position-relative rounded w-100 h-100"
                        src="<?php echo $settings['map_url']; ?>"
                        frameborder="0" style="min-height: 300px; border:0;" allowfullscreen="" aria-hidden="false"
                        tabindex="0"></iframe>
                </div>
                <div class="col-lg-4 col-md-12 wow fadeInUp" data-wow-delay="0.5s">
                    <?php if ($form_message !== ''): ?>
                    <div class="alert alert-<?php echo $form_message_type === 'success' ? 'success' : 'danger'; ?>">
                        <?php echo htmlspecialchars($form_message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <?php endif; ?>
                    <form method="POST" action="">
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

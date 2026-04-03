
<?php
$footer_settings = mysqli_query($conn, "SELECT * FROM header_settings LIMIT 1");
$footer = mysqli_fetch_assoc($footer_settings);
?>
    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-4 col-md-6">
                    <h4 class="text-white mb-3">Contact</h4>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i><?php echo $footer['address']; ?></p>
                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i><?php echo $footer['mobile']; ?></p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i><?php echo $footer['email']; ?></p>
                </div>
                <div class="col-lg-4 col-md-6 text-center">
                    <h4 class="text-white mb-3">Follow Us</h4>
                    <div class="d-flex pt-2 justify-content-center gap-2">
                        <?php if($footer['social_twitter']): ?>
                        <a class="btn btn-outline-light btn-social" href="<?php echo $footer['social_twitter']; ?>"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if($footer['social_facebook']): ?>
                        <a class="btn btn-outline-light btn-social" href="<?php echo $footer['social_facebook']; ?>"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if($footer['social_youtube']): ?>
                        <a class="btn btn-outline-light btn-social" href="<?php echo $footer['social_youtube']; ?>"><i class="fab fa-youtube"></i></a>
                        <?php endif; ?>
                        <?php if($footer['social_instagram']): ?>
                        <a class="btn btn-outline-light btn-social" href="<?php echo $footer['social_instagram']; ?>"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <h4 class="text-white mb-3">Newsletter</h4>
                    <p>Dolor amet sit justo amet elitr clita ipsum elitr est.</p>
                    <div class="position-relative mx-auto" style="max-width: 400px;">
                        <input class="form-control border-primary w-100 py-3 ps-4 pe-5" type="text" placeholder="Your email">
                        <button type="button" class="btn btn-primary py-2 position-absolute top-0 end-0 mt-2 me-2">SignUp</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="copyright">
                <div class="row">
                    <div class="col-12 text-center">
                        &copy; <a class="border-bottom" href="#"><?php echo $footer['website_name']; ?></a>, All Right Reserved.

                        <!--/*** The author's attribution link below must remain intact on your website. ***/-->
                        <!--/*** If you wish to remove this credit link, please purchase the Pro Version from https://htmlcodex.com . ***/-->
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->


    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>



<?php
// Footer File (includes/footer.php)
?>
    </div><!-- end of container -->

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Job Portal</h5>
                    <p>Find your dream job or hire the perfect candidate.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo isset($basePath) ? $basePath : ''; ?>/index.php" class="text-white">Home</a></li>
                        <li><a href="#" class="text-white">About Us</a></li>
                        <li><a href="#" class="text-white">Contact</a></li>
                        <li><a href="#" class="text-white">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Info</h5>
                    <address>
                        <i class="fas fa-map-marker-alt"></i> 123 Job Street, Ahmedabad<br>
                        <i class="fas fa-phone"></i> +919725094826<br>
                        <i class="fas fa-envelope"></i> info@jobportal.com
                    </address>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> Job Portal. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="<?php echo isset($basePath) ? $basePath : ''; ?>/assets/js/script.js"></script>
</body>
</html>
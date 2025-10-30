    </div>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Situs Lelang Online</h5>
                    <p>Platform lelang online terpercaya untuk membeli dan menjual barang berkualitas.</p>
                </div>
                <div class="col-md-6">
                    <h5>Kontak</h5>
                    <p><i class="fas fa-envelope me-2"></i>info@situslelang.com</p>
                    <p><i class="fas fa-phone me-2"></i>+62 812-3456-7890</p>
                </div>
            </div>
            <hr class="bg-light">
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> Situs Lelang Online. Hak Cipta Dilindungi.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fungsi countdown timer
        function updateCountdown(elementId, endTime) {
            const element = document.getElementById(elementId);
            if (!element) return;
            
            const countDownDate = new Date(endTime).getTime();
            
            const x = setInterval(function() {
                const now = new Date().getTime();
                const distance = countDownDate - now;
                
                if (distance < 0) {
                    clearInterval(x);
                    element.innerHTML = "Lelang telah berakhir";
                    return;
                }
                
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                element.innerHTML = days + " hari, " + hours + " jam, " + minutes + " menit, " + seconds + " detik";
            }, 1000);
        }
        
        // Fungsi untuk galeri gambar
        function changeMainImage(imageUrl) {
            document.getElementById('mainImage').src = imageUrl;
            
            // Update active class
            document.querySelectorAll('.gallery-image').forEach(img => {
                img.classList.remove('active');
            });
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
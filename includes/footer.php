    </main>
    
    <?php if (!$hideNavigation && isLoggedIn()): ?>
    <!-- Bottom Navigation for Mobile -->
    <nav class="bottom-nav d-lg-none fixed-bottom bg-white border-top">
        <div class="container-fluid">
            <div class="row text-center">
                <?php if (!isAdmin()): ?>
                    <div class="col">
                        <a href="index.php" class="bottom-nav-link">
                            <i class="fas fa-home"></i>
                            <span>Home</span>
                        </a>
                    </div>
                    <div class="col">
                        <a href="order.php" class="bottom-nav-link">
                            <i class="fas fa-bicycle"></i>
                            <span>Book</span>
                        </a>
                    </div>
                    <div class="col">
                        <a href="history.php" class="bottom-nav-link">
                            <i class="fas fa-history"></i>
                            <span>History</span>
                        </a>
                    </div>
                    <?php if (isDriver()): ?>
                    <div class="col">
                        <a href="driver.php" class="bottom-nav-link">
                            <i class="fas fa-bicycle"></i>
                            <span>Driver</span>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="col">
                        <a href="#" class="bottom-nav-link">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </a>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>assets/js/script.js"></script>
    
    <!-- Service Worker Registration for PWA -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').then(function(registration) {
                    console.log('ServiceWorker registration successful');
                }, function(err) {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>
    
    <!-- Real-time notifications -->
    <?php if (isLoggedIn()): ?>
    <script>
        // Initialize real-time features
        document.addEventListener('DOMContentLoaded', function() {
            // Check for new notifications every 30 seconds
            setInterval(checkNotifications, 30000);
            
            // Update online status
            updateOnlineStatus();
            
            // Listen for online/offline events
            window.addEventListener('online', updateOnlineStatus);
            window.addEventListener('offline', updateOnlineStatus);
        });
        
        function checkNotifications() {
            // This would connect to a real-time service like WebSockets or Server-Sent Events
            // For now, we'll use simple polling
            if (typeof loadRecentOrders === 'function') {
                loadRecentOrders();
            }
        }
        
        function updateOnlineStatus() {
            const isOnline = navigator.onLine;
            const statusElements = document.querySelectorAll('.connection-status');
            statusElements.forEach(el => {
                el.classList.toggle('online', isOnline);
                el.classList.toggle('offline', !isOnline);
            });
        }
    </script>
    <?php endif; ?>
    
    <!-- Global error handler -->
    <script>
        window.addEventListener('error', function(e) {
            console.error('Global error:', e.error);
            // In production, send errors to monitoring service
        });
        
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Unhandled promise rejection:', e.reason);
            // In production, send errors to monitoring service
        });
    </script>
    
    <!-- Performance monitoring -->
    <script>
        // Monitor page load performance
        window.addEventListener('load', function() {
            setTimeout(function() {
                const perfData = performance.getEntriesByType('navigation')[0];
                if (perfData) {
                    const loadTime = perfData.loadEventEnd - perfData.loadEventStart;
                    console.log('Page load time:', loadTime + 'ms');
                    
                    // In production, send to analytics service
                    if (loadTime > 3000) {
                        console.warn('Slow page load detected');
                    }
                }
            }, 0);
        });
    </script>
</body>
</html>

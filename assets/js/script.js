/**
 * RideMax Super App - Main JavaScript File
 * Mobile-optimized JavaScript for enhanced user experience
 */

(function() {
    'use strict';

    // Global app object
    window.RideMax = {
        config: {
            apiBaseUrl: window.location.origin,
            refreshInterval: 30000,
            locationUpdateInterval: 10000,
            notificationTimeout: 5000
        },
        
        // Current user location
        userLocation: null,
        
        // App state
        state: {
            isOnline: navigator.onLine,
            notifications: [],
            activeRequests: new Map()
        }
    };

    // Initialize app on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeApp();
    });

    /**
     * Initialize the application
     */
    function initializeApp() {
        setupEventListeners();
        setupNetworkHandling();
        setupNotifications();
        setupGeolocation();
        setupFormValidation();
        setupRealTimeFeatures();
        updateActiveNavigation();
        
        console.log('RideMax app initialized');
    }

    /**
     * Setup global event listeners
     */
    function setupEventListeners() {
        // Handle back button
        window.addEventListener('popstate', handleBackButton);
        
        // Handle visibility change
        document.addEventListener('visibilitychange', handleVisibilityChange);
        
        // Handle touch events for better mobile experience
        setupTouchHandlers();
        
        // Setup lazy loading for images
        setupLazyLoading();
        
        // Setup infinite scroll where applicable
        setupInfiniteScroll();
    }

    /**
     * Setup network status handling
     */
    function setupNetworkHandling() {
        window.addEventListener('online', function() {
            RideMax.state.isOnline = true;
            showNotification('Connection restored', 'success');
            syncOfflineData();
        });

        window.addEventListener('offline', function() {
            RideMax.state.isOnline = false;
            showNotification('You are offline', 'warning');
        });
        
        // Show connection status
        updateConnectionStatus();
    }

    /**
     * Setup push notifications
     */
    function setupNotifications() {
        if ('Notification' in window && 'serviceWorker' in navigator) {
            requestNotificationPermission();
        }
    }

    /**
     * Setup geolocation services
     */
    function setupGeolocation() {
        if ('geolocation' in navigator) {
            getCurrentLocation();
            
            // Watch position for real-time updates
            if (window.location.pathname.includes('driver.php') || 
                window.location.pathname.includes('order.php')) {
                watchUserLocation();
            }
        }
    }

    /**
     * Setup form validation
     */
    function setupFormValidation() {
        const forms = document.querySelectorAll('form[novalidate]');
        forms.forEach(form => {
            form.addEventListener('submit', handleFormSubmit);
            
            // Real-time validation
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('blur', validateField);
                input.addEventListener('input', debounce(validateField, 500));
            });
        });
    }

    /**
     * Setup real-time features
     */
    function setupRealTimeFeatures() {
        // Start periodic updates for order status
        if (isLoggedIn()) {
            startPeriodicUpdates();
        }
        
        // Setup WebSocket connection if available
        if (window.WebSocket) {
            setupWebSocket();
        }
    }

    /**
     * Handle form submission with loading states
     */
    function handleFormSubmit(event) {
        const form = event.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (!validateForm(form)) {
            event.preventDefault();
            return false;
        }
        
        // Show loading state
        if (submitBtn) {
            setButtonLoading(submitBtn, true);
        }
    }

    /**
     * Validate individual field
     */
    function validateField(event) {
        const field = event.target;
        const value = field.value.trim();
        let isValid = true;
        let message = '';

        // Remove previous validation
        clearFieldValidation(field);

        // Required field validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            message = 'This field is required';
        }

        // Email validation
        if (field.type === 'email' && value && !isValidEmail(value)) {
            isValid = false;
            message = 'Please enter a valid email address';
        }

        // Phone validation
        if (field.type === 'tel' && value && !isValidPhone(value)) {
            isValid = false;
            message = 'Please enter a valid phone number';
        }

        // Password validation
        if (field.type === 'password' && value && value.length < 6) {
            isValid = false;
            message = 'Password must be at least 6 characters';
        }

        // Custom validation
        if (field.hasAttribute('data-validate')) {
            const validateType = field.getAttribute('data-validate');
            const customValidation = customValidators[validateType];
            if (customValidation && !customValidation(value)) {
                isValid = false;
                message = field.getAttribute('data-validate-message') || 'Invalid input';
            }
        }

        // Show validation result
        if (!isValid) {
            showFieldError(field, message);
        } else {
            showFieldSuccess(field);
        }

        return isValid;
    }

    /**
     * Validate entire form
     */
    function validateForm(form) {
        const fields = form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;

        fields.forEach(field => {
            if (!validateField({ target: field })) {
                isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Custom validators
     */
    const customValidators = {
        indonesianPhone: function(value) {
            return /^(\+62|62|0)[0-9]{9,13}$/.test(value.replace(/\s/g, ''));
        },
        
        licensePlate: function(value) {
            return /^[A-Z]{1,2}\s?\d{1,4}\s?[A-Z]{1,3}$/i.test(value);
        },
        
        strongPassword: function(value) {
            return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(value);
        }
    };

    /**
     * Get current location
     */
    function getCurrentLocation() {
        if (!navigator.geolocation) return;

        const options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000 // 5 minutes
        };

        navigator.geolocation.getCurrentPosition(
            function(position) {
                RideMax.userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    timestamp: position.timestamp
                };
                
                console.log('Location updated:', RideMax.userLocation);
                triggerLocationUpdate();
            },
            function(error) {
                console.error('Geolocation error:', error);
                handleLocationError(error);
            },
            options
        );
    }

    /**
     * Watch user location for real-time updates
     */
    function watchUserLocation() {
        if (!navigator.geolocation) return;

        const options = {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 60000 // 1 minute
        };

        const watchId = navigator.geolocation.watchPosition(
            function(position) {
                const newLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    timestamp: position.timestamp
                };

                // Only update if location changed significantly
                if (!RideMax.userLocation || 
                    calculateDistance(RideMax.userLocation, newLocation) > 0.01) {
                    RideMax.userLocation = newLocation;
                    triggerLocationUpdate();
                }
            },
            function(error) {
                console.error('Location watch error:', error);
            },
            options
        );

        // Store watch ID for cleanup
        RideMax.locationWatchId = watchId;
    }

    /**
     * Handle location errors
     */
    function handleLocationError(error) {
        let message = 'Unable to get your location';
        
        switch(error.code) {
            case error.PERMISSION_DENIED:
                message = 'Location access denied. Please enable location services.';
                break;
            case error.POSITION_UNAVAILABLE:
                message = 'Location information unavailable.';
                break;
            case error.TIMEOUT:
                message = 'Location request timed out.';
                break;
        }
        
        showNotification(message, 'warning');
    }

    /**
     * Calculate distance between two points
     */
    function calculateDistance(pos1, pos2) {
        const R = 6371; // Earth's radius in km
        const dLat = (pos2.lat - pos1.lat) * Math.PI / 180;
        const dLng = (pos2.lng - pos1.lng) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(pos1.lat * Math.PI / 180) * Math.cos(pos2.lat * Math.PI / 180) *
                  Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    /**
     * Trigger location update events
     */
    function triggerLocationUpdate() {
        const event = new CustomEvent('locationUpdated', {
            detail: RideMax.userLocation
        });
        document.dispatchEvent(event);
    }

    /**
     * Setup touch handlers for better mobile experience
     */
    function setupTouchHandlers() {
        // Add touch feedback to buttons
        const buttons = document.querySelectorAll('.btn, .action-card, .order-card');
        buttons.forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            
            button.addEventListener('touchend', function() {
                this.style.transform = '';
            });
        });

        // Swipe gestures for cards
        setupSwipeGestures();
    }

    /**
     * Setup swipe gestures
     */
    function setupSwipeGestures() {
        let startX, startY, startTime;
        
        document.addEventListener('touchstart', function(e) {
            const touch = e.touches[0];
            startX = touch.clientX;
            startY = touch.clientY;
            startTime = Date.now();
        });
        
        document.addEventListener('touchend', function(e) {
            if (!startX || !startY) return;
            
            const touch = e.changedTouches[0];
            const endX = touch.clientX;
            const endY = touch.clientY;
            const endTime = Date.now();
            
            const deltaX = endX - startX;
            const deltaY = endY - startY;
            const deltaTime = endTime - startTime;
            
            // Detect swipe
            if (Math.abs(deltaX) > 50 && deltaTime < 300) {
                const direction = deltaX > 0 ? 'right' : 'left';
                triggerSwipe(e.target, direction);
            }
            
            // Reset
            startX = startY = null;
        });
    }

    /**
     * Trigger swipe event
     */
    function triggerSwipe(element, direction) {
        const swipeEvent = new CustomEvent('swipe', {
            detail: { direction: direction }
        });
        element.dispatchEvent(swipeEvent);
    }

    /**
     * Setup lazy loading for images
     */
    function setupLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    /**
     * Setup infinite scroll
     */
    function setupInfiniteScroll() {
        const scrollContainers = document.querySelectorAll('[data-infinite-scroll]');
        
        scrollContainers.forEach(container => {
            container.addEventListener('scroll', debounce(function() {
                if (container.scrollTop + container.clientHeight >= container.scrollHeight - 100) {
                    const loadMoreEvent = new CustomEvent('loadMore');
                    container.dispatchEvent(loadMoreEvent);
                }
            }, 200));
        });
    }

    /**
     * Start periodic updates
     */
    function startPeriodicUpdates() {
        setInterval(function() {
            if (document.visibilityState === 'visible' && RideMax.state.isOnline) {
                updatePageData();
            }
        }, RideMax.config.refreshInterval);
    }

    /**
     * Update page-specific data
     */
    function updatePageData() {
        const pathname = window.location.pathname;
        
        if (pathname.includes('index.php')) {
            if (typeof loadRecentOrders === 'function') {
                loadRecentOrders();
            }
        } else if (pathname.includes('history.php')) {
            if (typeof loadOrders === 'function') {
                loadOrders();
            }
        } else if (pathname.includes('driver.php')) {
            if (typeof loadAvailableRides === 'function' && driverOnline) {
                loadAvailableRides();
            }
        } else if (pathname.includes('admin/dashboard.php')) {
            if (typeof loadRecentActivities === 'function') {
                loadRecentActivities();
            }
        }
    }

    /**
     * Setup WebSocket connection
     */
    function setupWebSocket() {
        if (!isLoggedIn()) return;
        
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${protocol}//${window.location.host}/ws`;
        
        try {
            const ws = new WebSocket(wsUrl);
            
            ws.onopen = function() {
                console.log('WebSocket connected');
                RideMax.websocket = ws;
            };
            
            ws.onmessage = function(event) {
                const data = JSON.parse(event.data);
                handleWebSocketMessage(data);
            };
            
            ws.onclose = function() {
                console.log('WebSocket disconnected');
                // Attempt to reconnect after 5 seconds
                setTimeout(setupWebSocket, 5000);
            };
            
            ws.onerror = function(error) {
                console.error('WebSocket error:', error);
            };
        } catch (error) {
            console.log('WebSocket not available, using polling');
        }
    }

    /**
     * Handle WebSocket messages
     */
    function handleWebSocketMessage(data) {
        switch (data.type) {
            case 'order_update':
                handleOrderUpdate(data.payload);
                break;
            case 'driver_location':
                handleDriverLocationUpdate(data.payload);
                break;
            case 'notification':
                showNotification(data.payload.message, data.payload.type);
                break;
            default:
                console.log('Unknown WebSocket message:', data);
        }
    }

    /**
     * Handle order status updates
     */
    function handleOrderUpdate(orderData) {
        // Update UI based on order status
        const event = new CustomEvent('orderUpdated', {
            detail: orderData
        });
        document.dispatchEvent(event);
        
        // Show notification
        showNotification(`Order #${orderData.id} ${orderData.status}`, 'info');
    }

    /**
     * Handle driver location updates
     */
    function handleDriverLocationUpdate(locationData) {
        const event = new CustomEvent('driverLocationUpdated', {
            detail: locationData
        });
        document.dispatchEvent(event);
    }

    /**
     * Request notification permission
     */
    function requestNotificationPermission() {
        if (Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    console.log('Notification permission granted');
                }
            });
        }
    }

    /**
     * Show push notification
     */
    function showPushNotification(title, options = {}) {
        if (Notification.permission === 'granted' && 'serviceWorker' in navigator) {
            navigator.serviceWorker.ready.then(registration => {
                registration.showNotification(title, {
                    icon: '/assets/images/icon-192.png',
                    badge: '/assets/images/badge-72.png',
                    ...options
                });
            });
        }
    }

    /**
     * Show in-app notification
     */
    function showNotification(message, type = 'info', duration = 5000) {
        const notification = createNotificationElement(message, type);
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => notification.showNotification(), 100);
        
        // Auto remove
        setTimeout(() => {
            notification.hideNotification();
            setTimeout(() => notification.remove(), 300);
        }, duration);
        
        // Store in state
        RideMax.state.notifications.push({
            id: Date.now(),
            message,
            type,
            timestamp: new Date()
        });
    }

    /**
     * Create notification element
     */
    function createNotificationElement(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} notification-toast`;
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${getNotificationIcon(type)} me-2"></i>
                <span>${message}</span>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;
        
        // Add styles
        Object.assign(notification.style, {
            position: 'fixed',
            top: '80px',
            right: '20px',
            zIndex: '9999',
            minWidth: '300px',
            maxWidth: '400px',
            transform: 'translateX(400px)',
            transition: 'transform 0.3s ease'
        });
        
        // Custom show/hide methods for animations
        notification.showNotification = function() {
            this.style.transform = 'translateX(0)';
        };
        
        notification.hideNotification = function() {
            this.style.transform = 'translateX(400px)';
        };
        
        return notification;
    }

    /**
     * Get notification icon based on type
     */
    function getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-triangle',
            warning: 'exclamation-circle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    /**
     * Update active navigation
     */
    function updateActiveNavigation() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link, .bottom-nav-link, .list-group-item');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && currentPath.includes(href.replace('../', ''))) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }

    /**
     * Update connection status indicator
     */
    function updateConnectionStatus() {
        let statusElement = document.querySelector('.connection-status');
        
        if (!statusElement) {
            statusElement = document.createElement('div');
            statusElement.className = 'connection-status';
            document.body.appendChild(statusElement);
        }
        
        if (RideMax.state.isOnline) {
            statusElement.classList.add('online');
            statusElement.classList.remove('offline');
            statusElement.textContent = 'Online';
            statusElement.style.display = 'none';
        } else {
            statusElement.classList.add('offline');
            statusElement.classList.remove('online');
            statusElement.textContent = 'Offline';
            statusElement.style.display = 'block';
        }
    }

    /**
     * Sync offline data when connection restored
     */
    function syncOfflineData() {
        const offlineData = getOfflineData();
        if (offlineData.length > 0) {
            console.log('Syncing offline data:', offlineData);
            // Implement offline data sync logic
        }
    }

    /**
     * Get offline data from localStorage
     */
    function getOfflineData() {
        try {
            return JSON.parse(localStorage.getItem('ridemax_offline_data') || '[]');
        } catch (error) {
            console.error('Error reading offline data:', error);
            return [];
        }
    }

    /**
     * Handle visibility change
     */
    function handleVisibilityChange() {
        if (document.visibilityState === 'visible') {
            // App became visible, check for updates
            if (RideMax.state.isOnline && isLoggedIn()) {
                updatePageData();
            }
        }
    }

    /**
     * Handle back button
     */
    function handleBackButton(event) {
        // Custom back button handling if needed
        console.log('Back button pressed');
    }

    /**
     * Set button loading state
     */
    function setButtonLoading(button, loading) {
        if (loading) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || button.innerHTML;
        }
    }

    /**
     * Utility functions
     */

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Check if user is logged in
    function isLoggedIn() {
        return document.body.classList.contains('logged-in') || 
               document.querySelector('.navbar') !== null;
    }

    // Email validation
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // Phone validation (Indonesian)
    function isValidPhone(phone) {
        return /^(\+62|62|0)[0-9]{9,13}$/.test(phone.replace(/\s/g, ''));
    }

    // Show field error
    function showFieldError(field, message) {
        clearFieldValidation(field);
        
        field.classList.add('is-invalid');
        
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        
        field.parentNode.appendChild(feedback);
    }

    // Show field success
    function showFieldSuccess(field) {
        clearFieldValidation(field);
        field.classList.add('is-valid');
    }

    // Clear field validation
    function clearFieldValidation(field) {
        field.classList.remove('is-invalid', 'is-valid');
        const feedback = field.parentNode.querySelector('.invalid-feedback, .valid-feedback');
        if (feedback) {
            feedback.remove();
        }
    }

    // Format currency
    function formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    }

    // Format date
    function formatDate(date, options = {}) {
        return new Intl.DateTimeFormat('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            ...options
        }).format(new Date(date));
    }

    // Export utility functions to global scope
    window.RideMax.utils = {
        debounce,
        isValidEmail,
        isValidPhone,
        formatCurrency,
        formatDate,
        showNotification,
        setButtonLoading,
        calculateDistance
    };

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (RideMax.locationWatchId) {
            navigator.geolocation.clearWatch(RideMax.locationWatchId);
        }
        
        if (RideMax.websocket) {
            RideMax.websocket.close();
        }
    });

})();

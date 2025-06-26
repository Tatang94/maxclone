# RideMax - PHP Mobile Super App

A comprehensive ride-hailing super app built with PHP, featuring mobile-optimized interfaces for passengers, drivers, and administrators. Designed with a mobile-first approach to provide seamless ride booking and management experience.

## 🚗 Features

### For Passengers
- **Instant Ride Booking**: Book rides with multiple vehicle types (Economy, Comfort, Premium)
- **Real-time Tracking**: Track your driver's location and estimated arrival time
- **Order History**: View and manage your ride history
- **Multiple Payment Methods**: Support for cash, card, and digital wallet payments
- **Scheduled Rides**: Book rides for later with scheduling options
- **Rating System**: Rate drivers and provide feedback

### For Drivers
- **Driver Dashboard**: Comprehensive dashboard with earnings and ride statistics
- **Online/Offline Toggle**: Control availability with simple toggle
- **Ride Management**: Accept, start, and complete rides efficiently
- **Real-time Notifications**: Get notified of new ride requests instantly
- **Earnings Tracking**: Monitor daily, weekly, and monthly earnings
- **Navigation Integration**: Built-in navigation support for Google Maps and Waze

### For Administrators
- **Admin Dashboard**: Complete overview of system performance and statistics
- **User Management**: Manage passengers and drivers with detailed profiles
- **Order Management**: Monitor and manage all ride requests and assignments
- **System Settings**: Configure pricing, notifications, and application settings
- **Analytics**: View charts and reports on app usage and revenue
- **Database Tools**: Backup, optimize, and maintain the database

## 🛠 Technology Stack

- **Backend**: PHP 7.4+ with PDO for secure database operations
- **Database**: MySQL/MariaDB with optimized indexing
- **Frontend**: HTML5, CSS3, Bootstrap 5 for responsive design
- **JavaScript**: Vanilla JS with modern ES6+ features
- **Security**: Password hashing, SQL injection prevention, XSS protection
- **Real-time**: WebSocket support for live updates
- **PWA**: Service Worker support for offline functionality

## 📱 Mobile-First Design

- Responsive design optimized for smartphones and tablets
- Touch-friendly interface with gesture support
- Bottom navigation for easy thumb navigation
- Optimized loading times and smooth animations
- Progressive Web App (PWA) capabilities

## 🚀 Quick Setup

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or MariaDB 10.3+
- Web server (Apache/Nginx)
- Modern web browser

### Installation Steps

1. **Clone or Download the Application**
   ```bash
   # If using git
   git clone <repository-url> ridemax
   cd ridemax
   
   # Or download and extract the files to your web directory
   ```

2. **Database Setup**
   ```bash
   # Create database and import schema
   mysql -u root -p
   source database/db.sql
   ```

3. **Configure Database Connection**
   ```bash
   # Set environment variables or update includes/db.php
   export DB_HOST=localhost
   export DB_NAME=ridemax_db
   export DB_USER=your_username
   export DB_PASS=your_password
   ```

4. **Set Permissions**
   ```bash
   # Ensure web server can read files
   chmod -R 755 .
   chmod -R 777 backups/ # For database backups
   ```

5. **Configure Web Server**
   - Point document root to the application directory
   - Ensure mod_rewrite is enabled (for Apache)
   - Configure SSL certificate for production

6. **Access the Application**
   - Open your web browser and navigate to your domain
   - Login with demo credentials or create new accounts

## 🔐 Demo Credentials

### Admin Access
- **Email**: admin@demo.com
- **Password**: password

### Passenger Account
- **Email**: user@demo.com
- **Password**: password

### Driver Account
- **Email**: driver@demo.com
- **Password**: password

## 📊 File Structure


# RideMax - PHP Mobile Super App

## Overview

RideMax is a comprehensive ride-hailing super app built with PHP, featuring mobile-optimized interfaces for passengers, drivers, and administrators. The application follows a mobile-first design approach and provides a complete ride-booking ecosystem with real-time features.

## System Architecture

### Backend Architecture
- **Core Technology**: PHP 7.4+ with PDO for secure database operations
- **Architecture Pattern**: MVC-inspired structure with separate process handlers
- **Database Layer**: MySQL/MariaDB with optimized indexing and prepared statements
- **Session Management**: PHP sessions with secure authentication and remember tokens
- **Security**: Password hashing, SQL injection prevention, XSS protection, and rate limiting

### Frontend Architecture
- **Design System**: Mobile-first responsive design using Bootstrap 5
- **JavaScript**: Vanilla JS with modern ES6+ features for enhanced interactivity
- **CSS Framework**: Custom CSS with CSS variables for theming and responsive design
- **PWA Support**: Service Worker support for offline functionality
- **Real-time Updates**: WebSocket support for live tracking and notifications

### Data Storage
- **Primary Database**: SQLite for Replit compatibility
- **Connection Management**: PDO with automatic database initialization
- **Schema Design**: Normalized database structure with proper indexing
- **File-based Storage**: SQLite database file with automatic setup

## Key Components

### User Management System
- **Multi-role Authentication**: Support for passengers, drivers, and administrators
- **Registration/Login**: Secure user registration with email and phone validation
- **Profile Management**: User profiles with driver-specific information
- **Session Security**: Secure session handling with remember me functionality

### Ride Booking System
- **Order Management**: Complete ride booking workflow from request to completion
- **Real-time Tracking**: Live driver location updates and ETA calculations
- **Vehicle Types**: Multiple vehicle categories (Economy, Comfort, Premium)
- **Payment Integration**: Support for multiple payment methods (cash, card, digital wallet)
- **Scheduling**: Ability to schedule rides for later with date/time selection

### Driver Management
- **Driver Dashboard**: Comprehensive interface for driver operations
- **Online/Offline Status**: Real-time availability toggle
- **Earnings Tracking**: Daily, weekly, and monthly earnings reports
- **Ride Management**: Accept, start, and complete ride workflows
- **Location Updates**: Real-time location broadcasting for passenger tracking

### Administrative Panel
- **Admin Dashboard**: System overview with key metrics and analytics
- **User Management**: CRUD operations for managing passengers and drivers
- **Order Management**: Monitor and manage all ride requests and assignments
- **System Settings**: Configure pricing, notifications, and application parameters
- **Analytics**: Charts and reports on app usage, revenue, and performance

## Data Flow

### Ride Booking Flow
1. **Passenger Request**: User submits ride request with pickup/destination
2. **Driver Matching**: System finds available drivers based on location and vehicle type
3. **Driver Assignment**: Selected driver receives notification and can accept/decline
4. **Real-time Updates**: Both parties receive status updates throughout the journey
5. **Completion**: Payment processing and rating system upon ride completion

### Authentication Flow
1. **Login/Registration**: User credentials validated against database
2. **Session Creation**: Secure session established with user roles
3. **Authorization**: Role-based access control for different app sections
4. **Token Management**: Remember me tokens for persistent login

## External Dependencies

### CDN Resources
- **Bootstrap 5.3.0**: Frontend framework for responsive design
- **Font Awesome 6.4.0**: Icon library for UI elements
- **Inter Font**: Google Fonts for typography

### Third-party Integrations
- **Google Maps API**: Map integration for location services (configurable)
- **Waze Navigation**: Alternative navigation option for drivers
- **Payment Gateways**: Extensible payment system architecture

## Deployment Strategy

### Environment Setup
- **PHP Environment**: PHP 7.4+ with required extensions (PDO, MySQL)
- **Web Server**: Apache/Nginx configuration for PHP applications
- **Database**: MySQL/MariaDB with proper user permissions
- **File Permissions**: Proper file system permissions for security

### Configuration Management
- **Environment Variables**: Database credentials and API keys
- **Error Handling**: Comprehensive error logging and user-friendly error pages
- **Security Headers**: XSS protection, content type validation, and frame options

### Monitoring and Maintenance
- **Database Optimization**: Regular database maintenance and backup procedures
- **Performance Monitoring**: Built-in analytics for system performance tracking
- **User Activity Logging**: Comprehensive logging system for audit trails

## Changelog

- June 25, 2025. Initial setup and migration to Replit environment
- June 25, 2025. Database migrated from MySQL to PostgreSQL for Replit compatibility
- June 26, 2025. Final migration from Replit Agent to standard Replit environment
- June 26, 2025. Database converted to SQLite for optimal Replit compatibility
- June 26, 2025. Added digital wallet system with PayDisini QRIS integration
- June 26, 2025. Enhanced payment system to support balance-based transactions
- June 25, 2025. Added dedicated admin login page at /admin/login.php with enhanced security
- June 25, 2025. Fixed database schema inconsistencies and API endpoints
- June 25, 2025. Enhanced booking form with enlarged input fields and better mobile UX
- June 25, 2025. Successfully deployed with external URL: https://526553d3-57a4-42bb-aee4-ae7552d9ee4e-00-1ddcssyoat2r.picard.replit.dev/

## Recent Changes

✓ Migrated database configuration from MySQL to PostgreSQL
✓ Created PostgreSQL database schema with all tables and relationships
✓ Set up default admin user (admin@ridemax.com)
✓ Added dedicated admin login interface at /admin/login.php
✓ Fixed activity logging database column mismatch
✓ Verified all API endpoints are working properly
✓ Maintained security practices with proper input validation
✓ Cleared all demo data - ready for production use
✓ Reset database sequences for clean start
✓ Fixed admin dashboard authentication and API endpoints
✓ Updated all database queries for PostgreSQL compatibility
✓ Added fallback data for charts when no real data exists
✓ Implemented real-time admin dashboard functionality
✓ Converted entire application interface to Indonesian language
✓ Updated all form labels, buttons, and navigation to Indonesian
✓ Translated admin dashboard and user interface elements
✓ Maintained consistent Indonesian terminology throughout app
✓ Completed migration from Replit Agent to standard Replit environment
✓ Fixed JavaScript errors and implemented proper service worker
✓ All core functionality working properly in Indonesian
✓ Added Google Maps integration with route display from pickup to destination
✓ Implemented dynamic pricing based on distance calculation
✓ Enhanced passenger booking interface with real-time map updates
✓ Added garis rute (route line) visualization on map
✓ Integrated location autocomplete for Indonesian addresses
✓ Enlarged booking form input fields for better mobile usability (52px height, 16px font)
✓ Enhanced form styling with proper padding and focus states
✓ Configured server for external access and deployment
✓ Successfully deployed to Replit with external URL access
✓ Completed migration from Replit Agent to standard Replit environment
✓ Fixed Service Worker caching issues that caused blank page display
✓ Implemented network-first strategy for dynamic PHP content
✓ Added comprehensive debugging tools for troubleshooting
✓ Verified all API endpoints are secure and functioning properly
✓ Confirmed geolocation services working correctly for Indonesian users
✓ Successfully migrated from Replit Agent to standard Replit environment
✓ Converted database from PostgreSQL to SQLite for better compatibility
✓ Set up automated database initialization with proper schema
✓ Verified all application endpoints working correctly
✓ Confirmed Indonesian language interface fully functional
✓ Application now running cleanly on PHP 8.2 with SQLite backend
✓ Added digital wallet system with QRIS payment integration via PayDisini API
✓ Implemented balance top-up functionality with QR code generation
✓ Enhanced order booking to support wallet payments alongside cash
✓ Created comprehensive transaction history and balance management
✓ Integrated wallet balance display throughout the application interface
✓ Fixed PayDisini API integration with proper error handling and fallback system
✓ Created comprehensive user profile management system with password updates
✓ Implemented help and support system with FAQ and emergency contacts
✓ Enhanced navigation with functional profile and help page links
✓ Removed demo mode and configured PayDisini for production use
✓ Direct integration with PayDisini API for authentic QRIS payments
✓ Updated API key to ff79be802563e5dc1311c227a72d17c1 for PayDisini production
✓ Enhanced error handling for PayDisini account status monitoring
✓ System ready for production with proper PayDisini integration
✓ Integrated Midtrans payment gateway with Snap.js
✓ Added support for multiple payment methods (Credit Card, VA, E-Wallet, QRIS)
✓ Implemented secure payment notification handling with signature verification
✓ Created professional payment interface with Midtrans Snap
✓ Successfully migrated from Replit Agent to standard Replit environment
✓ Fixed Midtrans integration with QRIS as primary payment option
✓ Updated payment interface to prioritize QRIS payments
✓ Verified all core functionality working in new environment
✓ Updated Midtrans configuration to production mode with live API keys
✓ Fixed database schema errors for activity_logs table
✓ Fixed topup_process.php reference errors for Midtrans integration
✓ Payment system now using live Midtrans with real transaction processing

## User Preferences

Preferred communication style: Simple, everyday language.
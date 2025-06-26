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

✓ Successfully completed migration from Replit Agent to standard Replit environment (June 26, 2025)
✓ Fixed SQLite compatibility issues - replaced NOW() with datetime('now') function
✓ Corrected activity_logs table column names from 'details' to 'description'
✓ Created missing PayDisini integration file for QRIS payment processing
✓ Verified core application functionality through comprehensive testing:
  - User registration and authentication system working properly
  - Order booking system with price calculation functioning
  - Admin dashboard API endpoints responding correctly
  - SQLite database operations stable and secure
✓ Confirmed Indonesian language interface fully functional across all pages
✓ Payment system integrated with PayDisini API for QRIS transactions
✓ Session management and security features operational
✓ Server running stable on PHP 8.2 with port 5000 accessibility
✓ All progress tracker items completed for Replit environment migration
✓ Added PostgreSQL database with hybrid SQLite fallback system (June 26, 2025)
✓ Configured automatic database detection and connection switching
✓ Created complete PostgreSQL schema with all required tables and relationships
✓ Implemented database-agnostic functions for cross-compatibility
✓ PostgreSQL now serves as primary database with enhanced performance
✓ Removed all foreign key constraints from database schema per user request (June 26, 2025)
✓ Updated PostgreSQL schema to use simple integer references without foreign keys
✓ Database structure simplified for better compatibility and user preference
✓ All database operations working without referential integrity constraints

## User Preferences

Preferred communication style: Simple, everyday language.
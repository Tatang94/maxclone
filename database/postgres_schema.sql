-- RideMax Super App Database Schema - PostgreSQL Version
-- Mobile-first ride-hailing application
-- Migrated to PostgreSQL for Replit compatibility

-- Enable UUID extension for better primary keys
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Create enum types
CREATE TYPE user_type_enum AS ENUM ('user', 'admin');
CREATE TYPE user_status_enum AS ENUM ('active', 'inactive', 'suspended');
CREATE TYPE vehicle_type_enum AS ENUM ('economy', 'comfort', 'premium');
CREATE TYPE order_status_enum AS ENUM ('pending', 'accepted', 'in_progress', 'completed', 'cancelled');
CREATE TYPE payment_method_enum AS ENUM ('cash', 'card', 'digital_wallet');
CREATE TYPE payment_status_enum AS ENUM ('pending', 'completed', 'failed', 'refunded');

-- Table structure for table users
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type user_type_enum NOT NULL DEFAULT 'user',
    is_driver BOOLEAN NOT NULL DEFAULT FALSE,
    status user_status_enum NOT NULL DEFAULT 'active',
    email_verified_at TIMESTAMP NULL,
    phone_verified_at TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table drivers
CREATE TABLE drivers (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    license_number VARCHAR(50) NOT NULL UNIQUE,
    vehicle_make VARCHAR(50) NOT NULL,
    vehicle_model VARCHAR(50) NOT NULL,
    vehicle_year INTEGER,
    vehicle_plate VARCHAR(20) NOT NULL UNIQUE,
    vehicle_color VARCHAR(30),
    vehicle_type vehicle_type_enum NOT NULL DEFAULT 'economy',
    is_online BOOLEAN NOT NULL DEFAULT FALSE,
    current_lat DECIMAL(10, 8) NULL,
    current_lng DECIMAL(11, 8) NULL,
    rating DECIMAL(3, 2) NOT NULL DEFAULT 5.00,
    total_rides INTEGER NOT NULL DEFAULT 0,
    total_earnings DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table orders
CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id),
    driver_id INTEGER NULL REFERENCES drivers(id),
    pickup_address TEXT NOT NULL,
    pickup_lat DECIMAL(10, 8) NOT NULL,
    pickup_lng DECIMAL(11, 8) NOT NULL,
    destination_address TEXT NOT NULL,
    destination_lat DECIMAL(10, 8) NOT NULL,
    destination_lng DECIMAL(11, 8) NOT NULL,
    vehicle_type vehicle_type_enum NOT NULL DEFAULT 'economy',
    passenger_count INTEGER NOT NULL DEFAULT 1,
    scheduled_at TIMESTAMP NULL,
    estimated_fare DECIMAL(8, 2) NOT NULL,
    actual_fare DECIMAL(8, 2) NULL,
    distance_km DECIMAL(6, 2) NULL,
    duration_minutes INTEGER NULL,
    status order_status_enum NOT NULL DEFAULT 'pending',
    payment_method payment_method_enum NOT NULL DEFAULT 'cash',
    special_instructions TEXT NULL,
    driver_arrived_at TIMESTAMP NULL,
    trip_started_at TIMESTAMP NULL,
    trip_completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table payments
CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL REFERENCES orders(id),
    amount DECIMAL(8, 2) NOT NULL,
    method payment_method_enum NOT NULL,
    status payment_status_enum NOT NULL DEFAULT 'pending',
    transaction_id VARCHAR(100) NULL,
    gateway_response TEXT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table ratings
CREATE TABLE ratings (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL REFERENCES orders(id),
    user_id INTEGER NOT NULL REFERENCES users(id),
    driver_id INTEGER NOT NULL REFERENCES drivers(id),
    user_rating INTEGER CHECK (user_rating >= 1 AND user_rating <= 5),
    driver_rating INTEGER CHECK (driver_rating >= 1 AND driver_rating <= 5),
    user_comment TEXT NULL,
    driver_comment TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table remember_tokens
CREATE TABLE remember_tokens (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table activity_logs
CREATE TABLE activity_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NULL,
    ip_address INET NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table system_settings
CREATE TABLE system_settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_type VARCHAR(20) NOT NULL DEFAULT 'string',
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_phone ON users(phone);
CREATE INDEX idx_users_user_type ON users(user_type);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_is_driver ON users(is_driver);

CREATE INDEX idx_drivers_user_id ON drivers(user_id);
CREATE INDEX idx_drivers_is_online ON drivers(is_online);
CREATE INDEX idx_drivers_vehicle_type ON drivers(vehicle_type);
CREATE INDEX idx_drivers_location ON drivers(current_lat, current_lng);

CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_driver_id ON orders(driver_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created_at ON orders(created_at);
CREATE INDEX idx_orders_scheduled_at ON orders(scheduled_at);

CREATE INDEX idx_payments_order_id ON payments(order_id);
CREATE INDEX idx_payments_status ON payments(status);

CREATE INDEX idx_ratings_order_id ON ratings(order_id);
CREATE INDEX idx_ratings_user_id ON ratings(user_id);
CREATE INDEX idx_ratings_driver_id ON ratings(driver_id);

CREATE INDEX idx_remember_tokens_user_id ON remember_tokens(user_id);
CREATE INDEX idx_remember_tokens_token ON remember_tokens(token);
CREATE INDEX idx_remember_tokens_expires_at ON remember_tokens(expires_at);

CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_action ON activity_logs(action);
CREATE INDEX idx_activity_logs_created_at ON activity_logs(created_at);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('app_name', 'RideMax', 'string', 'Application name'),
('app_version', '1.0.0', 'string', 'Application version'),
('currency', 'USD', 'string', 'Default currency'),
('base_fare', '2.50', 'decimal', 'Base fare amount'),
('per_km_rate', '1.25', 'decimal', 'Rate per kilometer'),
('per_minute_rate', '0.35', 'decimal', 'Rate per minute'),
('booking_fee', '0.50', 'decimal', 'Booking fee'),
('cancellation_fee', '2.00', 'decimal', 'Cancellation fee'),
('commission_rate', '20', 'decimal', 'Commission rate percentage'),
('max_search_radius', '10', 'number', 'Maximum driver search radius in km'),
('password_min_length', '6', 'number', 'Minimum password length'),
('max_login_attempts', '5', 'number', 'Maximum login attempts'),
('session_timeout', '120', 'number', 'Session timeout in minutes'),
('lockout_duration', '15', 'number', 'Account lockout duration in minutes'),
('require_email_verification', 'false', 'boolean', 'Require email verification'),
('require_phone_verification', 'false', 'boolean', 'Require phone verification'),
('enable_two_factor', 'false', 'boolean', 'Enable two-factor authentication');

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, user_type, status, created_at) VALUES
('Administrator', 'admin@ridemax.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', CURRENT_TIMESTAMP);

-- Create functions for updating timestamps
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create triggers for updating timestamps
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_drivers_updated_at BEFORE UPDATE ON drivers FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_orders_updated_at BEFORE UPDATE ON orders FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_payments_updated_at BEFORE UPDATE ON payments FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_system_settings_updated_at BEFORE UPDATE ON system_settings FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
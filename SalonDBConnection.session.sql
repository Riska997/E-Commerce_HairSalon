
-- Create Customers table
CREATE TABLE IF NOT EXISTS customers (
    CustomerID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    ContactDetails VARCHAR(255),
    Email VARCHAR(100) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Appointments table
CREATE TABLE IF NOT EXISTS appointments (
    AppointmentID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerID INT NOT NULL,
    Date DATE NOT NULL,
    Time TIME NOT NULL,
    Status ENUM('Booked', 'Cancelled', 'Completed') DEFAULT 'Booked',
    PaymentID INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CustomerID) REFERENCES customers(CustomerID) ON DELETE CASCADE
);


-- Create Services table
CREATE TABLE IF NOT EXISTS services (
    ServiceID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Description TEXT,
    Price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Payments table
CREATE TABLE IF NOT EXISTS payments (
    PaymentID INT AUTO_INCREMENT PRIMARY KEY,
    AppointmentID INT,
    PaymentMethod ENUM('CreditCard', 'Cash', 'Online') NOT NULL,
    Amount DECIMAL(10, 2) NOT NULL,
    Status ENUM('Pending', 'Completed', 'Refunded') DEFAULT 'Pending',
    Date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Admins table
CREATE TABLE IF NOT EXISTS admins (
    AdminID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Role ENUM('Manager', 'Staff') DEFAULT 'Staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Appointment_Service table (for many-to-many relationship between appointments and services)
CREATE TABLE IF NOT EXISTS appointment_service (
    AppointmentID INT NOT NULL,
    ServiceID INT NOT NULL,
    PRIMARY KEY (AppointmentID, ServiceID),
    FOREIGN KEY (AppointmentID) REFERENCES appointments(AppointmentID) ON DELETE CASCADE,
    FOREIGN KEY (ServiceID) REFERENCES services(ServiceID) ON DELETE CASCADE
);

-- Add the PaymentID foreign key to the Appointments table
ALTER TABLE appointments
ADD CONSTRAINT fk_payment
FOREIGN KEY (PaymentID) REFERENCES payments(PaymentID) ON DELETE SET NULL;

ALTER TABLE services
ADD ImagePath VARCHAR(255);

-- Update Customers table with NOT NULL constraints and default values
ALTER TABLE customers
MODIFY COLUMN Name VARCHAR(100) NOT NULL,
MODIFY COLUMN Email VARCHAR(100) NOT NULL,
MODIFY COLUMN Password VARCHAR(255) NOT NULL,
MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL;

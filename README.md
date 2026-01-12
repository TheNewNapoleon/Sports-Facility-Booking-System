# Sport Club Facilities Booking System

A comprehensive web-based facility booking system for TARUMT Sabah Sports Club, allowing students and staff to book sports facilities online with ease.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [Project Structure](#project-structure)
- [Usage](#usage)
- [User Roles](#user-roles)
- [API Endpoints](#api-endpoints)
- [Screenshots](#screenshots)
- [Contributing](#contributing)
- [License](#license)

## 🎯 Overview

The Sport Club Facilities Booking System is a PHP-based web application designed to streamline the process of booking sports facilities at TARUMT Sabah campus. The system provides an intuitive interface for users to view available facilities, check availability, make bookings, manage their reservations, and provide feedback.

## ✨ Features

### User Features
- **Facility Booking**: Book various sports facilities (Basketball, Badminton, Futsal, Gym, Swimming Pool, Ping Pong)
- **Real-time Availability**: Check facility availability in real-time
- **Booking Management**: View, filter, and cancel bookings
- **User Profile**: Manage personal information, upload profile pictures, change passwords
- **Dashboard**: View announcements and upcoming events
- **Feedback System**: Submit feedback and view admin responses
- **Notifications**: Receive reminders for upcoming bookings
- **Search Functionality**: Search through bookings, feedback, and announcements
- **Responsive Design**: Mobile-friendly interface

### Admin Features
- **Admin Dashboard**: Comprehensive admin panel for managing the system
- **Booking Management**: Approve, reject, or manage user bookings
- **User Management**: Manage user accounts and blacklist users
- **Announcements**: Create and manage announcements
- **Events Management**: Schedule and manage events
- **Feedback Management**: Review and respond to user feedback
- **Reports**: Generate booking and facility usage reports

## 🛠 Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Libraries**: 
  - Font Awesome 6.5.0 (Icons)
  - Google Fonts (Poppins)
- **Server**: XAMPP (Apache + MySQL)
- **Architecture**: MVC-like structure

## 📦 Installation

### Prerequisites
- XAMPP (or any PHP development environment)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser (Chrome, Firefox, Edge, Safari)

### Step-by-Step Installation

1. **Clone or Download the Repository**
   ```bash
   git clone <repository-url>
   cd SportClubFacilitiesBooking
   ```

2. **Place Files in XAMPP htdocs**
   - Copy the project folder to `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac)

3. **Start XAMPP Services**
   - Open XAMPP Control Panel
   - Start Apache and MySQL services

4. **Import Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `campus_facility_booking`
   - Import the SQL file (`book (1).sql`) into the database

5. **Configure Database Connection**
   - Edit `db.php` file
   - Update database credentials:
     ```php
     $DB_HOST = 'localhost:3307';  // Change port if needed
     $DB_NAME = 'campus_facility_booking';
     $DB_USER = 'root';
     $DB_PASS = 'your_password';  // Your MySQL password
     ```

6. **Access the Application**
   - Open browser and navigate to: `http://localhost/SportClubFacilitiesBooking/`

## 🗄 Database Setup

### Database Schema

The system uses the following main tables:

- **users**: User accounts and profiles
- **venues**: Sports facilities/venues
- **courts**: Individual courts within venues
- **bookings**: Booking records
- **feedback**: User feedback and admin responses
- **announcements**: System announcements
- **events**: Scheduled events

### Database Configuration

Edit `db.php` to match your MySQL configuration:

```php
$DB_HOST = 'localhost:3307';  // Host and port
$DB_NAME = 'campus_facility_booking';  // Database name
$DB_USER = 'root';  // Database username
$DB_PASS = 'your_password';  // Database password
```

## ⚙️ Configuration

### Session Configuration
- Sessions are managed via PHP `session_start()`
- User authentication is handled through session variables

### File Upload Configuration
- Profile pictures are stored in `images/avatar/`
- Maximum file size and allowed types are configured in PHP

## 📁 Project Structure

```
SportClubFacilitiesBooking/
│
├── css/                          # Stylesheets
│   ├── global.css                # Global styles
│   ├── dashboard.css             # Dashboard styles
│   ├── navbar.css                # Navigation styles
│   ├── bookings.css              # Booking list styles
│   ├── feedback.css              # Feedback styles
│   ├── profile.css               # Profile styles
│   └── ...
│
├── images/                       # Image assets
│   ├── avatar/                   # User avatars
│   ├── logo1.png                 # Logo
│   └── ...
│
├── admin_dashboard.php           # Admin panel
├── booking_facilities.php        # Facility booking page
├── booking_list.php              # User booking list
├── dashboard.php                 # User dashboard
├── feedback.php                  # Feedback page
├── profile.php                   # User profile
├── Login.php                     # Login page
├── index.php                     # Homepage
├── db.php                        # Database configuration
├── save_booking.php              # Booking processing
├── cancel_booking.php            # Booking cancellation
├── faqai_backend.php             # AI assistant backend
├── script.js                     # Main JavaScript file
└── README.md                     # This file
```

## 🚀 Usage

### For Users

1. **Registration/Login**
   - Navigate to the login page
   - Register a new account or login with existing credentials

2. **Browse Facilities**
   - View available facilities on the homepage
   - Click on a facility to see details

3. **Make a Booking**
   - Go to "Booking Facilities"
   - Select facility, court, date, and time slot
   - Confirm booking

4. **Manage Bookings**
   - View all bookings in "Booking List"
   - Filter by status or search
   - Cancel bookings (if allowed)

5. **Submit Feedback**
   - Go to "Feedback" page
   - Fill out feedback form
   - View admin responses

### For Administrators

1. **Access Admin Dashboard**
   - Login with admin credentials
   - Navigate to admin dashboard

2. **Manage Bookings**
   - Approve or reject pending bookings
   - View booking statistics

3. **Manage Users**
   - View user list
   - Blacklist users if needed

4. **Create Announcements**
   - Post announcements visible to all users

5. **Schedule Events**
   - Create events that block facility availability

## 👥 User Roles

- **Student**: Can book facilities, view bookings, submit feedback
- **Staff**: Same privileges as students
- **Admin**: Full system access including user management, booking approval, announcements, and reports

## 🔌 API Endpoints

### Booking Endpoints
- `save_booking.php` - Process new bookings
- `cancel_booking.php` - Cancel existing bookings
- `check_availability.php` - Check facility availability

### Data Endpoints
- `get_venues.php` - Fetch available venues
- `get_venues_autocomplete.php` - Venue autocomplete data
- `dashboard_search.php` - Search announcements/events
- `feedback_search.php` - Search feedback

### Admin Endpoints
- `admin_dashboard.php` - Admin panel
- `reports_data.php` - Generate reports

## 📸 Screenshots

### Main Features
- **Homepage**: Welcome page with facility overview
- **Booking Page**: Interactive facility booking interface
- **Dashboard**: User dashboard with announcements and events
- **Booking List**: Manage all bookings with filters
- **Profile**: User profile management
- **Feedback**: Submit and view feedback
- **Admin Panel**: Comprehensive admin dashboard

## 🔒 Security Features

- Password hashing using PHP `password_hash()`
- SQL injection prevention using prepared statements
- XSS protection with `htmlspecialchars()`
- Session-based authentication
- User role-based access control
- File upload validation

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check MySQL service is running
   - Verify database credentials in `db.php`
   - Ensure database exists

2. **Session Issues**
   - Check PHP session configuration
   - Ensure cookies are enabled in browser

3. **File Upload Errors**
   - Check `images/avatar/` directory permissions
   - Verify PHP upload settings in `php.ini`

4. **Page Not Found**
   - Verify Apache is running
   - Check file paths and URLs

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is developed for TARUMT Sabah Sports Club. All rights reserved.

## 👨‍💻 Development Team

Developed for TARUMT Sabah Sports Club Facility Management.

## 📞 Support

For support and inquiries:
- Email: sabah@tarc.edu.my
- Facebook: [TAR UMT Sabah Branch](https://www.facebook.com/tarumtsabah)
- Instagram: @tarumtsabah

## 🔄 Version History

- **v1.0** - Initial release with core booking functionality
- Features: Facility booking, user management, feedback system, admin dashboard

## 📚 Additional Resources

- [PHP Documentation](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [XAMPP Documentation](https://www.apachefriends.org/docs/)

---

**Note**: This system is designed for TARUMT Sabah campus use. Ensure proper configuration and security measures are in place before deploying to production.


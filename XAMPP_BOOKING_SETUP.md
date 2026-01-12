# XAMPP Booking Setup Guide

This guide will help you set up and troubleshoot the booking system in XAMPP.

## ✅ Quick Setup Checklist

1. **Start XAMPP Services**
   - Open XAMPP Control Panel
   - Start **Apache** (should turn green)
   - Start **MySQL** (should turn green)

2. **Verify Database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Check if database `campus_facility_booking` exists
   - If not, create it: `CREATE DATABASE campus_facility_booking;`

3. **Create Bookings Table**
   - Open phpMyAdmin
   - Select database `campus_facility_booking`
   - Go to SQL tab
   - Copy and paste contents from `setup_bookings_table.sql`
   - Click "Go" to execute

4. **Test Database Connection**
   - Open browser: http://localhost/SportClubFacilitiesBooking/test_db_connection.php
   - Verify all checks pass (green checkmarks)

5. **Test Booking**
   - Login to your account
   - Go to Booking Facilities page
   - Try making a booking
   - Check booking_list.php to see if it appears

## 🔍 Troubleshooting

### Problem: "Database Connection Failed"

**Solutions:**
1. Check XAMPP MySQL is running (green in Control Panel)
2. Verify database name in `db.php` matches your database
3. Check MySQL username/password in `db.php` (default: root, no password)
4. Test connection: http://localhost/SportClubFacilitiesBooking/test_db_connection.php

### Problem: "Bookings table does not exist"

**Solution:**
1. Run the SQL script: `setup_bookings_table.sql` in phpMyAdmin
2. Or manually create table using SQL from the test page

### Problem: "Booking not saving"

**Check:**
1. Open browser Developer Tools (F12)
2. Go to Console tab
3. Try making a booking
4. Look for error messages
5. Check Network tab for failed requests

**Common Issues:**
- **403 Forbidden**: Check file permissions in XAMPP
- **500 Internal Server Error**: Check PHP error log: `C:\xampp\php\logs\php_error_log`
- **Connection Refused**: Apache not running
- **JSON Parse Error**: Check `save_booking.php` is returning valid JSON

### Problem: "User not logged in"

**Solution:**
1. Make sure you're logged in before accessing booking page
2. Check session is working: Look for session cookie in browser
3. Verify `Login.php` sets `$_SESSION['user_id']`

### Problem: "Missing required fields"

**Check:**
1. All fields are filled: Facility, Court, Date, Time
2. JavaScript console for errors
3. Network tab shows POST request to `save_booking.php`
4. Form data is being sent correctly

## 📝 Database Structure

### Required Tables:

**bookings**
```sql
- booking_id (INT, AUTO_INCREMENT, PRIMARY KEY)
- user_id (VARCHAR(100))
- venue_id (VARCHAR(100))
- court_id (INT, NULL)
- booking_date (DATE)
- booking_time (TIME)
- status (VARCHAR(20), DEFAULT 'booked')
- created_at (TIMESTAMP)
```

**venues**
```sql
- venue_id (VARCHAR or INT)
- name (VARCHAR)
- status (VARCHAR, 'open' or 'closed')
- image (VARCHAR, optional)
```

## 🧪 Testing Steps

1. **Test Database Connection**
   ```
   http://localhost/SportClubFacilitiesBooking/test_db_connection.php
   ```

2. **Test Booking Flow**
   - Login → Booking Facilities → Select Facility → Select Court → Select Date → Select Time → Book Now → Confirm
   - Check booking_list.php to verify booking appears

3. **Check Error Logs**
   - PHP Errors: `C:\xampp\php\logs\php_error_log`
   - Apache Errors: `C:\xampp\apache\logs\error.log`
   - MySQL Errors: `C:\xampp\mysql\data\mysql_error.log`

## 🔧 Common XAMPP Configuration Issues

### Port Conflicts
- If Apache won't start, change port in `httpd.conf` (default: 80)
- If MySQL won't start, change port in `my.ini` (default: 3306)

### File Permissions
- Ensure PHP files are readable
- Check `htdocs` folder permissions

### PHP Extensions
- Enable `mysqli` extension in `php.ini`
- Restart Apache after changes

## 📞 Still Having Issues?

1. Check `test_db_connection.php` for detailed diagnostics
2. Enable error display temporarily in `save_booking.php`:
   ```php
   ini_set('display_errors', 1);
   ```
3. Check browser console (F12) for JavaScript errors
4. Verify all files are in correct location: `C:\xampp\htdocs\SportClubFacilitiesBooking\`

## ✅ Success Indicators

When everything works:
- ✅ Database connection test shows all green
- ✅ Booking confirmation popup appears
- ✅ Success message shows
- ✅ Booking appears in booking_list.php
- ✅ Time slot becomes unavailable for others


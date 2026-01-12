# Database Integration Setup Guide

## Summary of Changes

Your booking system has been successfully linked to the database! Here's what was created and modified:

### New Files Created:

#### 1. **save_booking.php**
   - Handles booking submissions from the frontend
   - Saves bookings to the `bookings` table
   - Validates user session and input data
   - Checks for duplicate bookings
   - Returns JSON response with success/error status

#### 2. **get_venues.php**
   - Fetches all active venues from the database
   - Returns venue data including ID, name, image, and court count
   - Used to populate the carousel on page load

#### 3. **check_availability.php**
   - Checks which time slots are already booked for a specific venue and date
   - Returns list of booked times
   - Prevents double-booking of time slots

### Files Modified:

#### 1. **booking_facilities.php**
   - Added session check to ensure user is logged in before accessing the booking page
   - Redirects to login if user is not authenticated

#### 2. **script.js**
   - Updated `loadVenues()` to fetch facilities from database at page load
   - Updated `checkAvailability()` to show booked time slots as disabled
   - Updated `selectDate()` to check availability when a date is selected
   - Updated `confirmBooking()` to send booking data to save_booking.php
   - Added `bookedTimes` variable to track disabled time slots
   - Updated `renderTimeButtons()` to visually mark booked times and prevent selection

#### 3. **css/style.css**
   - Added styling for `.time-btn.booked` class to show booked time slots as disabled
   - Booked times appear grayed out with reduced opacity

## Database Requirements

Ensure your database has the following structure:

### venues Table
```sql
CREATE TABLE venues (
    venue_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    image VARCHAR(255),
    court_count INT DEFAULT 1,
    status ENUM('active', 'inactive') DEFAULT 'active'
);
```

### bookings Table
```sql
CREATE TABLE bookings (
    booking_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(100) NOT NULL,
    venue_id INT NOT NULL,
    court_number INT NOT NULL,
    booking_date DATE NOT NULL,
    booking_time VARCHAR(20) NOT NULL,
    status ENUM('pending', 'approved', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venue_id) REFERENCES venues(venue_id),
    UNIQUE KEY unique_booking (venue_id, court_number, booking_date, booking_time)
);
```

## How It Works

### 1. **Page Load**
   - User logs in and navigates to booking_facilities.php
   - JavaScript calls `loadVenues()` which fetches facilities from `get_venues.php`
   - Carousel displays available facilities

### 2. **Date Selection**
   - User clicks on a date in the calendar
   - `selectDate()` is called and triggers `checkAvailability()`
   - Backend checks booked time slots for that venue and date
   - Time buttons are re-rendered with booked slots disabled

### 3. **Booking Confirmation**
   - User selects facility, court, date, and time slots
   - Clicks "BOOK NOW" button
   - Confirmation popup shows booking details
   - User clicks "Confirm"
   - Data is sent to `save_booking.php` via POST request
   - Backend saves booking to database (one entry per time slot)
   - Success popup is shown

### 4. **Error Handling**
   - If time slots are unavailable, they appear disabled
   - If booking fails, warning popup shows error message
   - All database errors are caught and displayed to user

## Testing the Integration

1. **Start XAMPP** - Ensure Apache and MySQL are running
2. **Verify Database** - Check that venues table has data and bookings table exists
3. **Login** - Log in to your account
4. **Navigate to Booking** - Go to "Booking Facilities"
5. **Test Booking Flow**:
   - Scroll through facilities
   - Click on a facility
   - Select a date
   - Note that booked times should appear disabled
   - Select court and available times
   - Click BOOK NOW
   - Confirm booking
   - Check database bookings table to verify entry

## Troubleshooting

| Issue | Solution |
|-------|----------|
| "Failed to connect to server" | Check if XAMPP is running and API_BASE_URL is correct in script.js |
| Facilities not loading | Verify `venues` table has active records with `status='active'` |
| Booked times not showing | Check `check_availability.php` and verify bookings exist in database |
| Booking fails silently | Check browser console (F12) for JavaScript errors |
| Database connection error | Verify db.php credentials match your MySQL setup |

## Next Steps

1. Test the booking flow thoroughly
2. Implement email notifications when bookings are confirmed
3. Add admin approval functionality for bookings
4. Implement cancellation and modification features
5. Add payment integration if needed

---

**All files have been created and linked to your database. You're ready to start booking facilities!**

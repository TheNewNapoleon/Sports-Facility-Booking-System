-- Events Table Migration Script
-- Run this in phpMyAdmin or MySQL command line

-- Add new columns for date ranges and times
ALTER TABLE events ADD COLUMN start_date DATE AFTER venue_id;
ALTER TABLE events ADD COLUMN end_date DATE AFTER start_date;
ALTER TABLE events ADD COLUMN start_time TIME AFTER end_date;
ALTER TABLE events ADD COLUMN end_time TIME AFTER start_time;

-- Migrate existing data (copy event_date to start_date/end_date, event_time to start_time/end_time)
UPDATE events SET
    start_date = event_date,
    end_date = event_date,
    start_time = event_time,
    end_time = event_time
WHERE start_date IS NULL;

-- Make new columns NOT NULL
ALTER TABLE events MODIFY COLUMN start_date DATE NOT NULL;
ALTER TABLE events MODIFY COLUMN end_date DATE NOT NULL;
ALTER TABLE events MODIFY COLUMN start_time TIME NOT NULL;
ALTER TABLE events MODIFY COLUMN end_time TIME NOT NULL;

-- Optional: Drop old columns after verifying migration worked
-- ALTER TABLE events DROP COLUMN event_date;
-- ALTER TABLE events DROP COLUMN event_time;
-- Update trainer_profiles table to support multiple profiles per user
USE teachverse;

-- Remove the unique constraint on user_id
ALTER TABLE trainer_profiles DROP INDEX user_id;

-- Add profile_title column
ALTER TABLE trainer_profiles ADD COLUMN profile_title VARCHAR(255) NOT NULL DEFAULT 'My Profile' AFTER user_id;

-- Update existing records to have a default title
UPDATE trainer_profiles SET profile_title = 'My Profile' WHERE profile_title = '';

-- Show the updated table structure
DESCRIBE trainer_profiles;

-- Show any existing data
SELECT * FROM trainer_profiles;

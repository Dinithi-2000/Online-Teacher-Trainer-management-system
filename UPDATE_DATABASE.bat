@echo off
echo.
echo ========================================
echo  TeachVerse Database Update
echo  Multiple Trainer Profiles Support
echo ========================================
echo.
echo This script will update your database to support multiple trainer profiles per user.
echo.
echo IMPORTANT: Make sure your MySQL server is running and you have access to phpMyAdmin
echo or MySQL command line.
echo.
echo Please execute the following SQL commands in your MySQL/phpMyAdmin:
echo.
echo 1. Open phpMyAdmin in your browser
echo 2. Select the 'teachverse' database
echo 3. Go to the SQL tab
echo 4. Copy and paste the following commands:
echo.
echo ----------------------------------------
type "%~dp0database\update_multiple_profiles.sql"
echo ----------------------------------------
echo.
echo 5. Click 'Go' to execute the commands
echo.
echo After running the SQL commands, your trainer profiles system will support:
echo - Multiple profiles per user
echo - Profile titles/specializations
echo - Better profile management
echo.
echo Press any key to close this window...
pause >nul

@echo off
echo ====================================
echo     TeachVerse Website Setup
echo ====================================
echo.
echo This will help you set up your website!
echo.
echo STEP 1: Install XAMPP
echo - Download from: https://www.apachefriends.org/download.html
echo - Install it to C:\xampp\
echo.
echo STEP 2: Copy this folder to XAMPP
echo - Copy this entire folder to: C:\xampp\htdocs\teachverse\
echo.
echo STEP 3: Start XAMPP
echo - Open XAMPP Control Panel
echo - Start Apache and MySQL
echo.
echo STEP 4: Setup Database
echo - Go to: http://localhost/phpmyadmin
echo - Create database: teachverse
echo - Import file: database/setup.sql
echo.
echo STEP 5: Open Website
echo - Go to: http://localhost/teachverse/
echo.
echo ====================================
echo     Test Login Accounts
echo ====================================
echo Admin:   admin@teachverse.com   / password
echo Trainer: trainer@teachverse.com / password  
echo Student: student@teachverse.com / password
echo.
echo Press any key to open XAMPP download page...
pause >nul
start https://www.apachefriends.org/download.html

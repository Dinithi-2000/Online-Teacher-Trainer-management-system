# 🚀 TeachVerse Website Setup Guide

## Quick Setup Instructions

### 1. Install XAMPP
- Download XAMPP from: https://www.apachefriends.org/download.html
- Install it (usually to C:\xampp\)
- Start Apache and MySQL services from XAMPP Control Panel

### 2. Move Your Project
**Copy this entire folder to:**
```
C:\xampp\htdocs\teachverse\
```

So your project structure should be:
```
C:\xampp\htdocs\teachverse\
├── config/
├── assets/
├── database/
├── modules/
├── index.php
├── login.php
├── register.php
└── ... (all other files)
```

### 3. Setup Database
1. Open your browser and go to: http://localhost/phpmyadmin
2. Create a new database named: `teachverse`
3. Import the SQL file: `database/setup.sql`
   - Click on "teachverse" database
   - Go to "Import" tab
   - Choose file: `database/setup.sql`
   - Click "Go"

### 4. Access Your Website
**Main Website URL:** http://localhost/teachverse/

### 5. Test Login Accounts
| Role | Email | Password |
|------|-------|----------|
| Admin | admin@teachverse.com | password |
| Trainer | trainer@teachverse.com | password |
| Student | student@teachverse.com | password |

## 📱 Website Pages to Test

### Public Pages:
- **Homepage:** http://localhost/teachverse/
- **Course Catalog:** http://localhost/teachverse/courses.php
- **Course Details:** http://localhost/teachverse/course-details.php?id=1
- **Login:** http://localhost/teachverse/login.php
- **Register:** http://localhost/teachverse/register.php

### After Login - Dashboard:
- **User Dashboard:** http://localhost/teachverse/dashboard.php

### Admin Panel (login as admin):
- **User Management:** http://localhost/teachverse/modules/users/index.php
- **Course Management:** http://localhost/teachverse/modules/courses/index.php
- **All Reviews:** http://localhost/teachverse/modules/reviews/index.php

### Trainer Features (login as trainer):
- **Trainer Profiles:** http://localhost/teachverse/modules/trainer_profiles/index.php
- **Create Profile:** http://localhost/teachverse/modules/trainer_profiles/create.php

### Student Features (login as student):
- **My Enrollments:** http://localhost/teachverse/modules/enrollments/index.php
- **My Reviews:** http://localhost/teachverse/modules/reviews/index.php

## 🔧 CRUD Operations to Test

### 1. Course Management (Module 1):
- **Create:** Add new course from trainer/admin dashboard
- **Read:** Browse courses on homepage and courses page
- **Update:** Edit course details
- **Delete:** Remove courses (check enrollment validation)

### 2. User Management (Module 2):
- **Create:** Register new users or admin create users
- **Read:** View user list (admin only)
- **Update:** Edit user profiles and roles
- **Delete:** Remove users (check dependency warnings)

### 3. Trainer Profiles (Module 3):
- **Create:** Create trainer profile with bio and image
- **Read:** Browse trainer profiles
- **Update:** Edit profile information
- **Delete:** Remove trainer profiles

### 4. Enrollments (Module 4):
- **Create:** Enroll in courses from course details page
- **Read:** View enrollment status and progress
- **Update:** Update learning progress
- **Delete:** Unenroll from courses

### 5. Reviews (Module 5):
- **Create:** Write course reviews with star ratings
- **Read:** View reviews on course pages
- **Update:** Edit existing reviews
- **Delete:** Remove reviews

## 🎯 Testing Checklist

- [ ] Homepage loads with statistics
- [ ] User registration works
- [ ] Login system functions
- [ ] Course catalog displays properly
- [ ] Course enrollment process
- [ ] Progress tracking updates
- [ ] Review system with star ratings
- [ ] Admin panel access control
- [ ] File uploads (course images, trainer photos)
- [ ] Search and filtering features
- [ ] Responsive design on mobile

## 🚨 Troubleshooting

### If database connection fails:
1. Check XAMPP MySQL is running
2. Verify database name is "teachverse"
3. Check config/database.php settings

### If images don't load:
1. Check assets/images/ folder permissions
2. Upload test images through the interface

### If login doesn't work:
1. Make sure you imported setup.sql correctly
2. Try creating a new account

## 🎉 Success!
If everything works, you'll have a fully functional online teacher training platform with:
- User authentication system
- Course management
- Enrollment tracking
- Review system
- Modern responsive design
- Complete CRUD operations for all 5 modules

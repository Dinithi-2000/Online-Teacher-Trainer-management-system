# TeachVerse - Online Teacher Training Platform

A comprehensive web-based platform for teacher training with full CRUD operations across 5 core modules. Built with PHP, MySQL, HTML, CSS, and JavaScript.

## 🎯 Project Overview

TeachVerse is a university-level web development project designed to demonstrate complete CRUD (Create, Read, Update, Delete) functionality across multiple interconnected modules. The platform serves as an online teacher training ecosystem where trainers can create courses and students can enroll, learn, and provide feedback.

## 🚀 Features

### Core Modules (Each with Full CRUD Operations)

#### 📘 1. Course Management (Module 1)
- **Create**: Add new courses with title, description, duration, price, and images
- **Read**: Browse courses with search/filter functionality
- **Update**: Edit course details and content
- **Delete**: Remove courses (with enrollment checks)
- **Database**: `courses` table with full relationship management

#### 👥 2. User Management (Module 2)
- **Create**: Register new users with role-based permissions
- **Read**: View user lists with filtering by role
- **Update**: Modify user profiles and roles
- **Delete**: Remove users from the system
- **Database**: `users` table with authentication system
- **Bonus**: Complete login/logout system with sessions

#### 👨‍🏫 3. Trainer Profiles (Module 3)
- **Create**: Build detailed trainer profiles with bio, experience, certificates
- **Read**: Public trainer profile viewing
- **Update**: Edit profile information and upload certificates
- **Delete**: Remove trainer profiles
- **Database**: `trainer_profiles` table with file upload support

#### 📊 4. Enrollments & Progress Tracking (Module 4)
- **Create**: Enroll students in courses
- **Read**: View enrollment status and progress
- **Update**: Track and update learning progress
- **Delete**: Unenroll from courses
- **Database**: `enrollments` table with progress tracking

#### ⭐ 5. Review & Feedback System (Module 5)
- **Create**: Submit course reviews with star ratings
- **Read**: Display reviews and ratings
- **Update**: Edit existing reviews
- **Delete**: Remove reviews
- **Database**: `reviews` table with rating calculations
- **Bonus**: Interactive star rating UI with JavaScript

## 🛠 Technology Stack

- **Frontend**: HTML5, CSS3 (Custom modern UI), JavaScript (ES6+)
- **Backend**: PHP 7.4+ with PDO
- **Database**: MySQL with phpMyAdmin
- **Architecture**: MVC-inspired structure
- **Security**: Password hashing, SQL injection prevention, XSS protection

## 📁 Project Structure

```
TeachVerse/
├── config/
│   └── database.php              # Database configuration & helper functions
├── assets/
│   ├── css/
│   │   └── style.css            # Modern UI/UX styles with CSS Grid & Flexbox
│   ├── js/
│   │   └── main.js              # Interactive JavaScript functionality
│   └── images/                  # Course and profile images
├── database/
│   └── setup.sql                # Complete database schema with sample data
├── modules/
│   ├── courses/                 # Module 1: Course Management
│   │   ├── index.php           # Read - Browse courses
│   │   ├── create.php          # Create - Add new course
│   │   ├── edit.php            # Update - Edit course
│   │   └── delete.php          # Delete - Remove course
│   ├── users/                   # Module 2: User Management
│   │   ├── index.php           # Read - User listing
│   │   ├── create.php          # Create - Add user
│   │   ├── edit.php            # Update - Edit user
│   │   └── delete.php          # Delete - Remove user
│   ├── trainer_profiles/        # Module 3: Trainer Profiles
│   │   ├── create.php          # Create/Update - Manage profile
│   │   ├── view.php            # Read - View profile
│   │   └── delete.php          # Delete - Remove profile
│   ├── enrollments/            # Module 4: Enrollments & Progress
│   │   ├── index.php           # Read - View enrollments
│   │   ├── enroll.php          # Create - New enrollment
│   │   ├── update_progress.php # Update - Progress tracking
│   │   └── unenroll.php        # Delete - Remove enrollment
│   └── reviews/                # Module 5: Review System
│       ├── create.php          # Create/Update - Manage reviews
│       └── delete.php          # Delete - Remove review
├── index.php                    # Homepage with featured content
├── courses.php                  # Public course catalog
├── course-details.php           # Individual course pages
├── dashboard.php                # User dashboard
├── login.php                    # Authentication
├── register.php                 # User registration
└── logout.php                   # Session management
```

## 🎨 Modern UI/UX Design Features

- **Responsive Design**: Mobile-first approach with CSS Grid and Flexbox
- **Color Scheme**: Professional gradient-based theme
- **Interactive Elements**: Hover effects, smooth transitions, loading states
- **Typography**: Clean, readable fonts with proper hierarchy
- **Components**: Cards, modals, alerts, progress bars, star ratings
- **Accessibility**: Semantic HTML, ARIA labels, keyboard navigation

## 🔧 Installation & Setup

### Prerequisites
- XAMPP/WAMP/LAMP stack
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser (Chrome, Firefox, Safari, Edge)

### Step-by-Step Installation

1. **Clone/Download the Project**
   ```bash
   # Place the project in your web server directory
   # For XAMPP: C:\xampp\htdocs\teachverse\
   # For WAMP: C:\wamp64\www\teachverse\
   ```

2. **Database Setup**
   - Open phpMyAdmin (usually http://localhost/phpmyadmin)
   - Create a new database named `teachverse`
   - Import the SQL file: `database/setup.sql`
   - This will create all tables and insert sample data

3. **Configuration**
   - Open `config/database.php`
   - Update database credentials if needed:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USERNAME', 'root');
     define('DB_PASSWORD', '');
     define('DB_NAME', 'teachverse');
     ```

4. **File Permissions**
   - Ensure write permissions for image upload directories:
     - `assets/images/courses/`
     - `assets/images/trainers/`

5. **Access the Application**
   - Open your browser and navigate to: `http://localhost/teachverse/`

## 👥 Demo Accounts

The system comes with pre-configured demo accounts for testing:

| Role | Email | Password | Access Level |
|------|-------|----------|--------------|
| Admin | admin@teachverse.com | password | Full system access |
| Trainer | trainer@teachverse.com | password | Course creation & management |
| Student | student@teachverse.com | password | Course enrollment & learning |

## 📋 Database Schema

### Core Tables

1. **users** - User management with role-based access
2. **courses** - Course catalog with metadata
3. **trainer_profiles** - Extended trainer information
4. **enrollments** - Student-course relationships with progress
5. **reviews** - Course feedback and ratings

### Relationships
- Users → Trainer Profiles (1:1)
- Users → Courses (1:Many as creator)
- Users → Enrollments (1:Many)
- Courses → Enrollments (1:Many)
- Users → Reviews (1:Many)
- Courses → Reviews (1:Many)

## 🔐 Security Features

- **Authentication**: Secure login system with password hashing
- **Authorization**: Role-based access control (Admin, Trainer, Student)
- **SQL Injection Prevention**: PDO prepared statements
- **XSS Protection**: Input sanitization and output escaping
- **File Upload Security**: Type validation and secure storage
- **Session Management**: Secure session handling

## 📱 Responsive Design

The platform is fully responsive and works seamlessly across:
- **Desktop**: Full feature set with optimal layout
- **Tablet**: Adapted navigation and grid layouts
- **Mobile**: Touch-friendly interface with collapsed navigation

## 🎯 CRUD Operations Demonstration

Each module showcases complete CRUD functionality:

### Course Management Example
```php
// Create - Add new course
INSERT INTO courses (title, description, duration, price, created_by)

// Read - Display courses with filtering
SELECT * FROM courses WHERE title LIKE '%search%'

// Update - Modify course details
UPDATE courses SET title = ?, description = ? WHERE course_id = ?

// Delete - Remove course (with validation)
DELETE FROM courses WHERE course_id = ? AND no_enrollments
```

### Progress Tracking Example
```php
// Create - Enroll student
INSERT INTO enrollments (user_id, course_id, progress, status)

// Read - Get enrollment status
SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?

// Update - Track progress
UPDATE enrollments SET progress = ?, status = ? WHERE enroll_id = ?

// Delete - Unenroll student
DELETE FROM enrollments WHERE enroll_id = ?
```

## 🚀 Advanced Features

### Interactive JavaScript Components
- **Star Rating System**: Click-to-rate with visual feedback
- **Progress Bars**: Animated progress tracking
- **Modal Dialogs**: Dynamic content display
- **AJAX Operations**: Seamless user interactions
- **Form Validation**: Real-time input validation

### Modern CSS Features
- **CSS Grid**: Complex layout management
- **Flexbox**: Component alignment and distribution
- **CSS Variables**: Consistent theming
- **Animations**: Smooth transitions and hover effects
- **Media Queries**: Responsive breakpoints

## 📊 System Statistics Dashboard

The dashboard provides comprehensive analytics:
- Course enrollment metrics
- User activity tracking
- Progress completion rates
- Review and rating summaries
- Revenue tracking (for trainers)

## 🎓 Educational Value

This project demonstrates proficiency in:
- **Full-Stack Development**: Frontend and backend integration
- **Database Design**: Normalized schema with relationships
- **Security Best Practices**: Authentication and data protection
- **Modern Web Standards**: Responsive design and accessibility
- **Project Architecture**: Organized, maintainable code structure

## 🤝 Team Collaboration

Designed for 5-member team development:
- **Member 1**: Course Management module
- **Member 2**: User Management & Authentication
- **Member 3**: Trainer Profiles
- **Member 4**: Enrollments & Progress Tracking
- **Member 5**: Review & Feedback System

Each member implements complete CRUD operations for their assigned module while maintaining system integration.

## 📈 Future Enhancements

Potential expansions include:
- Video content integration
- Real-time messaging system
- Advanced analytics dashboard
- Mobile app development
- Payment gateway integration
- Multi-language support

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check XAMPP/WAMP services are running
   - Verify database credentials in `config/database.php`
   - Ensure MySQL service is active

2. **Image Upload Issues**
   - Check folder permissions for `assets/images/`
   - Verify file size limits in PHP configuration
   - Ensure proper file extensions are allowed

3. **Session Issues**
   - Check PHP session configuration
   - Clear browser cookies and cache
   - Verify session storage permissions

## 📄 License

This project is developed for educational purposes as part of university coursework. All code is available for learning and reference.

## 👨‍💻 Development Team

**Team TeachVerse** - University Web Development Project
- Focus: Demonstrating professional-level CRUD operations
- Technology: Modern PHP/MySQL with responsive frontend
- Goal: Comprehensive learning management system

---

**Note**: This project showcases modern web development practices suitable for a university-level course while maintaining professional code quality and user experience standards.

-- TeachVerse Database Setup
-- Create database
CREATE DATABASE IF NOT EXISTS teachverse;
USE teachverse;

-- Table 1: Users (Member 2 - User Management)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'trainer', 'student') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table 2: Courses (Member 1 - Course Management)
CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100) DEFAULT 'General',
    image VARCHAR(255) DEFAULT 'default-course.jpg',
    duration VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Table 3: Trainer Profiles (Member 3 - Trainer Profiles)
CREATE TABLE trainer_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    bio TEXT,
    experience TEXT,
    certificates TEXT,
    profile_image VARCHAR(255) DEFAULT 'default-trainer.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Table 4: Enrollments (Member 4 - Enrollments & Progress Tracking)
CREATE TABLE enrollments (
    enroll_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    progress INT DEFAULT 0,
    status ENUM('enrolled', 'in_progress', 'completed', 'dropped') DEFAULT 'enrolled',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (user_id, course_id)
);

-- Table 5: Reviews (Member 5 - Review & Feedback System)
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (user_id, course_id)
);

-- Insert sample admin user
INSERT INTO users (name, email, password, role) VALUES 
('Admin User', 'admin@teachverse.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample trainer
INSERT INTO users (name, email, password, role) VALUES 
('John Trainer', 'trainer@teachverse.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer');

-- Insert sample student
INSERT INTO users (name, email, password, role) VALUES 
('Jane Student', 'student@teachverse.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- Insert sample courses
INSERT INTO courses (title, description, category, duration, price, created_by) VALUES 
('Web Development Fundamentals', 'Learn the basics of HTML, CSS, and JavaScript', 'Programming', '8 weeks', 299.99, 2),
('Advanced PHP Programming', 'Master server-side development with PHP', 'Programming', '10 weeks', 399.99, 2),
('Database Design & MySQL', 'Complete guide to database design and MySQL', 'Technology', '6 weeks', 249.99, 2),
('Graphic Design Fundamentals', 'Learn the principles of graphic design using Adobe Creative Suite', 'Design', '6 weeks', 199.99, 2),
('Digital Marketing Strategy', 'Master modern digital marketing techniques and social media', 'Marketing', '8 weeks', 299.99, 2),
('Business Leadership Skills', 'Develop essential leadership and management skills', 'Business', '10 weeks', 399.99, 2),
('Spanish Language Course', 'Learn conversational Spanish from beginner to intermediate', 'Language', '12 weeks', 149.99, 2),
('Data Science with Python', 'Introduction to data analysis and machine learning', 'Science', '14 weeks', 449.99, 2);

-- Insert sample trainer profile
INSERT INTO trainer_profiles (user_id, bio, experience, certificates) VALUES 
(2, 'Experienced web developer with 5+ years in the industry', 'Full-stack development, PHP, JavaScript, MySQL', 'Certified PHP Developer, MySQL Certified');

-- Insert sample enrollments
INSERT INTO enrollments (user_id, course_id, progress, status) VALUES 
(3, 1, 25, 'in_progress'),
(3, 2, 0, 'enrolled');

-- Insert sample reviews
INSERT INTO reviews (user_id, course_id, rating, comment) VALUES 
(3, 1, 5, 'Excellent course! Very comprehensive and well-structured.');

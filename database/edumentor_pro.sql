-- EduMentor Pro Database Structure
-- Version: 1.0
-- Generated on: 2025-07-21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: `edumentor_pro`

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('admin','trainer','student') DEFAULT 'student',
  `profile_image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'active',
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
  `profile_image` varchar(255) DEFAULT NULL,
  `permissions` text DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `duration` int(11) DEFAULT 0,
  `price` decimal(10,2) DEFAULT 0.00,
  `rating` decimal(3,2) DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `syllabus` text DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `status` enum('active','inactive','draft') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `progress` decimal(5,2) DEFAULT 0.00,
  `status` enum('active','completed','dropped') DEFAULT 'active',
  `completion_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `excerpt` varchar(500) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `status` enum('published','draft','archived') DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Dumping data for table `users`
-- Sample user data for testing
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `role`, `profile_image`, `bio`, `status`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'demo_student', 'student@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo', 'Student', 'student', NULL, 'Demo student account for testing the EduMentor Pro platform. This account demonstrates the student user experience.', 'active', NULL, '2025-07-21 00:00:00', '2025-07-21 00:00:00'),
(2, 'john_trainer', 'trainer@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Trainer', 'trainer', NULL, 'Professional trainer with 10+ years of experience in digital education and teacher development.', 'active', NULL, '2025-07-21 00:00:00', '2025-07-21 00:00:00');

--
-- Dumping data for table `admins`
-- System administrators with full access
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `role`, `profile_image`, `permissions`, `last_login`, `status`, `created_at`, `updated_at`) VALUES
(1, 'edumentor_admin', 'admin@edumentor.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'EduMentor', 'Admin', 'super_admin', NULL, 'full_access,user_management,course_management,system_settings,reports,analytics', '2025-07-21 00:00:00', 'active', '2025-07-21 00:00:00', '2025-07-21 00:00:00');

-- --------------------------------------------------------

--
-- Sample courses data
--

INSERT INTO `courses` (`id`, `title`, `description`, `short_description`, `instructor_id`, `category`, `level`, `duration`, `price`, `rating`, `image`, `syllabus`, `objectives`, `requirements`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Digital Teaching Fundamentals', 'Master the basics of online teaching and digital classroom management. Learn essential tools and techniques for effective remote education.', 'Master the basics of online teaching and digital classroom management.', 1, 'Digital Education', 'beginner', 6, 99.99, 4.8, 'digital-teaching.jpg', 'Module 1: Introduction to Digital Teaching\nModule 2: Online Classroom Setup\nModule 3: Student Engagement Strategies\nModule 4: Assessment and Feedback\nModule 5: Technology Tools\nModule 6: Best Practices', 'Understand digital teaching principles, Set up effective online classrooms, Engage students remotely', 'Basic computer skills, Internet connection', 'active', '2025-07-21 00:00:00', '2025-07-21 00:00:00'),

(2, 'Classroom Management Mastery', 'Develop effective strategies for managing diverse classrooms and student behavior. Build positive learning environments.', 'Develop effective strategies for managing diverse classrooms and student behavior.', 1, 'Classroom Management', 'intermediate', 8, 149.99, 4.9, 'classroom-management.jpg', 'Module 1: Classroom Environment Setup\nModule 2: Behavior Management Strategies\nModule 3: Building Positive Relationships\nModule 4: Conflict Resolution\nModule 5: Parent Communication\nModule 6: Special Needs Considerations', 'Create positive classroom environments, Implement effective behavior management, Build strong student relationships', 'Teaching experience helpful but not required', 'active', '2025-07-21 00:00:00', '2025-07-21 00:00:00'),

(3, 'Modern Assessment Strategies', 'Learn innovative approaches to student assessment and performance evaluation. Design fair and effective assessment tools.', 'Learn innovative approaches to student assessment and performance evaluation.', 1, 'Assessment', 'advanced', 10, 179.99, 4.7, 'assessment-strategies.jpg', 'Module 1: Assessment Theory\nModule 2: Formative Assessment Techniques\nModule 3: Summative Assessment Design\nModule 4: Digital Assessment Tools\nModule 5: Rubric Development\nModule 6: Feedback Strategies', 'Design effective assessments, Use digital assessment tools, Provide meaningful feedback', 'Previous teaching experience recommended', 'active', '2025-07-21 00:00:00', '2025-07-21 00:00:00');

-- --------------------------------------------------------

--
-- Sample blog posts
--

INSERT INTO `blog_posts` (`id`, `title`, `content`, `excerpt`, `author_id`, `category`, `featured_image`, `status`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 'The Future of Digital Education', 'Digital education has transformed the way we learn and teach. In this comprehensive guide, we explore the latest trends, technologies, and methodologies that are shaping the future of education...', 'Exploring the latest trends and technologies shaping modern education.', 1, 'Education Technology', 'blog-digital-education.jpg', 'published', '2025-07-21 00:00:00', '2025-07-21 00:00:00', '2025-07-21 00:00:00'),

(2, '5 Essential Teaching Strategies for 2025', 'As we advance into 2025, teaching methodologies continue to evolve. Here are five essential strategies every educator should master...', 'Discover the most effective teaching strategies for modern educators.', 1, 'Teaching Tips', 'blog-teaching-strategies.jpg', 'published', '2025-07-21 00:00:00', '2025-07-21 00:00:00', '2025-07-21 00:00:00'),

(3, 'Building Inclusive Learning Environments', 'Creating inclusive classrooms that welcome and support all students is more important than ever. Learn practical strategies for fostering diversity and inclusion...', 'Practical strategies for creating inclusive and diverse learning spaces.', 1, 'Inclusive Education', 'blog-inclusive-learning.jpg', 'published', '2025-07-21 00:00:00', '2025-07-21 00:00:00', '2025-07-21 00:00:00');

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`user_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `resources_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

COMMIT;

-- End of SQL file

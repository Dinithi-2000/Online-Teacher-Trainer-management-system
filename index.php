<?php
session_start();
require_once 'config/database.php';

// Get featured courses with instructor and enrollment information
$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        u.name as instructor_name,
        COUNT(DISTINCT e.enroll_id) as enrollment_count,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(DISTINCT r.review_id) as review_count
    FROM courses c
    LEFT JOIN users u ON c.created_by = u.user_id
    LEFT JOIN enrollments e ON c.course_id = e.course_id
    LEFT JOIN reviews r ON c.course_id = r.course_id
    GROUP BY c.course_id, c.title, c.description, c.price, c.duration, c.image, c.category, c.created_at, c.updated_at, c.created_by, u.name
    ORDER BY c.created_at DESC 
    LIMIT 6
");
$stmt->execute();
$featured_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total statistics
$stats_courses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$stats_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('trainer', 'student')")->fetchColumn();
$stats_enrollments = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TeachVerse - Transform Your Teaching Journey</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Modern CSS Variables System */
        :root {
            /* Primary Colors */
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #06b6d4;
            --secondary-dark: #0891b2;
            --secondary-light: #22d3ee;
            
            /* Neutral Colors */
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            
            /* Semantic Colors */
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
            
            /* Text Colors */
            --text-primary: var(--gray-900);
            --text-secondary: var(--gray-600);
            --text-muted: var(--gray-500);
            --text-inverse: var(--white);
            
            /* Background Colors */
            --bg-primary: var(--white);
            --bg-secondary: var(--gray-50);
            --bg-tertiary: var(--gray-100);
            --bg-overlay: rgba(0, 0, 0, 0.5);
            
            /* Modern Shadows */
            --shadow-xs: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            
            /* Border Radius */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-2xl: 1.5rem;
            --radius-3xl: 2rem;
            --radius-full: 9999px;
            
            /* Spacing Scale */
            --space-1: 0.25rem;
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-5: 1.25rem;
            --space-6: 1.5rem;
            --space-8: 2rem;
            --space-10: 2.5rem;
            --space-12: 3rem;
            --space-16: 4rem;
            --space-20: 5rem;
            --space-24: 6rem;
            --space-32: 8rem;
            
            /* Transitions */
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-normal: 300ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 500ms cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Typography */
            --font-sans: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --font-mono: 'SF Mono', Consolas, 'Liberation Mono', Menlo, monospace;
            
            /* Z-Index Scale */
            --z-dropdown: 1000;
            --z-sticky: 1020;
            --z-fixed: 1030;
            --z-modal-backdrop: 1040;
            --z-modal: 1050;
            --z-popover: 1060;
            --z-tooltip: 1070;
            --z-toast: 1080;
            
            /* Additional Variables */
            --border-color: var(--gray-200);
            --border-radius-sm: var(--radius-sm);
            --transition: var(--transition-normal);
            --primary-gradient: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        /* Reset and Base Styles */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font-sans);
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--bg-primary);
            overflow-x: hidden;
        }

        /* Container System */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--space-6);
        }

        /* Button System */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-2);
            padding: var(--space-3) var(--space-6);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            border: none;
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: all var(--transition-normal);
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: var(--white);
        }

        .btn-ghost {
            background: transparent;
            color: var(--text-primary);
            border: 2px solid var(--gray-200);
        }

        .btn-ghost:hover {
            background: var(--gray-50);
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-lg {
            padding: var(--space-4) var(--space-8);
            font-size: 1rem;
        }

        .btn-xl {
            padding: var(--space-5) var(--space-10);
            font-size: 1.125rem;
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            position: relative;
            overflow: hidden;
            padding-top: 100px;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='rgba(255,255,255,0.1)' d='M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E") bottom center / cover no-repeat;
        }

        .hero-content {
            text-align: center;
            position: relative;
            z-index: 10;
            max-width: 900px;
            margin: 0 auto;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--radius-full);
            padding: var(--space-2) var(--space-4);
            margin-bottom: var(--space-6);
            font-size: 0.875rem;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }

        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: var(--space-6);
        }

        .hero-subtitle {
            font-size: 1.25rem;
            line-height: 1.6;
            margin-bottom: var(--space-8);
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-actions {
            display: flex;
            gap: var(--space-4);
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: var(--space-12);
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: var(--space-6);
            max-width: 600px;
            margin: 0 auto;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat-number {
            display: block;
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }

        .hero-stat-label {
            font-size: 0.875rem;
            opacity: 0.8;
            margin-top: var(--space-1);
        }

        /* Sections */
        .section {
            padding: var(--space-32) 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: var(--space-16);
        }

        .section-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: var(--space-4);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-subtitle {
            font-size: 1.125rem;
            color: var(--text-secondary);
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Features Section */
        .features-section {
            background: var(--bg-secondary);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: var(--space-8);
        }

        .feature-card {
            background: var(--white);
            border-radius: var(--radius-2xl);
            padding: var(--space-8);
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            transform: scaleX(0);
            transition: transform var(--transition-normal);
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            border-radius: var(--radius-xl);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--white);
            font-size: 2rem;
            margin-bottom: var(--space-6);
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: var(--space-4);
            color: var(--text-primary);
        }

        .feature-description {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Courses Section */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: var(--space-8);
        }

        .course-card {
            background: var(--white);
            border-radius: var(--radius-2xl);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            transition: all var(--transition-normal);
        }

        .course-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-2xl);
        }

        .course-image {
            width: 100%;
            height: 240px;
            object-fit: cover;
            transition: transform var(--transition-normal);
        }

        .course-card:hover .course-image {
            transform: scale(1.05);
        }

        .course-content {
            padding: var(--space-6);
        }

        .course-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: var(--space-3);
            color: var(--text-primary);
            line-height: 1.4;
        }

        .course-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: var(--space-4);
        }

        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-6);
            padding: var(--space-4);
            background: var(--gray-50);
            border-radius: var(--radius-lg);
        }

        .course-duration {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            color: var(--text-secondary);
            font-weight: 500;
        }

        .course-price {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
        }

        /* Enhanced Course Card Styles */
        .course-image-container {
            position: relative;
            overflow: hidden;
        }

        .course-category {
            position: absolute;
            top: var(--space-3);
            left: var(--space-3);
            background: rgba(0, 0, 0, 0.8);
            color: var(--white);
            padding: var(--space-1) var(--space-3);
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .course-rating {
            position: absolute;
            top: var(--space-3);
            right: var(--space-3);
            background: rgba(255, 255, 255, 0.95);
            color: var(--warning);
            padding: var(--space-2) var(--space-3);
            border-radius: var(--radius-lg);
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: var(--space-1);
            box-shadow: var(--shadow-sm);
        }

        .course-rating small {
            color: var(--text-secondary);
            margin-left: var(--space-1);
        }

        .course-instructor {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            margin-bottom: var(--space-3);
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .course-instructor i {
            color: var(--primary);
        }

        .course-stats {
            display: flex;
            flex-direction: column;
            gap: var(--space-2);
        }

        .course-students {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .course-students i {
            color: var(--secondary);
        }

        .free-badge {
            background: var(--success);
            color: var(--white);
            padding: var(--space-1) var(--space-3);
            border-radius: var(--radius-full);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Empty State */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: var(--space-20) var(--space-8);
            background: var(--gray-50);
            border-radius: var(--radius-2xl);
            border: 2px dashed var(--gray-300);
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--gray-400);
            margin-bottom: var(--space-4);
        }

        .empty-state h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-2);
        }

        .empty-state p {
            color: var(--text-secondary);
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-content {
            position: relative;
            z-index: 10;
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: var(--space-6);
        }

        .cta-subtitle {
            font-size: 1.125rem;
            line-height: 1.6;
            margin-bottom: var(--space-8);
            opacity: 0.9;
        }

        .cta-button {
            background: var(--white);
            color: var(--primary);
            font-size: 1.125rem;
            font-weight: 600;
            padding: var(--space-4) var(--space-8);
            border-radius: var(--radius-xl);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            box-shadow: var(--shadow-lg);
            transition: all var(--transition-normal);
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-2xl);
        }

        /* Footer */
        .footer {
            background: var(--gray-900);
            color: var(--white);
            padding: var(--space-20) 0 var(--space-8);
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-12);
            margin-bottom: var(--space-12);
        }

        .footer-section h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: var(--space-6);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .footer-section a {
            color: var(--gray-400);
            text-decoration: none;
            display: block;
            margin-bottom: var(--space-3);
            transition: all var(--transition-normal);
        }

        .footer-section a:hover {
            color: var(--white);
            transform: translateX(4px);
        }

        .footer-bottom {
            border-top: 1px solid var(--gray-800);
            padding-top: var(--space-8);
            text-align: center;
        }

        /* Scroll to Top */
        .scroll-to-top {
            position: fixed;
            bottom: var(--space-6);
            right: var(--space-6);
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: var(--radius-full);
            width: 50px;
            height: 50px;
            font-size: 1.25rem;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            transition: all var(--transition-normal);
            opacity: 0;
            visibility: hidden;
            z-index: var(--z-sticky);
        }

        .scroll-to-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .scroll-to-top:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-fade-in {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .animate-slide-in {
            animation: fadeInLeft 0.8s ease-out forwards;
        }

        .loading {
            opacity: 0;
            animation: fadeInUp 0.8s ease-out forwards;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                position: fixed;
                top: 80px;
                left: 0;
                right: 0;
                background: var(--white);
                flex-direction: column;
                padding: var(--space-8);
                box-shadow: var(--shadow-lg);
                transform: translateY(-100%);
                opacity: 0;
                visibility: hidden;
                transition: all var(--transition-normal);
            }

            .nav-menu.open {
                transform: translateY(0);
                opacity: 1;
                visibility: visible;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .hero {
                padding: 120px 0 80px;
            }

            .hero-actions {
                flex-direction: column;
                align-items: center;
            }

            .hero-actions .btn {
                width: 100%;
                max-width: 300px;
            }

            .features-grid,
            .courses-grid {
                grid-template-columns: 1fr;
            }

            .course-card {
                margin-bottom: var(--space-6);
            }

            .course-image {
                height: 200px;
            }

            .course-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--space-3);
            }

            .course-stats {
                width: 100%;
            }

            .course-category,
            .course-rating {
                position: static;
                display: inline-block;
                margin: var(--space-2) 0;
            }

            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }

        /* Utility Classes */
        .text-center { text-align: center; }
        .mb-8 { margin-bottom: var(--space-8); }
    </style>
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section id="main-content" class="hero section" role="main" aria-label="Welcome to TeachVerse">
        <div class="container">
            <div class="hero-content animate-fade-in">
                <div class="hero-badge">
                    <i class="fas fa-star"></i>
                    #1 Teacher Training Platform
                </div>
                
                <h1 class="hero-title">
                    Transform Your Teaching Journey
                </h1>
                
                <p class="hero-subtitle">
                    Join thousands of educators who are advancing their careers with our comprehensive 
                    professional development courses designed by industry experts.
                </p>
                
                <div class="hero-actions">
                    <a href="auth.php?mode=register&type=user" class="btn btn-primary btn-xl">
                        <i class="fas fa-rocket"></i> Start Learning Today
                    </a>
                    <a href="courses.php" class="btn btn-secondary btn-xl">
                        <i class="fas fa-search"></i> Explore Courses
                    </a>
                </div>
                
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?php echo $stats_courses; ?>+</span>
                        <span class="hero-stat-label">Expert Courses</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?php echo $stats_users; ?>+</span>
                        <span class="hero-stat-label">Active Learners</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?php echo $stats_enrollments; ?>+</span>
                        <span class="hero-stat-label">Enrollments</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number">98%</span>
                        <span class="hero-stat-label">Success Rate</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title animate-fade-in">Why Choose TeachVerse?</h2>
                <p class="section-subtitle animate-fade-in">
                    Discover the features that make us the leading teacher training platform
                </p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card animate-slide-in" style="animation-delay: 0.1s;">
                    <div class="feature-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3 class="feature-title">Expert-Led Courses</h3>
                    <p class="feature-description">
                        Learn from industry experts with years of teaching experience and proven methodologies.
                    </p>
                </div>

                <div class="feature-card animate-slide-in" style="animation-delay: 0.2s;">
                    <div class="feature-icon">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <h3 class="feature-title">Interactive Learning</h3>
                    <p class="feature-description">
                        Engage with multimedia content, live sessions, and hands-on practical exercises.
                    </p>
                </div>

                <div class="feature-card animate-slide-in" style="animation-delay: 0.3s;">
                    <div class="feature-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3 class="feature-title">Certified Programs</h3>
                    <p class="feature-description">
                        Earn recognized certifications that advance your career and validate your expertise.
                    </p>
                </div>

                <div class="feature-card animate-slide-in" style="animation-delay: 0.4s;">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Community Support</h3>
                    <p class="feature-description">
                        Connect with fellow educators in our vibrant community of passionate teachers.
                    </p>
                </div>

                <div class="feature-card animate-slide-in" style="animation-delay: 0.5s;">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="feature-title">Flexible Schedule</h3>
                    <p class="feature-description">
                        Learn at your own pace with 24/7 access to course materials and resources.
                    </p>
                </div>

                <div class="feature-card animate-slide-in" style="animation-delay: 0.6s;">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="feature-title">Mobile Learning</h3>
                    <p class="feature-description">
                        Access your courses anywhere, anytime with our mobile-optimized platform.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Courses Section -->
    <section class="featured-courses section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title animate-fade-in">Featured Courses</h2>
                <p class="section-subtitle animate-fade-in">
                    Discover our most popular courses designed by expert educators
                </p>
            </div>
            
            <div class="courses-grid">
                <?php if (empty($featured_courses)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h3>No Courses Available</h3>
                        <p>Check back soon for new courses!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($featured_courses as $index => $course): ?>
                        <div class="course-card loading" style="animation-delay: <?php echo ($index * 0.1); ?>s;">
                            <?php 
                            $imagePath = !empty($course['image']) 
                                ? 'assets/images/courses/' . $course['image'] 
                                : 'assets/images/courses/default-course.jpg';
                            ?>
                            <div class="course-image-container">
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                     alt="<?php echo htmlspecialchars($course['title']); ?>" 
                                     class="course-image"
                                     loading="lazy"
                                     onerror="this.src='assets/images/courses/default-course.jpg';"
                                     onload="this.style.opacity = '1';">
                                
                                <?php if ($course['category']): ?>
                                    <span class="course-category"><?php echo htmlspecialchars($course['category']); ?></span>
                                <?php endif; ?>
                                
                                <?php if ($course['avg_rating'] > 0): ?>
                                    <div class="course-rating">
                                        <i class="fas fa-star"></i>
                                        <span><?php echo number_format($course['avg_rating'], 1); ?></span>
                                        <small>(<?php echo $course['review_count']; ?>)</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="course-content">
                                <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                                
                                <?php if ($course['instructor_name']): ?>
                                    <div class="course-instructor">
                                        <i class="fas fa-user-tie"></i>
                                        <span><?php echo htmlspecialchars($course['instructor_name']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="course-description">
                                    <?php echo htmlspecialchars(substr($course['description'], 0, 120)) . '...'; ?>
                                </p>
                                
                                <div class="course-meta">
                                    <div class="course-stats">
                                        <?php if ($course['duration']): ?>
                                            <span class="course-duration">
                                                <i class="fas fa-clock"></i>
                                                <?php echo htmlspecialchars($course['duration']); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <span class="course-students">
                                            <i class="fas fa-users"></i>
                                            <?php echo $course['enrollment_count']; ?> student<?php echo $course['enrollment_count'] !== 1 ? 's' : ''; ?>
                                        </span>
                                    </div>
                                    
                                    <span class="course-price">
                                        <?php if ($course['price'] > 0): ?>
                                            $<?php echo number_format($course['price'], 0); ?>
                                        <?php else: ?>
                                            <span class="free-badge">Free</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <a href="course-details.php?id=<?php echo $course['course_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-arrow-right"></i>
                                    Learn More
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="text-center mb-8">
                <a href="courses.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-th-large"></i>
                    View All Courses
                </a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title animate-fade-in">Ready to Transform Your Teaching Career?</h2>
                <p class="cta-subtitle animate-fade-in">
                    Join thousands of educators who have already taken their careers to the next level with TeachVerse
                </p>
                <div class="animate-fade-in" style="animation-delay: 0.2s;">
                    <a href="auth.php?mode=register&type=user" class="cta-button">
                        <i class="fas fa-rocket"></i>
                        Start Your Journey Today
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>
                        <i class="fas fa-graduation-cap"></i>
                        TeachVerse
                    </h3>
                    <p>Transform your teaching journey with expert-led courses and professional development programs.</p>
                    <div style="margin-top: 1rem;">
                        <a href="#" style="margin-right: 1rem; color: inherit;"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="margin-right: 1rem; color: inherit;"><i class="fab fa-twitter"></i></a>
                        <a href="#" style="margin-right: 1rem; color: inherit;"><i class="fab fa-linkedin"></i></a>
                        <a href="#" style="color: inherit;"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Courses</h3>
                    <a href="courses.php">All Courses</a>
                    <a href="courses.php?category=teaching">Teaching Methods</a>
                    <a href="courses.php?category=technology">Educational Technology</a>
                    <a href="courses.php?category=management">Classroom Management</a>
                </div>
                
                <div class="footer-section">
                    <h3>Support</h3>
                    <a href="contact.php">Contact Us</a>
                    <a href="about.php">About</a>
                    <a href="#">Help Center</a>
                    <a href="#">Privacy Policy</a>
                </div>
                
                <div class="footer-section">
                    <h3>Community</h3>
                    <a href="trainers.php">Expert Trainers</a>
                    <a href="modules/reviews/index.php">Student Reviews</a>
                    <a href="#">Discussion Forums</a>
                    <a href="#">Success Stories</a>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 TeachVerse. All rights reserved. Made with <i class="fas fa-heart" style="color: #e53e3e;"></i> for educators worldwide</p>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()" aria-label="Scroll to top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const navMenu = document.getElementById('navMenu');
            const menuToggle = document.querySelector('.mobile-menu-toggle');
            
            navMenu.classList.toggle('open');
            
            // Update aria-expanded attribute
            const isOpen = navMenu.classList.contains('open');
            menuToggle.setAttribute('aria-expanded', isOpen);
            
            // Change icon
            const icon = menuToggle.querySelector('i');
            icon.className = isOpen ? 'fas fa-times' : 'fas fa-bars';
        }

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            const scrollToTopBtn = document.getElementById('scrollToTop');
            
            if (window.scrollY > 100) {
                header.style.background = 'rgba(255, 255, 255, 0.98)';
                header.style.boxShadow = '0 8px 25px rgba(0,0,0,0.1)';
                scrollToTopBtn.classList.add('visible');
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.boxShadow = '0 2px 4px rgba(0,0,0,0.05)';
                scrollToTopBtn.classList.remove('visible');
            }
        });

        // Smooth scroll function
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const navMenu = document.getElementById('navMenu');
            const menuToggle = document.querySelector('.mobile-menu-toggle');
            
            if (!navMenu.contains(e.target) && !menuToggle.contains(e.target)) {
                navMenu.classList.remove('open');
                menuToggle.setAttribute('aria-expanded', 'false');
                menuToggle.querySelector('i').className = 'fas fa-bars';
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Performance optimization: Preload critical images
        const criticalImages = [
            'assets/images/courses/default-course.jpg',
            'assets/images/trainers/default-trainer.jpg'
        ];

        criticalImages.forEach(src => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'image';
            link.href = src;
            document.head.appendChild(link);
        });

        // Image loading optimization
        document.querySelectorAll('.course-image').forEach(img => {
            img.style.opacity = '0';
            img.style.transition = 'opacity 0.3s ease';
        });
    </script>
</body>
</html>

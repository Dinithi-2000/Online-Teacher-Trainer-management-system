<?php
require_once 'config/database.php';

// Get featured courses
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT * FROM courses ORDER BY created_at DESC LIMIT 6");
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
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --dark-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --text-primary: #1a202c;
            --text-secondary: #718096;
            --text-muted: #a0aec0;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-accent: #edf2f7;
            --border-color: #e2e8f0;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 8px 25px rgba(0,0,0,0.1);
            --shadow-lg: 0 20px 40px rgba(0,0,0,0.15);
            --shadow-xl: 0 25px 50px rgba(0,0,0,0.25);
            --border-radius: 20px;
            --border-radius-sm: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--bg-primary);
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Header & Navigation */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            z-index: 1000;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo a {
            font-size: 1.8rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-menu a {
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 500;
            transition: var(--transition);
            position: relative;
            padding: 0.5rem 0;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            color: #667eea;
        }

        .nav-menu a.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-primary);
            cursor: pointer;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: var(--primary-gradient);
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') bottom center / cover no-repeat;
        }

        .hero-content {
            text-align: center;
            color: white;
            position: relative;
            z-index: 10;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4.5rem);
            font-weight: 900;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 3rem;
            opacity: 0.95;
            line-height: 1.6;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero-buttons .btn {
            font-size: 1.1rem;
            padding: 1.2rem 2.5rem;
        }

        .hero-buttons .btn-secondary {
            background: rgba(255,255,255,0.15);
            border: 2px solid rgba(255,255,255,0.8);
            color: white;
            backdrop-filter: blur(10px);
        }

        .hero-buttons .btn-secondary:hover {
            background: white;
            color: #667eea;
        }

        /* Statistics Section */
        .stats-section {
            padding: 6rem 0;
            background: white;
            position: relative;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-top: 3rem;
        }

        .stat-card {
            text-align: center;
            padding: 3rem 2rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        .stat-icon {
            font-size: 3.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 900;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Section Headers */
        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Featured Courses */
        .featured-courses {
            padding: 6rem 0;
            background: var(--bg-secondary);
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2.5rem;
        }

        .course-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }

        .course-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        .course-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            background: var(--primary-gradient);
        }

        .course-content {
            padding: 2rem;
        }

        .course-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
            line-height: 1.4;
        }

        .course-description {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--bg-accent);
            border-radius: var(--border-radius-sm);
        }

        .course-duration {
            color: var(--text-secondary);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .course-price {
            font-size: 1.2rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Features Section */
        .features-section {
            padding: 6rem 0;
            background: white;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
        }

        .feature-card {
            text-align: center;
            padding: 3rem 2rem;
            background: white;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            transition: var(--transition);
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
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: var(--transition);
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .feature-icon {
            font-size: 3.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
        }

        .feature-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .feature-description {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Reviews Section */
        .reviews-section {
            padding: 6rem 0;
            background: var(--bg-secondary);
        }

        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2.5rem;
        }

        .review-card {
            background: white;
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: var(--transition);
            position: relative;
        }

        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .review-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .review-stars {
            display: flex;
            gap: 0.25rem;
        }

        .review-comment {
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: 1.5rem;
            font-style: italic;
            position: relative;
        }

        .review-comment::before {
            content: '"';
            font-size: 4rem;
            color: var(--border-color);
            position: absolute;
            top: -1rem;
            left: -1rem;
        }

        .review-author {
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
        }

        .review-name {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .review-course {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* CTA Section */
        .cta-section {
            padding: 6rem 0;
            background: var(--primary-gradient);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,32L48,80C96,128,192,224,288,224C384,224,480,128,576,90.7C672,53,768,75,864,96C960,117,1056,139,1152,149.3C1248,160,1344,160,1392,160L1440,160L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z"></path></svg>') top center / cover no-repeat;
        }

        .cta-content {
            position: relative;
            z-index: 10;
        }

        .cta-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }

        .cta-subtitle {
            font-size: 1.2rem;
            opacity: 0.95;
            margin-bottom: 3rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-button {
            background: white;
            color: #667eea;
            font-size: 1.1rem;
            padding: 1.2rem 2.5rem;
            box-shadow: var(--shadow-lg);
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }

        /* Footer */
        .footer {
            background: #1a202c;
            color: white;
            padding: 4rem 0 2rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section h3 {
            margin-bottom: 1.5rem;
            color: white;
            font-weight: 700;
        }

        .footer-section a {
            color: #a0aec0;
            text-decoration: none;
            display: block;
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }

        .footer-section a:hover {
            color: white;
            transform: translateX(5px);
        }

        .footer-bottom {
            border-top: 1px solid #2d3748;
            padding-top: 2rem;
            text-align: center;
            color: #a0aec0;
        }

        /* Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeIn 0.8s ease-out forwards;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .slide-in-left {
            opacity: 0;
            transform: translateX(-50px);
            animation: slideInLeft 0.8s ease-out forwards;
        }

        @keyframes slideInLeft {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .slide-in-right {
            opacity: 0;
            transform: translateX(50px);
            animation: slideInRight 0.8s ease-out forwards;
        }

        @keyframes slideInRight {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
                position: fixed;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 2rem;
                box-shadow: var(--shadow-lg);
                gap: 1rem;
            }

            .nav-menu.open {
                display: flex;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .section-title {
                font-size: 2rem;
            }

            .container {
                padding: 0 1rem;
            }

            .courses-grid,
            .features-grid {
                grid-template-columns: 1fr;
            }

            .cta-title {
                font-size: 2rem;
            }
        }

        /* Scroll to top button */
        .scroll-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            opacity: 0;
            visibility: hidden;
            z-index: 1000;
        }

        .scroll-to-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .scroll-to-top:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav-container">
            <div class="logo">
                <a href="index.php">
                    <i class="fas fa-graduation-cap"></i> TeachVerse
                </a>
            </div>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="courses.php">Courses</a></li>
                <li><a href="trainers.php">Trainers</a></li>
                <li><a href="modules/reviews/index.php">Reviews</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </a>
                        <div class="dropdown-content">
                            <a href="profile.php"><i class="fas fa-user-edit"></i> Profile</a>
                            <a href="my-courses.php"><i class="fas fa-book"></i> My Courses</a>
                            <?php if (hasRole('admin')): ?>
                                <a href="admin/index.php"><i class="fas fa-cog"></i> Admin Panel</a>
                            <?php endif; ?>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="auth.php?mode=login&type=user" class="btn btn-secondary">Login</a></li>
                    <li><a href="auth.php?mode=register&type=user" class="btn btn-primary">Get Started</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content fade-in">
                <h1 class="hero-title">
                    Transform Your Teaching Journey with <span style="background: linear-gradient(45deg, #fff, #f0f9ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">TeachVerse</span>
                </h1>
                <p class="hero-subtitle">
                    Join thousands of educators worldwide in our comprehensive online training platform. 
                    Learn from expert trainers, advance your career, and unlock your teaching potential.
                </p>
                <div class="hero-buttons">
                    <a href="courses.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-rocket"></i> Explore Courses
                    </a>
                    <a href="auth.php?mode=register&type=user" class="btn btn-secondary btn-lg">
                        <i class="fas fa-play-circle"></i> Watch Demo
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title">Trusted by Educators Worldwide</h2>
                <p class="section-subtitle">Join our growing community of passionate teachers and trainers</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card slide-in-left" style="animation-delay: 0.1s;">
                    <div class="stat-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats_courses; ?>+</div>
                    <div class="stat-label">Expert-Led Courses</div>
                </div>
                
                <div class="stat-card slide-in-left" style="animation-delay: 0.2s;">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats_users; ?>+</div>
                    <div class="stat-label">Active Learners</div>
                </div>
                
                <div class="stat-card slide-in-left" style="animation-delay: 0.3s;">
                    <div class="stat-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats_enrollments; ?>+</div>
                    <div class="stat-label">Certificates Earned</div>
                </div>
                
                <div class="stat-card slide-in-left" style="animation-delay: 0.4s;">
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-number">98%</div>
                    <div class="stat-label">Success Rate</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Courses Section -->
    <section class="featured-courses">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title">Featured Courses</h2>
                <p class="section-subtitle">
                    Discover our most popular training courses designed by expert educators
                </p>
            </div>
            
            <div class="courses-grid">
                <?php if (!empty($featured_courses)): ?>
                    <?php foreach ($featured_courses as $index => $course): ?>
                    <div class="course-card fade-in" style="animation-delay: <?php echo ($index * 0.1) + 0.2; ?>s;">
                        <img src="assets/images/courses/<?php echo htmlspecialchars($course['image']); ?>" 
                             alt="<?php echo htmlspecialchars($course['title']); ?>" 
                             class="course-image"
                             onerror="this.src='assets/images/default-course.jpg'">
                        <div class="course-content">
                            <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                            <p class="course-description">
                                <?php echo htmlspecialchars(substr($course['description'], 0, 120)) . '...'; ?>
                            </p>
                            <div class="course-meta">
                                <div class="course-duration">
                                    <i class="fas fa-clock"></i> 
                                    <?php echo htmlspecialchars($course['duration']); ?>
                                </div>
                                <div class="course-price">
                                    <?php echo formatPrice($course['price']); ?>
                                </div>
                            </div>
                            <a href="course-details.php?id=<?php echo $course['course_id']; ?>" class="btn btn-primary" style="width: 100%; justify-content: center;">
                                <i class="fas fa-arrow-right"></i> Learn More
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 4rem 2rem;">
                        <div style="font-size: 4rem; color: var(--text-muted); margin-bottom: 2rem;">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h3 style="margin-bottom: 1rem; color: var(--text-primary);">Courses Coming Soon!</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                            We're preparing amazing courses for you. Check back soon!
                        </p>
                        <a href="contact.php" class="btn btn-primary">
                            <i class="fas fa-envelope"></i> Get Notified
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 3rem;">
                <a href="courses.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-arrow-right"></i> View All Courses
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title">Why Choose TeachVerse?</h2>
                <p class="section-subtitle">
                    Comprehensive tools and resources for effective teacher training and development
                </p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card fade-in" style="animation-delay: 0.1s;">
                    <div class="feature-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3 class="feature-title">Expert Instructors</h3>
                    <p class="feature-description">
                        Learn from certified educators with years of teaching experience and proven methodologies that deliver real results.
                    </p>
                </div>
                
                <div class="feature-card fade-in" style="animation-delay: 0.2s;">
                    <div class="feature-icon">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3 class="feature-title">Interactive Learning</h3>
                    <p class="feature-description">
                        Engage with interactive content, practical exercises, and real-world teaching scenarios that enhance your skills.
                    </p>
                </div>
                
                <div class="feature-card fade-in" style="animation-delay: 0.3s;">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Progress Tracking</h3>
                    <p class="feature-description">
                        Monitor your learning journey with detailed analytics, progress tracking, and achievement badges.
                    </p>
                </div>
                
                <div class="feature-card fade-in" style="animation-delay: 0.4s;">
                    <div class="feature-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3 class="feature-title">Verified Certificates</h3>
                    <p class="feature-description">
                        Earn industry-recognized certificates upon course completion to advance your teaching career.
                    </p>
                </div>
                
                <div class="feature-card fade-in" style="animation-delay: 0.5s;">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Community Support</h3>
                    <p class="feature-description">
                        Connect with fellow educators, share experiences, and get support from our active learning community.
                    </p>
                </div>
                
                <div class="feature-card fade-in" style="animation-delay: 0.6s;">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="feature-title">Learn Anywhere</h3>
                    <p class="feature-description">
                        Access your courses anytime, anywhere with our responsive mobile-friendly platform design.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Reviews Section -->
    <section class="reviews-section">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title">Student Success Stories</h2>
                <p class="section-subtitle">
                    Discover why thousands of educators trust TeachVerse for their professional development
                </p>
            </div>
            
            <div class="reviews-grid">
                <?php
                // Get latest 3 reviews for homepage
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("
                    SELECT r.*, c.title as course_title, u.name as user_name
                    FROM reviews r
                    JOIN courses c ON r.course_id = c.course_id
                    JOIN users u ON r.user_id = u.user_id
                    WHERE r.rating >= 4
                    ORDER BY r.created_at DESC
                    LIMIT 3
                ");
                $stmt->execute();
                $featured_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($featured_reviews)):
                    foreach ($featured_reviews as $index => $review):
                ?>
                    <div class="review-card fade-in" style="animation-delay: <?php echo ($index * 0.1) + 0.2; ?>s;">
                        <div class="review-rating">
                            <div class="review-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star" style="color: <?php echo $i <= $review['rating'] ? '#fbbf24' : '#e2e8f0'; ?>; font-size: 1.1rem;"></i>
                                <?php endfor; ?>
                            </div>
                            <span style="font-weight: 700; color: var(--text-primary); margin-left: 0.5rem;">
                                <?php echo $review['rating']; ?>/5
                            </span>
                        </div>
                        
                        <?php if (!empty($review['comment'])): ?>
                            <p class="review-comment">
                                <?php echo htmlspecialchars($review['comment']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="review-author">
                            <div class="review-name">
                                <?php echo htmlspecialchars($review['user_name']); ?>
                            </div>
                            <div class="review-course">
                                Course: <?php echo htmlspecialchars($review['course_title']); ?>
                            </div>
                        </div>
                    </div>
                <?php 
                    endforeach;
                else:
                ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 4rem 2rem; background: white; border-radius: var(--border-radius); box-shadow: var(--shadow-md);">
                        <div style="font-size: 4rem; color: var(--text-muted); margin-bottom: 2rem;">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3 style="margin-bottom: 1rem; color: var(--text-primary);">Be the First to Review!</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                            Enroll in a course and share your experience with the TeachVerse community.
                        </p>
                        <a href="courses.php" class="btn btn-primary">
                            <i class="fas fa-book"></i> Browse Courses
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 3rem;">
                <a href="modules/reviews/index.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-comments"></i> View All Reviews
                </a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content fade-in">
                <h2 class="cta-title">Ready to Transform Your Teaching Journey?</h2>
                <p class="cta-subtitle">
                    Join TeachVerse today and unlock your potential as an educator. 
                    Start learning from the best trainers in the industry.
                </p>
                <a href="auth.php?mode=register&type=user" class="cta-button">
                    <i class="fas fa-rocket"></i> Start Your Journey Now
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-graduation-cap"></i> TeachVerse</h3>
                    <p style="color: #a0aec0; margin-bottom: 2rem;">
                        Empowering educators worldwide with comprehensive online training programs and professional development opportunities.
                    </p>
                    <div style="display: flex; gap: 1rem;">
                        <a href="#" style="color: #a0aec0; font-size: 1.5rem; transition: var(--transition);" 
                           onmouseover="this.style.color='white'" onmouseout="this.style.color='#a0aec0'">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" style="color: #a0aec0; font-size: 1.5rem; transition: var(--transition);"
                           onmouseover="this.style.color='white'" onmouseout="this.style.color='#a0aec0'">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" style="color: #a0aec0; font-size: 1.5rem; transition: var(--transition);"
                           onmouseover="this.style.color='white'" onmouseout="this.style.color='#a0aec0'">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="#" style="color: #a0aec0; font-size: 1.5rem; transition: var(--transition);"
                           onmouseover="this.style.color='white'" onmouseout="this.style.color='#a0aec0'">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Platform</h3>
                    <a href="courses.php">Browse Courses</a>
                    <a href="trainers.php">Find Trainers</a>
                    <a href="modules/reviews/index.php">Reviews</a>
                    <a href="about.php">About Us</a>
                    <a href="contact.php">Contact Support</a>
                </div>
                
                <div class="footer-section">
                    <h3>For Students</h3>
                    <a href="dashboard.php">My Dashboard</a>
                    <a href="my-courses.php">My Courses</a>
                    <a href="certificates.php">Certificates</a>
                    <a href="progress.php">Learning Progress</a>
                    <a href="community.php">Community</a>
                </div>
                
                <div class="footer-section">
                    <h3>For Educators</h3>
                    <a href="trainer-portal.php">Trainer Portal</a>
                    <a href="create-course.php">Create Course</a>
                    <a href="trainer-resources.php">Resources</a>
                    <a href="earnings.php">Earnings</a>
                    <a href="analytics.php">Analytics</a>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 TeachVerse. All rights reserved. | Crafted with ❤️ for Educators</p>
                <div style="margin-top: 1rem; display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap;">
                    <a href="privacy.php" style="color: #a0aec0; text-decoration: none; font-size: 0.9rem;">Privacy Policy</a>
                    <a href="terms.php" style="color: #a0aec0; text-decoration: none; font-size: 0.9rem;">Terms of Service</a>
                    <a href="support.php" style="color: #a0aec0; text-decoration: none; font-size: 0.9rem;">Support</a>
                    <a href="auth.php?mode=login&type=admin" style="color: #94a3b8; text-decoration: none; font-size: 0.9rem;">
                        <i class="fas fa-shield-alt"></i> Admin Portal
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" id="scrollToTop">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        // Toggle mobile menu
        function toggleMobileMenu() {
            const navMenu = document.getElementById('navMenu');
            navMenu.classList.toggle('open');
        }

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

        // Scroll to top functionality
        const scrollToTopBtn = document.getElementById('scrollToTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('visible');
            } else {
                scrollToTopBtn.classList.remove('visible');
            }
        });

        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                }
            });
        }, observerOptions);

        // Observe all animated elements
        document.querySelectorAll('.fade-in, .slide-in-left, .slide-in-right').forEach(el => {
            el.style.animationPlayState = 'paused';
            observer.observe(el);
        });

        // Header scroll effect
        let lastScrollTop = 0;
        const header = document.querySelector('.header');

        window.addEventListener('scroll', () => {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > lastScrollTop && scrollTop > 100) {
                // Scrolling down
                header.style.transform = 'translateY(-100%)';
            } else {
                // Scrolling up
                header.style.transform = 'translateY(0)';
            }
            
            lastScrollTop = scrollTop;
        });

        // Add loading animation to course cards
        document.querySelectorAll('.course-card img').forEach(img => {
            img.addEventListener('load', function() {
                this.style.opacity = '1';
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            const navMenu = document.getElementById('navMenu');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (!navMenu.contains(e.target) && !toggle.contains(e.target)) {
                navMenu.classList.remove('open');
            }
        });

        // Add parallax effect to hero section
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const hero = document.querySelector('.hero');
            if (hero) {
                hero.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });

        // Dynamic statistics counter animation
        function animateCounter(element, target) {
            const startValue = 0;
            const duration = 2000;
            const startTime = performance.now();
            
            function updateCounter(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const currentValue = Math.floor(startValue + (target - startValue) * progress);
                
                element.textContent = currentValue + '+';
                
                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                }
            }
            
            requestAnimationFrame(updateCounter);
        }

        // Trigger counter animation when stats section is visible
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const numbers = entry.target.querySelectorAll('.stat-number');
                    numbers.forEach(num => {
                        const target = parseInt(num.textContent.replace('+', '').replace('%', ''));
                        if (!isNaN(target)) {
                            animateCounter(num, target);
                        }
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        });

        const statsSection = document.querySelector('.stats-section');
        if (statsSection) {
            statsObserver.observe(statsSection);
        }
    </script>
</body>
</html>

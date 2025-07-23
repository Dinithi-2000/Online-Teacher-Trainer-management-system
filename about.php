<?php
require_once 'config/database.php';

// Get some statistics for the about page
$pdo = getDBConnection();

try {
    // Get total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role IN ('student', 'trainer')");
    $total_users = $stmt->fetch()['total'];
    
    // Get total courses
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM courses");
    $total_courses = $stmt->fetch()['total'];
    
    // Get total enrollments
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM enrollments");
    $total_enrollments = $stmt->fetch()['total'];
    
    // Get trainers count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'trainer'");
    $total_trainers = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    $total_users = 0;
    $total_courses = 0;
    $total_enrollments = 0;
    $total_trainers = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - TeachVerse</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* About Page Specific Styles */
        .about-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .about-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="20" cy="20" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="30" r="1.5" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            animation: float 20s linear infinite;
        }
        
        @keyframes float {
            0% { transform: translate(0, 0); }
            50% { transform: translate(-10px, -10px); }
            100% { transform: translate(0, 0); }
        }
        
        .about-hero .container {
            position: relative;
            z-index: 1;
        }
        
        .about-hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            animation: slideUp 0.8s ease-out;
        }
        
        .about-hero p {
            font-size: 1.3rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            animation: slideUp 0.8s ease-out 0.2s both;
        }
        
        .about-content {
            padding: 80px 0;
        }
        
        .about-section {
            margin-bottom: 80px;
        }
        
        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            margin-bottom: 60px;
        }
        
        .about-text h2 {
            font-size: 2.5rem;
            color: #1a202c;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }
        
        .about-text p {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #4a5568;
            margin-bottom: 1.5rem;
        }
        
        .about-image {
            position: relative;
        }
        
        .about-image img {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .stats-section {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 80px 0;
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .stat-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: #1a202c;
            margin-bottom: 10px;
            display: block;
        }
        
        .stat-label {
            font-size: 1.1rem;
            color: #4a5568;
            font-weight: 500;
        }
        
        .values-section {
            padding: 80px 0;
        }
        
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }
        
        .value-card {
            text-align: center;
            padding: 40px 30px;
            border: 2px solid transparent;
            border-radius: 15px;
            transition: all 0.3s ease;
            background: linear-gradient(white, white) padding-box,
                        linear-gradient(135deg, #667eea, #764ba2) border-box;
        }
        
        .value-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.2);
        }
        
        .value-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
        }
        
        .value-card h3 {
            font-size: 1.5rem;
            color: #1a202c;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .value-card p {
            color: #4a5568;
            line-height: 1.6;
        }
        
        .team-section {
            background: #f8fafc;
            padding: 80px 0;
        }
        
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }
        
        .team-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .team-card:hover {
            transform: translateY(-10px);
        }
        
        .team-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            font-weight: 600;
        }
        
        .team-card h3 {
            font-size: 1.3rem;
            color: #1a202c;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .team-card .role {
            color: #667eea;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .team-card p {
            color: #4a5568;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .cta-section p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-cta {
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-primary-cta {
            background: white;
            color: #667eea;
        }
        
        .btn-primary-cta:hover {
            background: #f7fafc;
            transform: translateY(-2px);
        }
        
        .btn-secondary-cta {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-secondary-cta:hover {
            background: white;
            color: #667eea;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes countUp {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .about-hero h1 {
                font-size: 2.5rem;
            }
            
            .about-hero p {
                font-size: 1.1rem;
            }
            
            .about-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .about-text h2 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 20px;
            }
            
            .values-grid,
            .team-grid {
                grid-template-columns: 1fr;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav-container">
            <div class="logo">
                <a href="index.php" style="color: white; text-decoration: none;">
                    <i class="fas fa-graduation-cap"></i> TeachVerse
                </a>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="courses.php">Courses</a></li>
                <li><a href="trainers.php">Trainers</a></li>
                <li><a href="modules/reviews/index.php">Reviews</a></li>
                <li><a href="about.php" class="active">About Us</a></li>
                <li><a href="contact.php">Contact Us</a></li>
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
                    <li><a href="auth.php?mode=login&type=user" class="btn btn-secondary btn-sm">Login</a></li>
                    <li><a href="auth.php?mode=register&type=user" class="btn btn-primary btn-sm">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="about-hero">
        <div class="container">
            <h1>About TeachVerse</h1>
            <p>Empowering educators worldwide through innovative online training and professional development programs</p>
        </div>
    </section>

    <!-- About Content -->
    <section class="about-content">
        <div class="container">
            <!-- Our Story -->
            <div class="about-section">
                <div class="about-grid">
                    <div class="about-text">
                        <h2>Our Story</h2>
                        <p>TeachVerse was born from a simple yet powerful vision: to create a world where every educator has access to high-quality professional development opportunities, regardless of their location or circumstances.</p>
                        <p>Founded by a team of passionate educators and technology enthusiasts, we recognized the growing need for flexible, engaging, and effective teacher training programs that could adapt to the digital age.</p>
                        <p>Today, TeachVerse serves thousands of educators worldwide, providing them with the skills, knowledge, and confidence they need to excel in their teaching careers.</p>
                    </div>
                    <div class="about-image">
                        <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Team collaboration" />
                    </div>
                </div>
            </div>

            <!-- Our Mission -->
            <div class="about-section">
                <div class="about-grid">
                    <div class="about-image">
                        <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Online learning" />
                    </div>
                    <div class="about-text">
                        <h2>Our Mission</h2>
                        <p>We believe that great teachers change the world, one student at a time. Our mission is to empower educators with cutting-edge training programs that enhance their teaching skills and inspire their students.</p>
                        <p>Through innovative technology, expert instruction, and a supportive community, we're committed to making professional development accessible, engaging, and effective for educators everywhere.</p>
                        <p>We strive to bridge the gap between traditional education and modern teaching methodologies, ensuring our learners are equipped for the challenges of tomorrow's classrooms.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <h2 style="font-size: 2.5rem; color: #1a202c; margin-bottom: 20px; font-weight: 700;">Our Impact</h2>
            <p style="font-size: 1.2rem; color: #4a5568; max-width: 600px; margin: 0 auto;">Numbers that reflect our commitment to educational excellence</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="stat-number" data-target="<?php echo $total_users; ?>">0</span>
                    <div class="stat-label">Active Learners</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <span class="stat-number" data-target="<?php echo $total_courses; ?>">0</span>
                    <div class="stat-label">Courses Available</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <span class="stat-number" data-target="<?php echo $total_enrollments; ?>">0</span>
                    <div class="stat-label">Course Enrollments</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <span class="stat-number" data-target="<?php echo $total_trainers; ?>">0</span>
                    <div class="stat-label">Expert Trainers</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="values-section">
        <div class="container">
            <div style="text-align: center; margin-bottom: 40px;">
                <h2 style="font-size: 2.5rem; color: #1a202c; margin-bottom: 20px; font-weight: 700;">Our Values</h2>
                <p style="font-size: 1.2rem; color: #4a5568; max-width: 600px; margin: 0 auto;">The principles that guide everything we do</p>
            </div>
            
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3>Innovation</h3>
                    <p>We continuously explore new technologies and methodologies to enhance the learning experience and stay ahead of educational trends.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Excellence</h3>
                    <p>We are committed to delivering the highest quality content and support, ensuring every learner achieves their professional goals.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Community</h3>
                    <p>We foster a supportive learning environment where educators can connect, collaborate, and grow together.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3>Accessibility</h3>
                    <p>We believe quality education should be accessible to everyone, anywhere, at any time, breaking down geographical barriers.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <div class="container">
            <div style="text-align: center; margin-bottom: 40px;">
                <h2 style="font-size: 2.5rem; color: #1a202c; margin-bottom: 20px; font-weight: 700;">Meet Our Team</h2>
                <p style="font-size: 1.2rem; color: #4a5568; max-width: 600px; margin: 0 auto;">The passionate individuals behind TeachVerse</p>
            </div>
            
            <div class="team-grid">
                <div class="team-card">
                    <div class="team-avatar">S</div>
                    <h3>Sarah Johnson</h3>
                    <div class="role">CEO & Founder</div>
                    <p>Former educator with 15+ years of experience in curriculum development and teacher training programs.</p>
                </div>
                
                <div class="team-card">
                    <div class="team-avatar">M</div>
                    <h3>Michael Chen</h3>
                    <div class="role">CTO</div>
                    <p>Technology expert specializing in educational platforms and learning management systems.</p>
                </div>
                
                <div class="team-card">
                    <div class="team-avatar">E</div>
                    <h3>Emily Rodriguez</h3>
                    <div class="role">Head of Content</div>
                    <p>Educational content specialist with expertise in instructional design and online learning.</p>
                </div>
                
                <div class="team-card">
                    <div class="team-avatar">D</div>
                    <h3>David Kim</h3>
                    <div class="role">Head of Community</div>
                    <p>Community building expert focused on creating engaging learning experiences for educators.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Transform Your Teaching?</h2>
            <p>Join thousands of educators who are already advancing their careers with TeachVerse. Start your journey today!</p>
            
            <div class="cta-buttons">
                <a href="auth.php?mode=register&type=user" class="btn-cta btn-primary-cta">
                    <i class="fas fa-rocket"></i>
                    Get Started Free
                </a>
                <a href="courses.php" class="btn-cta btn-secondary-cta">
                    <i class="fas fa-book-open"></i>
                    Browse Courses
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background: #1a202c; color: white; padding: 3rem 0;">
        <div class="container">
            <div style="text-align: center;">
                <div style="margin-bottom: 2rem;">
                    <h3 style="color: white; margin-bottom: 1rem;">
                        <i class="fas fa-graduation-cap"></i> TeachVerse
                    </h3>
                    <p style="opacity: 0.8; max-width: 500px; margin: 0 auto;">
                        Empowering educators worldwide through innovative online training and professional development.
                    </p>
                </div>
                
                <div style="display: flex; justify-content: center; gap: 2rem; margin-bottom: 2rem; flex-wrap: wrap;">
                    <a href="index.php" style="color: #94a3b8; text-decoration: none;">Home</a>
                    <a href="courses.php" style="color: #94a3b8; text-decoration: none;">Courses</a>
                    <a href="trainers.php" style="color: #94a3b8; text-decoration: none;">Trainers</a>
                    <a href="about.php" style="color: #94a3b8; text-decoration: none;">About Us</a>
                    <a href="contact.php" style="color: #94a3b8; text-decoration: none;">Contact Us</a>
                </div>
                
                <div style="border-top: 1px solid #374151; padding-top: 2rem;">
                    <p>&copy; 2025 TeachVerse. All rights reserved. | Developed by Team TeachVerse</p>
                    <div style="margin-top: 8px;">
                        <a href="auth.php?mode=login&type=admin" style="color: #94a3b8; font-size: 12px; text-decoration: none;">
                            <i class="fas fa-shield-alt"></i> Admin Portal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
        // Counter Animation
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');
            
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-target'));
                const duration = 2000;
                const increment = target / (duration / 16);
                let current = 0;
                
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        counter.textContent = target;
                        clearInterval(timer);
                    } else {
                        counter.textContent = Math.floor(current);
                    }
                }, 16);
            });
        }
        
        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    if (entry.target.classList.contains('stats-section')) {
                        animateCounters();
                    }
                }
            });
        }, observerOptions);
        
        // Observe stats section
        document.addEventListener('DOMContentLoaded', () => {
            const statsSection = document.querySelector('.stats-section');
            if (statsSection) {
                observer.observe(statsSection);
            }
        });
    </script>
</body>
</html>

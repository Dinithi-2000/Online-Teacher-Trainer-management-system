<?php
require_once 'config/database.php';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    $phone = sanitize($_POST['phone'] ?? '');
    $priority = sanitize($_POST['priority'] ?? 'normal');
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    
    if (empty($subject)) {
        $errors[] = 'Subject is required.';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required.';
    }
    
    if (empty($errors)) {
        // Save contact message to database
        try {
            $pdo = getDBConnection();
            
            // Create contacts table if it doesn't exist
            $create_table = "CREATE TABLE IF NOT EXISTS contacts (
                contact_id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(20),
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
                status ENUM('new', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $pdo->exec($create_table);
            
            // Insert contact message
            $stmt = $pdo->prepare("INSERT INTO contacts (name, email, phone, subject, message, priority) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $subject, $message, $priority]);
            
            $success = 'Thank you for your message! We\'ll get back to you soon.';
            
            // Clear form data
            $_POST = [];
            
        } catch (Exception $e) {
            $error = 'There was an error sending your message. Please try again.';
        }
    } else {
        $error = implode(' ', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - TeachVerse</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Contact Page Specific Styles */
        .contact-hero {
            background: linear-gradient(135deg, #4c51bf 0%, #667eea 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .contact-hero::before {
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
        
        .contact-hero .container {
            position: relative;
            z-index: 1;
        }
        
        .contact-hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            animation: slideUp 0.8s ease-out;
        }
        
        .contact-hero p {
            font-size: 1.3rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            animation: slideUp 0.8s ease-out 0.2s both;
        }
        
        .contact-content {
            padding: 80px 0;
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            margin-bottom: 60px;
        }
        
        .contact-info {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 50px 40px;
            border-radius: 20px;
            position: relative;
        }
        
        .contact-info h2 {
            font-size: 2.2rem;
            color: #1a202c;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }
        
        .contact-info p {
            color: #4a5568;
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 2rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .contact-item:hover {
            transform: translateX(10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .contact-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #4c51bf 0%, #667eea 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .contact-details h3 {
            font-size: 1.2rem;
            color: #1a202c;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .contact-details p {
            color: #4a5568;
            margin: 0;
            font-size: 0.95rem;
        }
        
        .contact-form-container {
            background: white;
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .contact-form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, #4c51bf 0%, #667eea 100%);
            border-radius: 20px 20px 0 0;
        }
        
        .contact-form-container h2 {
            font-size: 2.2rem;
            color: #1a202c;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3748;
            font-size: 14px;
        }
        
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fafafa;
            font-family: 'Inter', sans-serif;
        }
        
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #4c51bf;
            background: white;
            box-shadow: 0 0 0 3px rgba(76, 81, 191, 0.1);
            transform: translateY(-2px);
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .priority-selector {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 8px;
        }
        
        .priority-option {
            position: relative;
        }
        
        .priority-option input[type="radio"] {
            display: none;
        }
        
        .priority-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 8px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 12px;
            text-align: center;
            background: white;
        }
        
        .priority-option input[type="radio"]:checked + label {
            border-color: #4c51bf;
            background: #4c51bf;
            color: white;
        }
        
        .priority-low label { border-color: #48bb78; }
        .priority-low input:checked + label { background: #48bb78; border-color: #48bb78; }
        
        .priority-high label { border-color: #ed8936; }
        .priority-high input:checked + label { background: #ed8936; border-color: #ed8936; }
        
        .priority-urgent label { border-color: #f56565; }
        .priority-urgent input:checked + label { background: #f56565; border-color: #f56565; }
        
        .submit-btn {
            background: linear-gradient(135deg, #4c51bf 0%, #667eea 100%);
            color: white;
            padding: 18px 40px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(76, 81, 191, 0.4);
        }
        
        .submit-btn:active {
            transform: translateY(-1px);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: #fed7d7;
            border: 2px solid #fc8181;
            color: #c53030;
        }
        
        .alert-success {
            background: #c6f6d5;
            border: 2px solid #68d391;
            color: #2f855a;
        }
        
        .map-section {
            background: #f8fafc;
            padding: 80px 0;
        }
        
        .map-container {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4a5568;
            font-size: 1.1rem;
        }
        
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-top: 60px;
        }
        
        .quick-link {
            background: white;
            padding: 30px 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .quick-link:hover {
            transform: translateY(-10px);
            color: inherit;
        }
        
        .quick-link-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #4c51bf 0%, #667eea 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 1.5rem;
        }
        
        .quick-link h3 {
            font-size: 1.2rem;
            color: #1a202c;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .quick-link p {
            color: #4a5568;
            font-size: 0.9rem;
            margin: 0;
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
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .contact-hero h1 {
                font-size: 2.5rem;
            }
            
            .contact-hero p {
                font-size: 1.1rem;
            }
            
            .contact-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .contact-info,
            .contact-form-container {
                padding: 30px 25px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .priority-selector {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .quick-links {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .contact-item {
                padding: 15px;
            }
            
            .contact-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
        }
        
        /* Loading Animation */
        .loading {
            pointer-events: none;
            opacity: 0.7;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
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
                <li><a href="about.php">About Us</a></li>
                <li><a href="contact.php" class="active">Contact Us</a></li>
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
    <section class="contact-hero">
        <div class="container">
            <h1>Get in Touch</h1>
            <p>We're here to help you on your educational journey. Reach out to us anytime!</p>
        </div>
    </section>

    <!-- Contact Content -->
    <section class="contact-content">
        <div class="container">
            <div class="contact-grid">
                <!-- Contact Information -->
                <div class="contact-info">
                    <h2>Let's Connect</h2>
                    <p>Have questions about our courses, need technical support, or want to discuss partnership opportunities? We'd love to hear from you!</p>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-details">
                            <h3>Visit Us</h3>
                            <p>123 Education Street<br>Learning City, LC 12345<br>United States</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-details">
                            <h3>Call Us</h3>
                            <p>+1 (555) 123-4567<br>Mon - Fri: 9:00 AM - 6:00 PM EST</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-details">
                            <h3>Email Us</h3>
                            <p>support@teachverse.com<br>info@teachverse.com</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="contact-details">
                            <h3>Support Hours</h3>
                            <p>24/7 Online Support<br>Live Chat Available</p>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="contact-form-container">
                    <h2>Send us a Message</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="contact-form" id="contactForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" class="form-input" 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                                       placeholder="Enter your full name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-input" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                       placeholder="Enter your email" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-input" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                                       placeholder="Enter your phone number">
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">Subject *</label>
                                <input type="text" id="subject" name="subject" class="form-input" 
                                       value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" 
                                       placeholder="What's this about?" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Priority Level</label>
                            <div class="priority-selector">
                                <div class="priority-option priority-low">
                                    <input type="radio" id="low" name="priority" value="low" 
                                           <?php echo ($_POST['priority'] ?? '') === 'low' ? 'checked' : ''; ?>>
                                    <label for="low">Low</label>
                                </div>
                                <div class="priority-option">
                                    <input type="radio" id="normal" name="priority" value="normal" 
                                           <?php echo ($_POST['priority'] ?? 'normal') === 'normal' ? 'checked' : ''; ?>>
                                    <label for="normal">Normal</label>
                                </div>
                                <div class="priority-option priority-high">
                                    <input type="radio" id="high" name="priority" value="high" 
                                           <?php echo ($_POST['priority'] ?? '') === 'high' ? 'checked' : ''; ?>>
                                    <label for="high">High</label>
                                </div>
                                <div class="priority-option priority-urgent">
                                    <input type="radio" id="urgent" name="priority" value="urgent" 
                                           <?php echo ($_POST['priority'] ?? '') === 'urgent' ? 'checked' : ''; ?>>
                                    <label for="urgent">Urgent</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" class="form-textarea" 
                                      placeholder="Tell us how we can help you..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i>
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="quick-links">
                <a href="courses.php" class="quick-link">
                    <div class="quick-link-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3>Browse Courses</h3>
                    <p>Explore our comprehensive course catalog</p>
                </a>
                
                <a href="trainers.php" class="quick-link">
                    <div class="quick-link-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3>Meet Trainers</h3>
                    <p>Connect with our expert instructors</p>
                </a>
                
                <a href="about.php" class="quick-link">
                    <div class="quick-link-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <h3>About TeachVerse</h3>
                    <p>Learn more about our mission and values</p>
                </a>
                
                <a href="auth.php?mode=register&type=user" class="quick-link">
                    <div class="quick-link-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3>Join Us</h3>
                    <p>Start your learning journey today</p>
                </a>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
            <h2 style="text-align: center; font-size: 2.5rem; color: #1a202c; margin-bottom: 40px; font-weight: 700;">Find Us</h2>
            <div class="map-container">
                <div style="text-align: center;">
                    <i class="fas fa-map-marked-alt" style="font-size: 3rem; color: #4c51bf; margin-bottom: 20px;"></i>
                    <h3 style="color: #1a202c; margin-bottom: 10px;">Interactive Map Coming Soon</h3>
                    <p>We're located in the heart of Learning City, easily accessible by public transport.</p>
                </div>
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
        // Form submission with loading state
        document.getElementById('contactForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            // Reset after 5 seconds in case of issues
            setTimeout(() => {
                submitBtn.classList.remove('loading');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });
        
        // Real-time form validation
        const inputs = document.querySelectorAll('.form-input, .form-textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() !== '') {
                    this.style.borderColor = '#48bb78';
                }
            });
            
            input.addEventListener('focus', function() {
                this.style.borderColor = '#4c51bf';
            });
        });
        
        // Email validation
        const emailInput = document.getElementById('email');
        emailInput.addEventListener('input', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value && !emailRegex.test(this.value)) {
                this.style.borderColor = '#f56565';
            } else if (this.value) {
                this.style.borderColor = '#48bb78';
            }
        });
        
        // Character counter for message
        const messageTextarea = document.getElementById('message');
        const maxLength = 1000;
        
        // Add character counter
        const counter = document.createElement('div');
        counter.style.cssText = 'text-align: right; font-size: 12px; color: #4a5568; margin-top: 5px;';
        messageTextarea.parentNode.appendChild(counter);
        
        messageTextarea.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
            counter.textContent = `${this.value.length}/${maxLength} characters`;
            
            if (remaining < 50) {
                counter.style.color = '#f56565';
            } else {
                counter.style.color = '#4a5568';
            }
        });
        
        // Trigger initial counter
        messageTextarea.dispatchEvent(new Event('input'));
    </script>
</body>
</html>

<?php
// Simple database connection test
require_once 'config.php';
require_once 'database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test - EduMentor Pro</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            display: flex;
            align-items: center;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            display: flex;
            align-items: center;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            display: flex;
            align-items: center;
        }
        .icon {
            font-size: 20px;
            margin-right: 10px;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin: 10px 10px 0 0;
            font-weight: 500;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #1e7e34;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .status-active {
            color: #28a745;
            font-weight: 600;
        }
        .status-inactive {
            color: #dc3545;
            font-weight: 600;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        h2 {
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 EduMentor Pro</h1>
            <p>Database Connection & Setup</p>
        </div>
        
        <div class="content">
            <?php
            echo "<div class='section'>";
            echo "<h2>🔌 Database Connection Test</h2>";
            
            try {
                $database = new Database();
                $db = $database->getConnection();
                
                if ($db) {
                    echo "<div class='success'>";
                    echo "<span class='icon'>✅</span>";
                    echo "<div>";
                    echo "<strong>Successfully connected to database!</strong><br>";
                    echo "Connection established to: <strong>" . DB_NAME . "</strong> on <strong>" . DB_HOST . "</strong>";
                    echo "</div>";
                    echo "</div>";
                    
                    // Get database info
                    $stmt = $db->query("SELECT DATABASE() as db_name, VERSION() as version");
                    $info = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    echo "<div class='info'>";
                    echo "<span class='icon'>ℹ️</span>";
                    echo "<div>";
                    echo "Database: <strong>{$info['db_name']}</strong><br>";
                    echo "MySQL Version: <strong>{$info['version']}</strong>";
                    echo "</div>";
                    echo "</div>";
                    
                } else {
                    echo "<div class='error'>";
                    echo "<span class='icon'>❌</span>";
                    echo "<strong>Failed to connect to database!</strong>";
                    echo "</div>";
                    throw new Exception("Database connection failed");
                }
            } catch (Exception $e) {
                echo "<div class='error'>";
                echo "<span class='icon'>❌</span>";
                echo "<strong>Database connection error:</strong> " . $e->getMessage();
                echo "</div>";
                echo "</div></div></body></html>";
                exit;
            }
            echo "</div>";
            
            // Check and create tables
            echo "<div class='section'>";
            echo "<h2>🗄️ Database Tables</h2>";
            
            try {
                // Check if tables exist
                $stmt = $db->query("SHOW TABLES");
                $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (empty($existingTables)) {
                    echo "<div class='info'>";
                    echo "<span class='icon'>ℹ️</span>";
                    echo "<strong>No tables found. Creating database tables...</strong>";
                    echo "</div>";
                    
                    if (createTables()) {
                        echo "<div class='success'>";
                        echo "<span class='icon'>✅</span>";
                        echo "<strong>All database tables created successfully!</strong>";
                        echo "</div>";
                        
                        // Refresh table list
                        $stmt = $db->query("SHOW TABLES");
                        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    } else {
                        echo "<div class='error'>";
                        echo "<span class='icon'>❌</span>";
                        echo "<strong>Error creating database tables!</strong>";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='success'>";
                    echo "<span class='icon'>✅</span>";
                    echo "<strong>Database tables already exist!</strong>";
                    echo "</div>";
                }
                
                // Display table information
                if (!empty($existingTables)) {
                    echo "<table>";
                    echo "<thead>";
                    echo "<tr><th>Table Name</th><th>Records</th><th>Status</th><th>Actions</th></tr>";
                    echo "</thead>";
                    echo "<tbody>";
                    
                    foreach ($existingTables as $table) {
                        $countStmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
                        $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
                        
                        echo "<tr>";
                        echo "<td><strong>$table</strong></td>";
                        echo "<td>$count records</td>";
                        echo "<td><span class='status-active'>✅ Active</span></td>";
                        echo "<td>";
                        if ($count > 0) {
                            echo "<small>Has data</small>";
                        } else {
                            echo "<small>Empty</small>";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                    echo "</tbody></table>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>";
                echo "<span class='icon'>❌</span>";
                echo "<strong>Error checking tables:</strong> " . $e->getMessage();
                echo "</div>";
            }
            echo "</div>";
            
            // Check for admin user
            echo "<div class='section'>";
            echo "<h2>👤 Admin User Status</h2>";
            
            try {
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
                $stmt->execute();
                $adminCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($adminCount > 0) {
                    echo "<div class='success'>";
                    echo "<span class='icon'>✅</span>";
                    echo "<strong>Admin user exists!</strong> You can log in to the admin panel.";
                    echo "</div>";
                    
                    echo "<div class='info'>";
                    echo "<span class='icon'>ℹ️</span>";
                    echo "<div>";
                    echo "<strong>Default Admin Credentials:</strong><br>";
                    echo "Email: <code>admin@edumentor.com</code><br>";
                    echo "Password: <code>admin123</code><br>";
                    echo "<small>Please change the default password after first login.</small>";
                    echo "</div>";
                    echo "</div>";
                } else {
                    echo "<div class='error'>";
                    echo "<span class='icon'>❌</span>";
                    echo "<strong>No admin user found!</strong> You need to create an admin user.";
                    echo "</div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>";
                echo "<span class='icon'>❌</span>";
                echo "<strong>Error checking admin user:</strong> " . $e->getMessage();
                echo "</div>";
            }
            echo "</div>";
            
            // Final status and actions
            echo "<div class='section'>";
            echo "<h2>🚀 Next Steps</h2>";
            
            echo "<div class='success'>";
            echo "<span class='icon'>🎉</span>";
            echo "<strong>Database setup is complete!</strong> Your EduMentor Pro platform is ready to use.";
            echo "</div>";
            
            echo "<h3>Quick Actions:</h3>";
            echo "<a href='../admin-side/index.html' class='btn'>🏠 Admin Dashboard</a>";
            echo "<a href='../user-side/index.html' class='btn'>👥 User Website</a>";
            echo "<a href='http://localhost/phpmyadmin/index.php?route=/database/structure&db=edumentor_pro' class='btn' target='_blank'>🗄️ phpMyAdmin</a>";
            echo "<a href='db-test.php' class='btn btn-success'>🔄 Refresh Test</a>";
            
            echo "</div>";
            ?>
        </div>
    </div>
</body>
</html>

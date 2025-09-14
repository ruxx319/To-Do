<?php
require_once '../includes/header.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();

// Only allow users to see this page
if ($_SESSION['role'] !== 'user') {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// You can still fetch stats if you want
$userId = intval($_SESSION['id']);
$total_tasks = $conn->query("SELECT COUNT(*) FROM tasks WHERE user_id=$userId")->fetchColumn();
$completed   = $conn->query("SELECT COUNT(*) FROM tasks WHERE user_id=$userId AND status='completed'")->fetchColumn();
$pending     = $conn->query("SELECT COUNT(*) FROM tasks WHERE user_id=$userId AND status='pending'")->fetchColumn();
$overdue     = $conn->query("SELECT COUNT(*) FROM tasks WHERE user_id=$userId AND due_date < CURDATE() AND status='pending'")->fetchColumn();
?>
<!DOCTYPE html>
< lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - To-Do List App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: #333;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo i {
            font-size: 28px;
            color: #6a11cb;
        }
        
        .logo h1 {
            font-weight: 700;
            color: #2d2d2d;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            gap: 15px;
        }
        
        nav a {
            text-decoration: none;
            color: #444;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        nav a:hover, nav a.active {
            background-color: #6a11cb;
            color: white;
        }
        
        .user-nav {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 10px 0;
            margin-bottom: 30px;
        }
        
        .user-nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6a11cb;
            font-weight: bold;
        }
        
        .user-type {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .user-actions a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .user-actions a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 25px;
        }
        
        .sidebar {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            height: fit-content;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 10px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            text-decoration: none;
            color: #444;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: #6a11cb;
            color: white;
        }
        
        .sidebar-menu i {
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        }
        
        .welcome-banner h2 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-card i {
            font-size: 40px;
            margin-bottom: 15px;
            color: #6a11cb;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: #2d2d2d;
        }
        
        .stat-label {
            color: #666;
            font-weight: 600;
        }
        
        .tasks-section {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .section-header h3 {
            font-size: 1.5rem;
            color: #2d2d2d;
        }
        
        .btn {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .task-list {
            list-style: none;
        }
        
        .task-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .task-item:last-child {
            border-bottom: none;
        }
        
        .task-checkbox {
            margin-right: 15px;
        }
        
        .task-content {
            flex-grow: 1;
        }
        
        .task-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .task-due {
            font-size: 0.9rem;
            color: #666;
        }
        
        .task-priority {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 15px;
        }
        
        .priority-high {
            background-color: #ffebee;
            color: #f44336;
        }
        
        .priority-medium {
            background-color: #fff8e1;
            color: #ffc107;
        }
        
        .priority-low {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .user-specific-content {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        }
        
        .user-specific-content h3 {
            font-size: 1.5rem;
            color: #2d2d2d;
            margin-bottom: 20px;
        }
        
        .admin-features, .premium-features, .regular-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .feature-card {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            padding: 20px;
            border-radius: 12px;
            color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .feature-card:nth-child(2) {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
        }
        
        .feature-card:nth-child(3) {
            background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);
        }
        
        .feature-card i {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .feature-card h4 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        
        footer {
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            text-align: center;
            padding: 30px;
            margin-top: 50px;
            border-radius: 10px;
        }
        
        .user-switcher {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            text-align: center;
        }
        
        .user-switcher h3 {
            margin-bottom: 15px;
            color: #2d2d2d;
        }
        
        .user-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .user-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .regular-btn {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            color: white;
        }
        
        .premium-btn {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            color: white;
        }
        
        .admin-btn {
            background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);
            color: white;
        }
        
        .user-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        @media (max-width: 900px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                order: 2;
            }
            
            .main-content {
                order: 1;
            }
            
            .header-content, .user-nav-content {
                flex-direction: column;
                gap: 15px;
            }
            
            nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-tasks"></i>
                    <h1>To-Do List App</h1>
                </div>
                <nav>
                    <ul>
                        <li><a href="#" class="active">Home</a></li>
                        <li><a href="#">Features</a></li>
                        <li><a href="#">About</a></li>
                        <li><a href="#">Contact</a></li>
                        <li><a href="#">Help</a></li>
                    </ul>
                </nav>
            </div>
        </header>

        <div class="user-switcher">
            <h3>Switch User View (for demonstration):</h3>
            <div class="user-buttons">
                <button class="user-btn regular-btn" onclick="switchUser('regular')">Regular User</button>
                <button class="user-btn premium-btn" onclick="switchUser('premium')">Premium User</button>
                <button class="user-btn admin-btn" onclick="switchUser('admin')">Administrator</button>
            </div>
        </div>

        <div class="user-nav" id="user-nav">
            <!-- User navigation will be dynamically inserted here -->
        </div>

        <div class="dashboard">
            <div class="sidebar">
                <ul class="sidebar-menu" id="sidebar-menu">
                    <!-- Sidebar menu will be dynamically inserted here -->
                </ul>
            </div>
            
            <div class="main-content">
                <div class="welcome-banner">
                    <h2>Welcome, <span id="welcome-name">User</span>!</h2>
                    <p>This is your personalized dashboard. Here you can manage your tasks, view your progress, and access special features based on your account type.</p>
                </div>
                
                <div class="stats-cards">
                    <div class="stat-card">
                        <i class="fas fa-tasks"></i>
                        <div class="stat-number" id="total-tasks">12</div>
                        <div class="stat-label">Total Tasks</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-check-circle"></i>
                        <div class="stat-number" id="completed-tasks">7</div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-clock"></i>
                        <div class="stat-number" id="pending-tasks">5</div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-exclamation-circle"></i>
                        <div class="stat-number" id="overdue-tasks">1</div>
                        <div class="stat-label">Overdue</div>
                    </div>
                </div>
                
                <div class="tasks-section">
                    <div class="section-header">
                        <h3>Recent Tasks</h3>
                        <a href="#" class="btn">View All Tasks</a>
                    </div>
                    
                    <ul class="task-list">
                        <li class="task-item">
                            <input type="checkbox" class="task-checkbox">
                            <div class="task-content">
                                <div class="task-title">Complete project proposal</div>
                                <div class="task-due">Due: Today, 5:00 PM</div>
                            </div>
                            <span class="task-priority priority-high">High</span>
                        </li>
                        <li class="task-item">
                            <input type="checkbox" class="task-checkbox" checked>
                            <div class="task-content">
                                <div class="task-title">Team meeting</div>
                                <div class="task-due">Completed: Today, 10:00 AM</div>
                            </div>
                            <span class="task-priority priority-medium">Medium</span>
                        </li>
                        <li class="task-item">
                            <input type="checkbox" class="task-checkbox">
                            <div class="task-content">
                                <div class="task-title">Research competitors</div>
                                <div class="task-due">Due: Tomorrow, 3:00 PM</div>
                            </div>
                            <span class="task-priority priority-medium">Medium</span>
                        </li>
                    </ul>
                </div>
                
                <div class="user-specific-content" id="user-specific-content">
                    <!-- User-specific content will be dynamically inserted here -->
                </div>
            </div>
        </div>

        <footer>
            <p>© 2025 To-Do List App. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // User data with unique navigation options
        const userData = {
            regular: {
                name: "John Doe",
                email: "john@example.com",
                type: "Regular User",
                nav: `
                    <div class="user-nav-content">
                        <div class="user-info">
                            <div class="user-avatar">JD</div>
                            <div>
                                <div>John Doe</div>
                                <div class="user-type">Regular User</div>
                            </div>
                        </div>
                        <div class="user-actions">
                            <a href="#"><i class="fas fa-tasks"></i> My Tasks</a>
                            <a href="#"><i class="fas fa-user"></i> Profile</a>
                            <a href="#"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                `,
                sidebar: `
                    <li><a href="#" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="#"><i class="fas fa-tasks"></i> My Tasks</a></li>
                    <li><a href="#"><i class="fas fa-calendar"></i> Calendar</a></li>
                    <li><a href="#"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                `,
                content: `
                    <h3>Regular User Features</h3>
                    <div class="regular-features">
                        <div class="feature-card">
                            <i class="fas fa-check-circle"></i>
                            <h4>Task Completion</h4>
                            <p>Mark tasks as completed and track your progress</p>
                        </div>
                        <div class="feature-card">
                            <i class="fas fa-bell"></i>
                            <h4>Basic Reminders</h4>
                            <p>Get notified about upcoming due dates</p>
                        </div>
                    </div>
                `
            },
            premium: {
                name: "Sarah Johnson",
                email: "sarah@example.com",
                type: "Premium User",
                nav: `
                    <div class="user-nav-content">
                        <div class="user-info">
                            <div class="user-avatar">SJ</div>
                            <div>
                                <div>Sarah Johnson</div>
                                <div class="user-type">Premium User</div>
                            </div>
                        </div>
                        <div class="user-actions">
                            <a href="#"><i class="fas fa-tasks"></i> My Tasks</a>
                            <a href="#"><i class="fas fa-chart-line"></i> Analytics</a>
                            <a href="#"><i class="fas fa-user"></i> Profile</a>
                            <a href="#"><i class="fas fa-crown"></i> Premium Features</a>
                            <a href="#"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                `,
                sidebar: `
                    <li><a href="#" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="#"><i class="fas fa-tasks"></i> My Tasks</a></li>
                    <li><a href="#"><i class="fas fa-calendar"></i> Calendar</a></li>
                    <li><a href="#"><i class="fas fa-chart-line"></i> Analytics</a></li>
                    <li><a href="#"><i class="fas fa-file-export"></i> Export</a></li>
                    <li><a href="#"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                `,
                content: `
                    <h3>Premium User Features</h3>
                    <div class="premium-features">
                        <div class="feature-card">
                            <i class="fas fa-chart-line"></i>
                            <h4>Advanced Analytics</h4>
                            <p>Get detailed insights into your productivity patterns</p>
                        </div>
                        <div class="feature-card">
                            <i class="fas fa-file-export"></i>
                            <h4>Data Export</h4>
                            <p>Export your tasks and reports in multiple formats</p>
                        </div>
                        <div class="feature-card">
                            <i class="fas fa-palette"></i>
                            <h4>Custom Themes</h4>
                            <p>Personalize your interface with custom colors</p>
                        </div>
                    </div>
                `
            },
            admin: {
                name: "Admin User",
                email: "admin@example.com",
                type: "Administrator",
                nav: `
                    <div class="user-nav-content">
                        <div class="user-info">
                            <div class="user-avatar">AD</div>
                            <div>
                                <div>Admin User</div>
                                <div class="user-type">Administrator</div>
                            </div>
                        </div>
                        <div class="user-actions">
                            <a href="#"><i class="fas fa-tasks"></i> My Tasks</a>
                            <a href="#"><i class="fas fa-users"></i> User Management</a>
                            <a href="#"><i class="fas fa-cog"></i> System Settings</a>
                            <a href="#"><i class="fas fa-chart-bar"></i> Reports</a>
                            <a href="#"><i class="fas fa-user"></i> Profile</a>
                            <a href="#"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                `,
                sidebar: `
                    <li><a href="#" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="#"><i class="fas fa-tasks"></i> My Tasks</a></li>
                    <li><a href="#"><i class="fas fa-users"></i> User Management</a></li>
                    <li><a href="#"><i class="fas fa-cog"></i> System Settings</a></li>
                    <li><a href="#"><i class="fas fa-database"></i> Database</a></li>
                    <li><a href="#"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <li><a href="#"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                `,
                content: `
                    <h3>Administrator Features</h3>
                    <div class="admin-features">
                        <div class="feature-card">
                            <i class="fas fa-users"></i>
                            <h4>User Management</h4>
                            <p>Manage all users, their roles and permissions</p>
                        </div>
                        <div class="feature-card">
                            <i class="fas fa-cog"></i>
                            <h4>System Settings</h4>
                            <p>Configure application settings and preferences</p>
                        </div>
                        <div class="feature-card">
                            <i class="fas fa-database"></i>
                            <h4>Database Admin</h4>
                            <p>Access and manage the application database</p>
                        </div>
                        <div class="feature-card">
                            <i class="fas fa-chart-bar"></i>
                            <h4>Advanced Reports</h4>
                            <p>Generate detailed system usage reports</p>
                        </div>
                    </div>
                `
            }
        };
        
        // Function to switch user view
        function switchUser(userType) {
            const user = userData[userType];
            
            // Update user navigation
            document.getElementById('user-nav').innerHTML = user.nav;
            
            // Update sidebar
            document.getElementById('sidebar-menu').innerHTML = user.sidebar;
            
            // Update welcome message
            document.getElementById('welcome-name').textContent = user.name;
            
            // Update user-specific content
            document.getElementById('user-specific-content').innerHTML = user.content;
            
            // Update stats based on user type
            if (userType === 'regular') {
                document.getElementById('total-tasks').textContent = '12';
                document.getElementById('completed-tasks').textContent = '7';
                document.getElementById('pending-tasks').textContent = '5';
                document.getElementById('overdue-tasks').textContent = '1';
            } else if (userType === 'premium') {
                document.getElementById('total-tasks').textContent = '23';
                document.getElementById('completed-tasks').textContent = '15';
                document.getElementById('pending-tasks').textContent = '8';
                document.getElementById('overdue-tasks').textContent = '0';
            } else if (userType === 'admin') {
                document.getElementById('total-tasks').textContent = '8';
                document.getElementById('completed-tasks').textContent = '6';
                document.getElementById('pending-tasks').textContent = '2';
                document.getElementById('overdue-tasks').textContent = '0';
            }
        }
        
        // Initialize with regular user
        switchUser('regular');
    </script>
</body>

</html>
<?php require_once '../includes/footer.php'; ?>

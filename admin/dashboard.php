<?php
session_start();
include("../connection.php");
include("../auth_check.php");

// Check if user is logged in and is an admin
checkAuth('Admin');
$user = getUserInfo();

// Get dashboard statistics
try {
    // Total users
    $total_users_query = "SELECT COUNT(*) as total FROM USER";
    $total_users_result = $conn->query($total_users_query);
    $total_users = $total_users_result->fetch_assoc()['total'];

    // Total products
    $total_products_query = "SELECT COUNT(*) as total FROM PRODUCT";
    $total_products_result = $conn->query($total_products_query);
    $total_products = $total_products_result->fetch_assoc()['total'];

    // Total services
    $total_services_query = "SELECT COUNT(*) as total FROM SERVICE";
    $total_services_result = $conn->query($total_services_query);
    $total_services = $total_services_result->fetch_assoc()['total'];

    // Total revenue (from orders)
    $total_revenue_query = "SELECT COALESCE(SUM(TotalAmount), 0) as total FROM `ORDER` WHERE Status = 'Completed'";
    $total_revenue_result = $conn->query($total_revenue_query);
    $total_revenue = $total_revenue_result->fetch_assoc()['total'];

    // Recent activity data for charts
    $sales_data_query = "SELECT 
        MONTH(OrderDate) as month, 
        MONTHNAME(OrderDate) as month_name,
        SUM(TotalAmount) as total_sales 
        FROM `ORDER` 
        WHERE YEAR(OrderDate) = YEAR(CURDATE()) AND Status = 'Completed'
        GROUP BY MONTH(OrderDate), MONTHNAME(OrderDate)
        ORDER BY MONTH(OrderDate)";
    $sales_data_result = $conn->query($sales_data_query);
    $sales_data = [];
    $sales_labels = [];
    while($row = $sales_data_result->fetch_assoc()) {
        $sales_labels[] = $row['month_name'];
        $sales_data[] = $row['total_sales'];
    }

    // Service requests data
    $service_data_query = "SELECT 
        Status, 
        COUNT(*) as count 
        FROM SERVICE_BOOKING 
        GROUP BY Status";
    $service_data_result = $conn->query($service_data_query);
    $service_labels = [];
    $service_counts = [];
    $service_colors = [];
    
    $status_colors = [
        'Pending' => '#f39c12',
        'Assigned' => '#3498db',
        'InProgress' => '#9b59b6',
        'Completed' => '#2ecc71',
        'Cancelled' => '#e74c3c'
    ];
    
    while($row = $service_data_result->fetch_assoc()) {
        $service_labels[] = $row['Status'];
        $service_counts[] = $row['count'];
        $service_colors[] = $status_colors[$row['Status']] ?? '#95a5a6';
    }

} catch(Exception $e) {
    // Set default values if queries fail
    $total_users = 0;
    $total_products = 0;
    $total_services = 0;
    $total_revenue = 0;
    $sales_data = [0];
    $sales_labels = ['No Data'];
    $service_labels = ['No Data'];
    $service_counts = [0];
    $service_colors = ['#95a5a6'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DC Electricals</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            display: flex;
            height: 100vh;
            background: #f4f6f9;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #1e1e2f, #2d2d44);
            color: #fff;
            padding: 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .logo {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;
        }

        .logo img {
            width: 45px;
            height: 45px;
            object-fit: contain;
            border-radius: 50%;
            background: #fff;
            padding: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .sidebar h2 {
            margin-bottom: 30px;
            text-align: center;
            font-size: 18px;
            color: #e8e8e8;
            border-bottom: 1px solid #3a3a5a;
            padding-bottom: 15px;
        }

        .sidebar a {
            color: #ddd;
            text-decoration: none;
            padding: 12px 15px;
            margin: 3px 0;
            display: flex;
            align-items: center;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            font-size: 16px;
        }

        .sidebar a:hover {
            background: rgba(74, 144, 226, 0.2);
            color: #fff;
            transform: translateX(5px);
        }

        .sidebar a.active {
            background: #4a90e2;
            color: #fff;
        }

        /* Main Content */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: #fff;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e8e8e8;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .navbar h3 {
            color: #333;
            font-size: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-name {
            color: #666;
            font-size: 14px;
        }

        .logout-btn {
            padding: 8px 16px;
            background: #dc3545;
            border: none;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        /* Dashboard Content */
        .content {
            padding: 25px;
            overflow-y: auto;
        }

        .welcome-message {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(74, 144, 226, 0.3);
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .card-icon {
            font-size: 35px;
            margin-right: 20px;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .card:nth-child(1) .card-icon { background: rgba(74, 144, 226, 0.1); color: #4a90e2; }
        .card:nth-child(2) .card-icon { background: rgba(46, 204, 113, 0.1); color: #2ecc71; }
        .card:nth-child(3) .card-icon { background: rgba(243, 156, 18, 0.1); color: #f39c12; }
        .card:nth-child(4) .card-icon { background: rgba(155, 89, 182, 0.1); color: #9b59b6; }

        .card-info h4 {
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-info p {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
        }

        /* Chart Section */
        .charts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .chart-box {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .chart-box h4 {
            margin-bottom: 20px;
            color: #333;
            font-size: 18px;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 10px;
        }

        canvas {
            width: 100% !important;
            height: 300px !important;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .charts {
                grid-template-columns: 1fr;
            }
            .cards {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .sidebar {
                width: 70px;
                padding: 15px 10px;
            }
            .sidebar h2 {
                display: none;
            }
            .sidebar a span {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="../logo.jpg" alt="DC Electricals Logo">
        </div>
        <h2>DC Admin Panel</h2>
        <a href="dashboard.php" class="active">
            <i class="ri-dashboard-line"></i>
            <span>Dashboard</span>
        </a>
        <a href="users.php">
            <i class="ri-user-3-line"></i>
            <span>Users</span>
        </a>
        <a href="products.php">
            <i class="ri-box-3-line"></i>
            <span>Products</span>
        </a>
        <a href="services.php">
            <i class="ri-tools-line"></i>
            <span>Services</span>
        </a>
        <a href="technicians.php">
            <i class="ri-team-line"></i>
            <span>Technicians</span>
        </a>
        <a href="bookings.php">
            <i class="ri-calendar-line"></i>
            <span>Bookings</span>
        </a>
        <a href="orders.php">
            <i class="ri-shopping-cart-line"></i>
            <span>Orders</span>
        </a>
        <a href="inquiries.php">
            <i class="ri-question-answer-line"></i>
            <span>Inquiries</span>
        </a>
        <a href="reports.php">
            <i class="ri-bar-chart-line"></i>
            <span>Reports</span>
        </a>
        <a href="settings.php">
            <i class="ri-settings-3-line"></i>
            <span>Settings</span>
        </a>
    </div>

    <!-- Main -->
    <div class="main">
        <!-- Navbar -->
        <div class="navbar">
            <h3>Admin Dashboard</h3>
            <div class="user-info">
                <span class="user-name">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                <button class="logout-btn" onclick="logout()">
                    <i class="ri-logout-box-line"></i> Logout
                </button>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Welcome Message -->
            <div class="welcome-message">
                <h2>Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h2>
                <p>Here's what's happening with your DC Electricals business today.</p>
            </div>

            <!-- Cards -->
            <div class="cards">
                <div class="card">
                    <div class="card-icon">
                        <i class="ri-user-3-line"></i>
                    </div>
                    <div class="card-info">
                        <h4>Total Users</h4>
                        <p><?php echo number_format($total_users); ?></p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <i class="ri-box-3-line"></i>
                    </div>
                    <div class="card-info">
                        <h4>Products</h4>
                        <p><?php echo number_format($total_products); ?></p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <i class="ri-tools-line"></i>
                    </div>
                    <div class="card-info">
                        <h4>Services</h4>
                        <p><?php echo number_format($total_services); ?></p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <i class="ri-money-dollar-circle-line"></i>
                    </div>
                    <div class="card-info">
                        <h4>Revenue</h4>
                        <p>Rs. <?php echo number_format($total_revenue, 2); ?></p>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts">
                <div class="chart-box">
                    <h4>Monthly Sales Overview</h4>
                    <canvas id="salesChart"></canvas>
                </div>
                <div class="chart-box">
                    <h4>Service Booking Status</h4>
                    <canvas id="serviceChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Sales Chart
        const ctx1 = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($sales_labels); ?>,
                datasets: [{
                    label: 'Sales (Rs.)',
                    data: <?php echo json_encode($sales_data); ?>,
                    borderColor: '#4a90e2',
                    backgroundColor: 'rgba(74, 144, 226, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4a90e2',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rs. ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Service Chart
        const ctx2 = document.getElementById('serviceChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($service_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($service_counts); ?>,
                    backgroundColor: <?php echo json_encode($service_colors); ?>,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../logout.php';
            }
        }

        // Auto refresh data every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutes
    </script>
</body>
</html>
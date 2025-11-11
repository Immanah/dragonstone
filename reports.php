<?php
session_start();

// Proper session authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: adminlogin.php');
    exit();
}

// Check if user has permission for reports
$admin_permissions = $_SESSION['admin_permissions'] ?? [];
if (!in_array('all', $admin_permissions) && !in_array('reports', $admin_permissions)) {
    header('Location: adminindex.php');
    exit();
}

// Database connection
include '../includes/database.php';
$conn = getDatabaseConnection();

// Date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default to start of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Default to end of current month

// Get report data from database
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_co2_saved = $conn->query("SELECT SUM(co2_saved) as total FROM products")->fetch_assoc()['total'];
$total_revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'")->fetch_assoc()['total'] ?? 0;

// New users in date range
$new_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE date_created BETWEEN '$start_date' AND '$end_date 23:59:59'")->fetch_assoc()['count'];

// Sales by category
$sales_by_category = $conn->query("
    SELECT c.name as category_name, COUNT(p.product_id) as product_count, 
           SUM(p.co2_saved) as total_co2, SUM(p.price) as total_value
    FROM categories c 
    LEFT JOIN products p ON c.category_id = p.category_id 
    GROUP BY c.category_id, c.name
");

// User roles breakdown
$users_by_role = $conn->query("
    SELECT role, COUNT(*) as count 
    FROM users 
    GROUP BY role
");

// Recent activity
$recent_users = $conn->query("
    SELECT first_name, last_name, email, date_created 
    FROM users 
    ORDER BY date_created DESC 
    LIMIT 5
");

// Low stock products
$low_stock_products = $conn->query("
    SELECT name, stock_quantity 
    FROM products 
    WHERE stock_quantity < 10 
    ORDER BY stock_quantity ASC 
    LIMIT 5
");

// Monthly sales data for chart
$monthly_sales = $conn->query("
    SELECT 
        DATE_FORMAT(order_date, '%Y-%m') as month,
        COUNT(*) as order_count,
        SUM(total_amount) as revenue
    FROM orders 
    WHERE order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(order_date, '%Y-%m')
    ORDER BY month ASC
");

// Top products by sales
$top_products = $conn->query("
    SELECT p.name, COUNT(oi.order_item_id) as sales_count
    FROM products p
    LEFT JOIN order_items oi ON p.product_id = oi.product_id
    GROUP BY p.product_id, p.name
    ORDER BY sales_count DESC
    LIMIT 10
");

// Prepare data for charts
$sales_months = [];
$sales_data = [];
$revenue_data = [];

while ($row = $monthly_sales->fetch_assoc()) {
    $sales_months[] = date('M Y', strtotime($row['month']));
    $sales_data[] = $row['order_count'];
    $revenue_data[] = $row['revenue'];
}

$category_names = [];
$category_counts = [];
$category_co2 = [];

while ($row = $sales_by_category->fetch_assoc()) {
    $category_names[] = $row['category_name'];
    $category_counts[] = $row['product_count'];
    $category_co2[] = $row['total_co2'];
}

// Reset pointer for later use
$sales_by_category->data_seek(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - DragonStone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #2d5016;
            --secondary-color: #4a7c2a;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-radius: 10px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-nav .nav-link {
            color: #6c757d;
            border-radius: var(--border-radius);
            margin: 0.1rem;
            transition: var(--transition);
        }
        .admin-nav .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        .admin-nav .nav-link:hover:not(.active) {
            background-color: rgba(45, 80, 22, 0.1);
        }
        
        .filter-section {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--box-shadow);
            border: 1px solid #e9ecef;
        }
        
        .card {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid #e9ecef;
            transition: var(--transition);
            height: 100%;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card {
            text-align: center;
            padding: 1.5rem;
        }
        
        .stat-card .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-card .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .stat-card .stat-change {
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--dark-color);
            background-color: #f8f9fa;
        }
        
        .table-responsive {
            border-radius: var(--border-radius);
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }
        
        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }
        
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .btn-success {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-success:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ced4da;
            transition: var(--transition);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(45, 80, 22, 0.25);
        }
        
        .navbar-brand {
            font-weight: 700;
        }
        
        .list-group-item {
            border: none;
            padding: 0.75rem 0;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        .export-options {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: var(--box-shadow);
            border: 1px solid #e9ecef;
        }
        
        @media (max-width: 768px) {
            .stat-card {
                padding: 1rem;
            }
            
            .stat-card .stat-value {
                font-size: 1.5rem;
            }
            
            .chart-container {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand">
                <i class="fas fa-dragon me-2"></i>DragonStone Admin
                <small class="d-block opacity-75">Reports & Analytics</small>
            </span>
            <div class="navbar-nav">
                <a href="../index.php" class="btn btn-outline-light btn-sm me-2"><i class="fas fa-external-link-alt me-1"></i>View Main Site</a>
                <a href="adminindex.php" class="btn btn-outline-warning btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <!-- Admin Navigation -->
    <div class="bg-light border-bottom">
        <div class="container">
            <div class="nav admin-nav nav-pills py-2">
                <a class="nav-link" href="adminindex.php"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
                <a class="nav-link" href="usermanagement.php"><i class="fas fa-users me-1"></i>User Management</a>
                <a class="nav-link" href="productmanagement.php"><i class="fas fa-box me-1"></i>Product Management</a>
                <a class="nav-link" href="categorymanagement.php"><i class="fas fa-tags me-1"></i>Category Management</a>
                <a class="nav-link active" href="reports.php"><i class="fas fa-chart-bar me-1"></i>Reports</a>
            </div>
        </div>
    </div>

    <!-- Reports Content -->
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h1>
            <div class="btn-group">
                <button class="btn btn-outline-primary" id="printReport"><i class="fas fa-print me-1"></i>Print</button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exportModal"><i class="fas fa-download me-1"></i>Export</button>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="filter-section">
            <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Reports</h5>
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-sync me-1"></i>Apply Filters</button>
                </div>
                <div class="col-md-3">
                    <a href="reports.php" class="btn btn-outline-secondary w-100"><i class="fas fa-times me-1"></i>Clear Filters</a>
                </div>
            </form>
        </div>

        <!-- Summary Stats -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-change text-success">
                        <i class="fas fa-arrow-up me-1"></i><?php echo $new_users; ?> new this period
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_products; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($total_co2_saved); ?>kg</div>
                    <div class="stat-label">CO2 Saved</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Sales Chart -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Sales & Revenue Trend</h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary active" data-chart-type="line">Line</button>
                            <button class="btn btn-outline-secondary" data-chart-type="bar">Bar</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Roles Breakdown -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-tag me-2"></i>User Roles</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="rolesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-2">
            <!-- Products by Category -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Products by Category</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Products</th>
                                        <th>CO2 Saved</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($category = $sales_by_category->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                            <td><span class="badge bg-primary"><?php echo $category['product_count']; ?></span></td>
                                            <td><span class="badge bg-success"><?php echo $category['total_co2'] ?? 0; ?>kg</span></td>
                                            <td>$<?php echo number_format($category['total_value'] ?? 0, 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Products</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($top_products->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php $rank = 1; ?>
                                <?php while($product = $top_products->fetch_assoc()): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-primary me-2"><?php echo $rank++; ?></span>
                                            <span><?php echo htmlspecialchars($product['name']); ?></span>
                                        </div>
                                        <span class="badge bg-success"><?php echo $product['sales_count']; ?> sales</span>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No sales data available.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-2">
            <!-- Recent User Registrations -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Recent User Registrations</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_users->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while($user = $recent_users->fetch_assoc()): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                        </div>
                                        <small class="text-muted"><?php echo date('M j, Y', strtotime($user['date_created'])); ?></small>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No recent user registrations.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Low Stock Products</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($low_stock_products->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while($product = $low_stock_products->fetch_assoc()): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?php echo htmlspecialchars($product['name']); ?></span>
                                        <span class="badge bg-<?php echo $product['stock_quantity'] == 0 ? 'danger' : 'warning'; ?>">
                                            <?php echo $product['stock_quantity']; ?> left
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-success"><i class="fas fa-check-circle me-1"></i>All products have sufficient stock.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Options -->
        <div class="export-options">
            <h5 class="mb-3"><i class="fas fa-download me-2"></i>Export Reports</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <button class="btn btn-outline-primary w-100" data-export-type="pdf">
                        <i class="fas fa-file-pdf me-2"></i>Export as PDF
                    </button>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-outline-success w-100" data-export-type="csv">
                        <i class="fas fa-file-csv me-2"></i>Export as CSV
                    </button>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-outline-info w-100" data-export-type="excel">
                        <i class="fas fa-file-excel me-2"></i>Export as Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-download me-2"></i>Export Reports</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Export Format</label>
                        <select class="form-select" id="exportFormat">
                            <option value="pdf">PDF Document</option>
                            <option value="csv">CSV Spreadsheet</option>
                            <option value="excel">Excel Workbook</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <select class="form-select" id="exportDateRange">
                            <option value="current">Current Period</option>
                            <option value="last_month">Last Month</option>
                            <option value="last_quarter">Last Quarter</option>
                            <option value="last_year">Last Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="includeCharts" checked>
                        <label class="form-check-label" for="includeCharts">Include charts and graphs</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmExport">Export Report</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sales Chart
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($sales_months); ?>,
                    datasets: [
                        {
                            label: 'Orders',
                            data: <?php echo json_encode($sales_data); ?>,
                            borderColor: '#2d5016',
                            backgroundColor: 'rgba(45, 80, 22, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Revenue ($)',
                            data: <?php echo json_encode($revenue_data); ?>,
                            borderColor: '#4a7c2a',
                            backgroundColor: 'rgba(74, 124, 42, 0.1)',
                            tension: 0.3,
                            fill: true,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Orders'
                            }
                        },
                        y1: {
                            position: 'right',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Revenue ($)'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
            
            // Roles Chart
            const rolesCtx = document.getElementById('rolesChart').getContext('2d');
            const rolesChart = new Chart(rolesCtx, {
                type: 'doughnut',
                data: {
                    labels: [
                        <?php 
                        $users_by_role->data_seek(0);
                        while($role_data = $users_by_role->fetch_assoc()): 
                            echo "'" . $role_data['role'] . "',";
                        endwhile; 
                        ?>
                    ],
                    datasets: [{
                        data: [
                            <?php 
                            $users_by_role->data_seek(0);
                            while($role_data = $users_by_role->fetch_assoc()): 
                                echo $role_data['count'] . ",";
                            endwhile; 
                            ?>
                        ],
                        backgroundColor: [
                            '#2d5016',
                            '#4a7c2a',
                            '#6ba743',
                            '#8dc264',
                            '#a9d681'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            // Chart type toggle
            document.querySelectorAll('[data-chart-type]').forEach(button => {
                button.addEventListener('click', function() {
                    const type = this.getAttribute('data-chart-type');
                    salesChart.config.type = type;
                    salesChart.update();
                    
                    // Update active state
                    document.querySelectorAll('[data-chart-type]').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    this.classList.add('active');
                });
            });
            
            // Print functionality
            document.getElementById('printReport').addEventListener('click', function() {
                window.print();
            });
            
            // Export functionality
            document.getElementById('confirmExport').addEventListener('click', function() {
                const format = document.getElementById('exportFormat').value;
                const dateRange = document.getElementById('exportDateRange').value;
                const includeCharts = document.getElementById('includeCharts').checked;
                
                alert(`Exporting report as ${format.toUpperCase()} for ${dateRange} ${includeCharts ? 'with' : 'without'} charts.`);
                
                // In a real implementation, you would make an AJAX request to generate the export
                // For this example, we'll just close the modal
                const exportModal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
                exportModal.hide();
            });
            
            // Simple export buttons
            document.querySelectorAll('[data-export-type]').forEach(button => {
                button.addEventListener('click', function() {
                    const type = this.getAttribute('data-export-type');
                    alert(`Exporting report as ${type.toUpperCase()}. In a real implementation, this would generate and download the file.`);
                });
            });
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>
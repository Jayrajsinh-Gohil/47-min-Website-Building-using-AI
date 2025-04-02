<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is admin, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== 1){
    header("location: ../login.php");
    exit;
}

// Include config file
require_once "../includes/config.php";

// Get total users count
$total_users = 0;
$sql = "SELECT COUNT(*) as total FROM users";
$result = mysqli_query($conn, $sql);
if($result){
    $row = mysqli_fetch_assoc($result);
    $total_users = $row['total'];
}

// Get total products count
$total_products = 0;
$sql = "SELECT COUNT(*) as total FROM products";
$result = mysqli_query($conn, $sql);
if($result){
    $row = mysqli_fetch_assoc($result);
    $total_products = $row['total'];
}

// Get total orders count
$total_orders = 0;
$sql = "SELECT COUNT(*) as total FROM orders";
$result = mysqli_query($conn, $sql);
if($result){
    $row = mysqli_fetch_assoc($result);
    $total_orders = $row['total'];
}

// Get total revenue
$total_revenue = 0;
$sql = "SELECT SUM(total_amount) as total FROM orders WHERE order_status != 'cancelled'";
$result = mysqli_query($conn, $sql);
if($result){
    $row = mysqli_fetch_assoc($result);
    $total_revenue = $row['total'] ? $row['total'] : 0;
}

// Get orders by status
$orders_by_status = [];
$sql = "SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status";
$result = mysqli_query($conn, $sql);
if($result){
    while($row = mysqli_fetch_assoc($result)){
        $orders_by_status[$row['order_status']] = $row['count'];
    }
}

// Get recent orders
$recent_orders = [];
$sql = "SELECT o.*, u.username 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5";
$result = mysqli_query($conn, $sql);
if($result){
    while($row = mysqli_fetch_assoc($result)){
        $recent_orders[] = $row;
    }
}

// Get top selling products
$top_products = [];
$sql = "SELECT p.id, p.name, p.image, SUM(oi.quantity) as total_sold 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        JOIN orders o ON oi.order_id = o.id 
        WHERE o.order_status != 'cancelled' 
        GROUP BY p.id 
        ORDER BY total_sold DESC 
        LIMIT 5";
$result = mysqli_query($conn, $sql);
if($result){
    while($row = mysqli_fetch_assoc($result)){
        $top_products[] = $row;
    }
}

// Get low stock products
$low_stock_products = [];
$sql = "SELECT * FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 5";
$result = mysqli_query($conn, $sql);
if($result){
    while($row = mysqli_fetch_assoc($result)){
        $low_stock_products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MobileStore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h3>Admin Panel</h3>
            <ul>
                <li><a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-mobile-alt"></i> Products</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Back to Site</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h2>Dashboard</h2>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</p>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <p><?php echo $total_users; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Products</h3>
                        <p><?php echo $total_products; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Orders</h3>
                        <p><?php echo $total_orders; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Revenue</h3>
                        <p>$<?php echo number_format($total_revenue, 2); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-sections">
                <div class="dashboard-section">
                    <h3>Orders by Status</h3>
                    <div class="status-chart">
                        <?php
                        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
                        foreach($statuses as $status):
                            $count = isset($orders_by_status[$status]) ? $orders_by_status[$status] : 0;
                            $percentage = $total_orders > 0 ? ($count / $total_orders) * 100 : 0;
                        ?>
                        <div class="status-bar">
                            <div class="status-label">
                                <span class="status-<?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                                <span class="status-count"><?php echo $count; ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress" style="width: <?php echo $percentage; ?>%;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="dashboard-section">
                    <h3>Recent Orders</h3>
                    <?php if(empty($recent_orders)): ?>
                        <p>No orders found.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-<?php echo $order['order_status']; ?>"><?php echo ucfirst($order['order_status']); ?></span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="view-all">
                            <a href="orders.php" class="btn btn-secondary">View All Orders</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="dashboard-sections">
                <div class="dashboard-section">
                    <h3>Top Selling Products</h3>
                    <?php if(empty($top_products)): ?>
                        <p>No products sold yet.</p>
                    <?php else: ?>
                        <div class="product-cards">
                            <?php foreach($top_products as $product): ?>
                                <div class="product-card">
                                    <img src="../uploads/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                    <div class="product-info">
                                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                        <p>Total Sold: <?php echo $product['total_sold']; ?></p>
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="dashboard-section">
                    <h3>Low Stock Products</h3>
                    <?php if(empty($low_stock_products)): ?>
                        <p>No low stock products.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Stock</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($low_stock_products as $product): ?>
                                    <tr>
                                        <td class="product-cell">
                                            <div class="product-info">
                                                <img src="../uploads/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-thumbnail">
                                                <span class="product-name"><?php echo htmlspecialchars($product['name']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="stock-level <?php echo $product['stock'] == 0 ? 'out-of-stock' : 'low-stock'; ?>">
                                                <?php echo $product['stock'] == 0 ? 'Out of Stock' : $product['stock']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">Update Stock</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="view-all">
                            <a href="products.php" class="btn btn-secondary">View All Products</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html> 
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

// Process update order status
if(isset($_POST["update_status"]) && !empty($_POST["order_id"]) && !empty($_POST["status"])){
    $order_id = $_POST["order_id"];
    $status = $_POST["status"];
    
    // Validate status
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if(in_array($status, $valid_statuses)){
        // Update order status
        $sql = "UPDATE orders SET order_status = ? WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
            
            if(mysqli_stmt_execute($stmt)){
                // Status updated successfully
                $_SESSION["success"] = "Order status updated successfully.";
                header("location: orders.php");
                exit;
            } else {
                $_SESSION["error"] = "Oops! Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    } else {
        $_SESSION["error"] = "Invalid order status.";
    }
}

// Get orders with search, filter, and pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Prepare SQL query
$sql = "SELECT o.*, u.username 
        FROM orders o 
        JOIN users u ON o.user_id = u.id";
$count_sql = "SELECT COUNT(*) as total 
              FROM orders o 
              JOIN users u ON o.user_id = u.id";

// Add filters
$where_clauses = [];

if (!empty($search)) {
    $search_term = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $where_clauses[] = "(u.username LIKE '$search_term' OR o.id LIKE '$search_term')";
}

if (!empty($status_filter)) {
    $status = mysqli_real_escape_string($conn, $status_filter);
    $where_clauses[] = "o.order_status = '$status'";
}

if (!empty($where_clauses)) {
    $where_clause = " WHERE " . implode(" AND ", $where_clauses);
    $sql .= $where_clause;
    $count_sql .= $where_clause;
}

// Add sorting and pagination
$sql .= " ORDER BY o.created_at DESC LIMIT $offset, $limit";

// Get total orders count
$total_orders = 0;
$result = mysqli_query($conn, $count_sql);
if($result){
    $row = mysqli_fetch_assoc($result);
    $total_orders = $row['total'];
}

// Calculate total pages
$total_pages = ceil($total_orders / $limit);

// Get orders
$orders = [];
$result = mysqli_query($conn, $sql);
if($result){
    while($row = mysqli_fetch_assoc($result)){
        $orders[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - MobileStore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h3>Admin Panel</h3>
            <ul>
                <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-mobile-alt"></i> Products</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Back to Site</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h2>Manage Orders</h2>
            </div>
            
            <?php if(isset($_SESSION["success"])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION["success"]; ?>
                    <?php unset($_SESSION["success"]); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION["error"])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION["error"]; ?>
                    <?php unset($_SESSION["error"]); ?>
                </div>
            <?php endif; ?>
            
            <div class="admin-filters">
                <form action="orders.php" method="get" class="filter-form">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                    
                    <div class="filter-box">
                        <label for="status">Filter by Status:</label>
                        <select name="status" id="status" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <div class="admin-table-container">
                <?php if(empty($orders)): ?>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-<?php echo $order['order_status']; ?>"><?php echo ucfirst($order['order_status']); ?></span>
                                    </td>
                                    <td><?php echo date('M j, Y, g:i a', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">View</a>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="showUpdateStatusModal(<?php echo $order['id']; ?>, '<?php echo $order['order_status']; ?>')">Update Status</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if($page > 1): ?>
                                <a href="orders.php?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="btn btn-secondary">&laquo; Previous</a>
                            <?php endif; ?>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="orders.php?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="btn <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            
                            <?php if($page < $total_pages): ?>
                                <a href="orders.php?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="btn btn-secondary">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div id="updateStatusModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Update Order Status</h2>
            <form action="orders.php" method="post">
                <input type="hidden" name="order_id" id="order_id">
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status_select" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script>
        // Modal functions
        const modal = document.getElementById('updateStatusModal');
        
        function showUpdateStatusModal(orderId, currentStatus) {
            document.getElementById('order_id').value = orderId;
            document.getElementById('status_select').value = currentStatus;
            modal.style.display = 'block';
        }
        
        function closeModal() {
            modal.style.display = 'none';
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html> 
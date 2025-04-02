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

// Check if order ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    $_SESSION["error"] = "Order ID is required.";
    header("location: orders.php");
    exit;
}

$order_id = $_GET["id"];

// Process update order status
if(isset($_POST["update_status"]) && !empty($_POST["status"])){
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
                header("location: order_details.php?id=" . $order_id);
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

// Get order details
$order = null;
$sql = "SELECT o.*, u.username, u.email, u.full_name, u.phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $order = mysqli_fetch_assoc($result);
        } else {
            // Order not found
            $_SESSION["error"] = "Order not found.";
            header("location: orders.php");
            exit;
        }
    } else {
        $_SESSION["error"] = "Oops! Something went wrong. Please try again later.";
        header("location: orders.php");
        exit;
    }
    
    mysqli_stmt_close($stmt);
} else {
    $_SESSION["error"] = "Oops! Something went wrong. Please try again later.";
    header("location: orders.php");
    exit;
}

// Get order items
$order_items = [];
$sql = "SELECT oi.*, p.name, p.image 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)){
            $order_items[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - MobileStore</title>
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
                <h2>Order Details</h2>
                <a href="orders.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Orders</a>
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
            
            <div class="order-details-container">
                <div class="order-info-section">
                    <div class="order-header">
                        <h3>Order #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></h3>
                        <div class="order-status">
                            <span class="status-<?php echo $order['order_status']; ?>"><?php echo ucfirst($order['order_status']); ?></span>
                            <button type="button" class="btn btn-primary btn-sm" onclick="showUpdateStatusModal('<?php echo $order['order_status']; ?>')">Update Status</button>
                        </div>
                    </div>
                    
                    <div class="order-meta">
                        <div class="meta-item">
                            <span class="meta-label">Order Date:</span>
                            <span class="meta-value"><?php echo date('M j, Y, g:i a', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Payment Method:</span>
                            <span class="meta-value"><?php echo ucfirst($order['payment_method']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Total Amount:</span>
                            <span class="meta-value">$<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="order-sections-container">
                    <div class="order-section">
                        <h4>Customer Information</h4>
                        <div class="customer-info">
                            <div class="info-item">
                                <span class="info-label">Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['full_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['phone']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Username:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['username']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-section">
                        <h4>Shipping Address</h4>
                        <div class="address-info">
                            <p><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="order-section">
                    <h4>Order Items</h4>
                    <div class="order-items">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($order_items as $item): ?>
                                    <tr>
                                        <td class="product-cell">
                                            <div class="product-info">
                                                <img src="../uploads/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-thumbnail">
                                                <span class="product-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                            </div>
                                        </td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-right"><strong>Subtotal:</strong></td>
                                    <td>$<?php echo number_format($order['subtotal'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-right"><strong>Shipping:</strong></td>
                                    <td>$<?php echo number_format($order['shipping_cost'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-right"><strong>Total:</strong></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <?php if(!empty($order['notes'])): ?>
                <div class="order-section">
                    <h4>Order Notes</h4>
                    <div class="order-notes">
                        <p><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div id="updateStatusModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Update Order Status</h2>
            <form action="order_details.php?id=<?php echo $order_id; ?>" method="post">
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
        
        function showUpdateStatusModal(currentStatus) {
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
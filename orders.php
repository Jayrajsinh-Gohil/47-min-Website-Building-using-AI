<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include config file
require_once "includes/config.php";

// Set page title
$page_title = "My Orders";

// Include header
require_once "includes/header.php";

// Get user orders
$user_id = $_SESSION["id"];
$orders = [];

$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)){
            $orders[] = $row;
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "Oops! Something went wrong. Please try again later.";
}
?>

<div class="orders-container">
    <h1 class="page-title">My Orders</h1>
    
    <?php if(empty($orders)): ?>
        <div class="empty-orders">
            <i class="fas fa-shopping-bag fa-4x"></i>
            <p>You haven't placed any orders yet.</p>
            <a href="products.php" class="btn btn-primary">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-number">
                            <span>Order #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="order-date">
                            <span><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="order-status">
                            <span class="status-<?php echo $order['order_status']; ?>"><?php echo ucfirst($order['order_status']); ?></span>
                        </div>
                    </div>
                    
                    <div class="order-body">
                        <?php
                        // Get order items
                        $order_items = [];
                        $sql = "SELECT oi.*, p.name, p.image 
                                FROM order_items oi 
                                JOIN products p ON oi.product_id = p.id 
                                WHERE oi.order_id = ?";
                        
                        if($stmt = mysqli_prepare($conn, $sql)){
                            mysqli_stmt_bind_param($stmt, "i", $order['id']);
                            
                            if(mysqli_stmt_execute($stmt)){
                                $result = mysqli_stmt_get_result($stmt);
                                
                                while($row = mysqli_fetch_assoc($result)){
                                    $order_items[] = $row;
                                }
                            }
                            
                            mysqli_stmt_close($stmt);
                        }
                        ?>
                        
                        <div class="order-items">
                            <?php foreach($order_items as $item): ?>
                                <div class="order-item">
                                    <div class="order-item-image">
                                        <img src="assets/images/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    </div>
                                    <div class="order-item-details">
                                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <div class="order-item-meta">
                                            <span>Quantity: <?php echo $item['quantity']; ?></span>
                                            <span>Price: $<?php echo number_format($item['price'], 2); ?></span>
                                        </div>
                                    </div>
                                    <div class="order-item-subtotal">
                                        $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="order-footer">
                        <div class="order-total">
                            <span>Total:</span>
                            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                        <div class="order-actions">
                            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
require_once "includes/footer.php";
?> 
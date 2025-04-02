<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Check if order ID is set in session
if(!isset($_SESSION["order_id"])){
    header("location: index.php");
    exit;
}

// Include config file
require_once "includes/config.php";

// Set page title
$page_title = "Order Confirmation";

// Include header
require_once "includes/header.php";

// Get order details
$order_id = $_SESSION["order_id"];
$user_id = $_SESSION["id"];
$order = [];
$order_items = [];

// Get order information
$sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $order = mysqli_fetch_assoc($result);
        } else {
            // Order not found or doesn't belong to the user
            header("location: index.php");
            exit;
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "Oops! Something went wrong. Please try again later.";
}

// Get order items
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
    } else {
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "Oops! Something went wrong. Please try again later.";
}

// Clear order ID from session
unset($_SESSION["order_id"]);
?>

<div class="confirmation-container">
    <div class="confirmation-header">
        <i class="fas fa-check-circle fa-4x"></i>
        <h1>Thank You for Your Order!</h1>
        <p>Your order has been placed successfully. We'll process it as soon as possible.</p>
    </div>
    
    <div class="order-details">
        <div class="order-info">
            <h2>Order Information</h2>
            <div class="info-item">
                <span>Order Number:</span>
                <span>#<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="info-item">
                <span>Order Date:</span>
                <span><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></span>
            </div>
            <div class="info-item">
                <span>Order Status:</span>
                <span class="status-<?php echo $order['order_status']; ?>"><?php echo ucfirst($order['order_status']); ?></span>
            </div>
            <div class="info-item">
                <span>Payment Method:</span>
                <span>Cash on Delivery</span>
            </div>
            <div class="info-item">
                <span>Shipping Address:</span>
                <span><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></span>
            </div>
        </div>
        
        <div class="order-summary">
            <h2>Order Summary</h2>
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
                            <div class="order-item-subtotal">
                                $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-totals">
                <div class="summary-item">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="summary-item">
                    <span>Shipping</span>
                    <span>Free</span>
                </div>
                <div class="summary-item summary-total">
                    <span>Total</span>
                    <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="confirmation-actions">
        <a href="orders.php" class="btn btn-primary">View My Orders</a>
        <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
    </div>
</div>

<?php
// Include footer
require_once "includes/footer.php";
?> 
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

// Check if order ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: orders.php");
    exit;
}

$order_id = $_GET["id"];
$user_id = $_SESSION["id"];

// Validate order ID
if(!is_numeric($order_id)){
    header("location: orders.php");
    exit;
}

// Get order details
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
            header("location: orders.php");
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

// Set page title
$page_title = "Order #" . str_pad($order['id'], 8, '0', STR_PAD_LEFT);

// Include header
require_once "includes/header.php";
?>

<div class="order-details-container">
    <div class="order-details-header">
        <h1 class="page-title">Order Details</h1>
        <a href="orders.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
    </div>
    
    <div class="order-details-content">
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
        
        <div class="order-items-container">
            <h2>Order Items</h2>
            <table class="order-items-table">
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
                            <td>
                                <div class="order-product">
                                    <img src="assets/images/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <div>
                                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <a href="product.php?id=<?php echo $item['product_id']; ?>">View Product</a>
                                    </div>
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
                        <td colspan="3" class="text-right">Subtotal</td>
                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-right">Shipping</td>
                        <td>Free</td>
                    </tr>
                    <tr class="order-total">
                        <td colspan="3" class="text-right">Total</td>
                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
    <?php if($order['order_status'] == 'pending'): ?>
        <div class="order-actions">
            <form action="cancel_order.php" method="post" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                <button type="submit" class="btn btn-danger">Cancel Order</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
require_once "includes/footer.php";
?> 
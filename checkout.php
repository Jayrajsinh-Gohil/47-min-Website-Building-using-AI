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
$page_title = "Checkout";

// Include header
require_once "includes/header.php";

// Get user information
$user_id = $_SESSION["id"];
$user_info = [];

$sql = "SELECT * FROM users WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $user_info = mysqli_fetch_assoc($result);
        } else {
            // User not found
            header("location: logout.php");
            exit;
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "Oops! Something went wrong. Please try again later.";
}

// Get cart items
$cart_items = [];
$cart_total = 0;

$sql = "SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.image, p.stock 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)){
            $cart_items[] = $row;
            $cart_total += $row["price"] * $row["quantity"];
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "Oops! Something went wrong. Please try again later.";
}

// Check if cart is empty
if(empty($cart_items)){
    header("location: cart.php");
    exit;
}

// Process checkout form
$address = $phone = "";
$address_err = $phone_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate address
    if(empty(trim($_POST["address"]))){
        $address_err = "Please enter your shipping address.";
    } else {
        $address = trim($_POST["address"]);
    }
    
    // Validate phone
    if(empty(trim($_POST["phone"]))){
        $phone_err = "Please enter your phone number.";
    } else {
        $phone = trim($_POST["phone"]);
    }
    
    // Check input errors before processing the order
    if(empty($address_err) && empty($phone_err)){
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Update user address and phone if provided
            $sql = "UPDATE users SET address = ?, phone = ? WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "ssi", $address, $phone, $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            
            // Create order
            $sql = "INSERT INTO orders (user_id, total_amount, shipping_address, order_status) VALUES (?, ?, ?, 'pending')";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "ids", $user_id, $cart_total, $address);
                mysqli_stmt_execute($stmt);
                $order_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
                
                // Add order items
                foreach($cart_items as $item){
                    $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                    if($stmt = mysqli_prepare($conn, $sql)){
                        mysqli_stmt_bind_param($stmt, "iiid", $order_id, $item["product_id"], $item["quantity"], $item["price"]);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                        
                        // Update product stock
                        $sql = "UPDATE products SET stock = stock - ? WHERE id = ?";
                        if($stmt = mysqli_prepare($conn, $sql)){
                            mysqli_stmt_bind_param($stmt, "ii", $item["quantity"], $item["product_id"]);
                            mysqli_stmt_execute($stmt);
                            mysqli_stmt_close($stmt);
                        }
                    }
                }
                
                // Clear cart
                $sql = "DELETE FROM cart WHERE user_id = ?";
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
                
                // Commit transaction
                mysqli_commit($conn);
                
                // Redirect to order confirmation page
                $_SESSION["order_id"] = $order_id;
                header("location: order_confirmation.php");
                exit;
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
}
?>

<div class="checkout-container">
    <h1 class="page-title">Checkout</h1>
    
    <div class="checkout-content">
        <div class="checkout-form">
            <h2>Shipping Information</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="validate">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_info['full_name']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user_info['email']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control <?php echo (!empty($phone_err)) ? 'is-invalid' : ''; ?>" value="<?php echo !empty($user_info['phone']) ? htmlspecialchars($user_info['phone']) : $phone; ?>" required>
                    <?php if(!empty($phone_err)): ?>
                        <div class="error-message"><?php echo $phone_err; ?></div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Shipping Address</label>
                    <textarea name="address" class="form-control <?php echo (!empty($address_err)) ? 'is-invalid' : ''; ?>" rows="3" required><?php echo !empty($user_info['address']) ? htmlspecialchars($user_info['address']) : $address; ?></textarea>
                    <?php if(!empty($address_err)): ?>
                        <div class="error-message"><?php echo $address_err; ?></div>
                    <?php endif; ?>
                </div>
                
                <h2>Payment Method</h2>
                <div class="payment-methods">
                    <div class="payment-method">
                        <input type="radio" name="payment_method" id="payment-cod" value="cod" checked>
                        <label for="payment-cod">Cash on Delivery</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Place Order</button>
                    <a href="cart.php" class="btn btn-secondary btn-block">Back to Cart</a>
                </div>
            </form>
        </div>
        
        <div class="order-summary">
            <h2>Order Summary</h2>
            <div class="order-items">
                <?php foreach($cart_items as $item): ?>
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
                    <span>$<?php echo number_format($cart_total, 2); ?></span>
                </div>
                <div class="summary-item">
                    <span>Shipping</span>
                    <span>Free</span>
                </div>
                <div class="summary-item summary-total">
                    <span>Total</span>
                    <span>$<?php echo number_format($cart_total, 2); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once "includes/footer.php";
?> 
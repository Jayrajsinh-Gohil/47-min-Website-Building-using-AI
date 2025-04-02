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
$page_title = "Shopping Cart";

// Include header
require_once "includes/header.php";

// Get cart items
$user_id = $_SESSION["id"];
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
?>

<div class="cart-container">
    <h1 class="page-title">Shopping Cart</h1>
    
    <?php if(isset($_SESSION["cart_success"])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION["cart_success"]; ?>
            <?php unset($_SESSION["cart_success"]); ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION["cart_error"])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION["cart_error"]; ?>
            <?php unset($_SESSION["cart_error"]); ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION["cart_message"])): ?>
        <div class="alert alert-info">
            <?php echo $_SESSION["cart_message"]; ?>
            <?php unset($_SESSION["cart_message"]); ?>
        </div>
    <?php endif; ?>
    
    <?php if(empty($cart_items)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart fa-4x"></i>
            <p>Your cart is empty.</p>
            <a href="products.php" class="btn btn-primary">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="cart-content">
            <div class="cart-items">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="cart-product">
                                        <img src="assets/images/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <div>
                                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                            <a href="product.php?id=<?php echo $item['product_id']; ?>">View Product</a>
                                        </div>
                                    </div>
                                </td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <div class="cart-quantity">
                                        <button class="quantity-btn minus"><i class="fas fa-minus"></i></button>
                                        <input type="number" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" class="quantity-input" data-product-id="<?php echo $item['product_id']; ?>">
                                        <button class="quantity-btn plus"><i class="fas fa-plus"></i></button>
                                    </div>
                                </td>
                                <td class="subtotal-<?php echo $item['product_id']; ?>">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td>
                                    <a href="remove_from_cart.php?id=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm btn-delete">
                                        <i class="fas fa-trash"></i> Remove
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="cart-summary">
                <h3>Order Summary</h3>
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
                    <span class="cart-total">$<?php echo number_format($cart_total, 2); ?></span>
                </div>
                <a href="checkout.php" class="btn btn-primary btn-block">Proceed to Checkout</a>
                <a href="products.php" class="btn btn-secondary btn-block">Continue Shopping</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
require_once "includes/footer.php";
?> 
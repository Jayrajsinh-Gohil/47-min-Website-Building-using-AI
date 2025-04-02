<?php
// Include header
require_once "includes/header.php";

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: products.php");
    exit;
}

$product_id = $_GET['id'];

// Get product details
$sql = "SELECT * FROM products WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $product = mysqli_fetch_assoc($result);
        } else {
            // Product not found
            header("location: products.php");
            exit;
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "Oops! Something went wrong. Please try again later.";
}

// Set page title
$page_title = $product['name'];
?>

<div class="product-details">
    <div class="product-images">
        <div class="main-image">
            <img src="assets/images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="main-product-image">
        </div>
        <!-- If you have multiple product images, you can add thumbnails here -->
    </div>
    
    <div class="product-info">
        <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
        
        <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
        
        <div class="product-description">
            <p><?php echo htmlspecialchars($product['description']); ?></p>
        </div>
        
        <div class="product-meta">
            <div class="product-category">
                <span>Category:</span> <?php echo htmlspecialchars($product['category']); ?>
            </div>
            
            <div class="product-stock">
                <span>Availability:</span> 
                <?php if ($product['stock'] > 0): ?>
                    <span class="in-stock">In Stock (<?php echo $product['stock']; ?> available)</span>
                <?php else: ?>
                    <span class="out-of-stock">Out of Stock</span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (isLoggedIn() && $product['stock'] > 0): ?>
            <form action="add_to_cart.php" method="post" class="add-to-cart-form">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                
                <div class="quantity-selector">
                    <label for="quantity">Quantity:</label>
                    <div class="quantity-input-group">
                        <button type="button" class="quantity-btn minus"><i class="fas fa-minus"></i></button>
                        <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="quantity-input">
                        <button type="button" class="quantity-btn plus"><i class="fas fa-plus"></i></button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-add-to-cart">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>
            </form>
        <?php elseif (!isLoggedIn()): ?>
            <a href="login.php" class="btn btn-primary">Login to Buy</a>
        <?php else: ?>
            <button class="btn btn-secondary" disabled>Out of Stock</button>
        <?php endif; ?>
        
        <div class="product-actions">
            <a href="products.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
        </div>
    </div>
</div>

<section class="related-products">
    <div class="container">
        <h2 class="section-title">Related Products</h2>
        <div class="products-grid">
            <?php
            // Get related products (same category, excluding current product)
            $related_sql = "SELECT * FROM products WHERE category = ? AND id != ? ORDER BY id DESC LIMIT 4";
            if ($stmt = mysqli_prepare($conn, $related_sql)) {
                mysqli_stmt_bind_param($stmt, "si", $product['category'], $product['id']);
                
                if (mysqli_stmt_execute($stmt)) {
                    $related_result = mysqli_stmt_get_result($stmt);
                    
                    if (mysqli_num_rows($related_result) > 0) {
                        while ($related_product = mysqli_fetch_assoc($related_result)) {
                            ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="assets/images/<?php echo htmlspecialchars($related_product['image']); ?>" alt="<?php echo htmlspecialchars($related_product['name']); ?>">
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name"><?php echo htmlspecialchars($related_product['name']); ?></h3>
                                    <div class="product-price">$<?php echo number_format($related_product['price'], 2); ?></div>
                                    <p class="product-description"><?php echo htmlspecialchars($related_product['description']); ?></p>
                                    <div class="product-actions">
                                        <a href="product.php?id=<?php echo $related_product['id']; ?>" class="btn btn-secondary">View Details</a>
                                        <?php if (isLoggedIn()): ?>
                                            <form action="add_to_cart.php" method="post">
                                                <input type="hidden" name="product_id" value="<?php echo $related_product['id']; ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="btn btn-primary">Add to Cart</button>
                                            </form>
                                        <?php else: ?>
                                            <a href="login.php" class="btn btn-primary">Login to Buy</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo "<p>No related products found.</p>";
                    }
                }
                
                mysqli_stmt_close($stmt);
            }
            ?>
        </div>
    </div>
</section>

<?php
// Include footer
require_once "includes/footer.php";
?> 
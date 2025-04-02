<?php
// Include header
require_once "includes/header.php";
?>

<section class="hero">
    <div class="container">
        <h2>Experience the Future of Mobile Technology</h2>
        <p>Discover the latest smartphones, tablets, and accessories with exceptional performance and stunning design.</p>
        <a href="products.php" class="btn btn-primary">Shop Now</a>
    </div>
</section>

<section class="featured-products">
    <div class="container">
        <h2 class="section-title">Featured Products</h2>
        <div class="products-grid">
            <?php
            // Get featured products
            $sql = "SELECT * FROM products ORDER BY id DESC LIMIT 6";
            $result = mysqli_query($conn, $sql);
            
            if (mysqli_num_rows($result) > 0) {
                while ($product = mysqli_fetch_assoc($result)) {
                    ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <div class="product-actions">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary">View Details</a>
                                <?php if (isLoggedIn()): ?>
                                    <form action="add_to_cart.php" method="post">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
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
                echo "<p>No products found.</p>";
            }
            ?>
        </div>
    </div>
</section>

<section class="categories">
    <div class="container">
        <h2 class="section-title">Shop by Category</h2>
        <div class="category-grid">
            <div class="category-card">
                <img src="generate_placeholder.php?width=300&height=250&text=Smartphones&bg=0071e3" alt="Smartphones">
                <div class="category-overlay">
                    <h3>Smartphones</h3>
                    <a href="products.php?category=Smartphone" class="btn btn-primary">Shop Now</a>
                </div>
            </div>
            <div class="category-card">
                <img src="generate_placeholder.php?width=300&height=250&text=Tablets&bg=4cd964" alt="Tablets">
                <div class="category-overlay">
                    <h3>Tablets</h3>
                    <a href="products.php?category=Tablet" class="btn btn-primary">Shop Now</a>
                </div>
            </div>
            <div class="category-card">
                <img src="generate_placeholder.php?width=300&height=250&text=Laptops&bg=ff9500" alt="Laptops">
                <div class="category-overlay">
                    <h3>Laptops</h3>
                    <a href="products.php?category=Laptop" class="btn btn-primary">Shop Now</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="features">
    <div class="container">
        <div class="features-grid">
            <div class="feature">
                <i class="fas fa-shipping-fast"></i>
                <h3>Fast Shipping</h3>
                <p>Free shipping on all orders over $50</p>
            </div>
            <div class="feature">
                <i class="fas fa-lock"></i>
                <h3>Secure Payments</h3>
                <p>100% secure payment processing</p>
            </div>
            <div class="feature">
                <i class="fas fa-headset"></i>
                <h3>24/7 Support</h3>
                <p>Dedicated support team available</p>
            </div>
            <div class="feature">
                <i class="fas fa-undo"></i>
                <h3>Easy Returns</h3>
                <p>30-day money-back guarantee</p>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
require_once "includes/footer.php";
?> 
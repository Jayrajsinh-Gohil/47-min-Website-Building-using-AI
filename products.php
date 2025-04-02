<?php
// Include header
require_once "includes/header.php";

// Set page title
$page_title = "Products";

// Get category filter
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Get search query
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Get sort option
$sort_option = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Prepare SQL query
$sql = "SELECT * FROM products WHERE 1=1";

// Add category filter if set
if (!empty($category_filter)) {
    $sql .= " AND category = '" . mysqli_real_escape_string($conn, $category_filter) . "'";
}

// Add search filter if set
if (!empty($search_query)) {
    $sql .= " AND (name LIKE '%" . mysqli_real_escape_string($conn, $search_query) . "%' OR description LIKE '%" . mysqli_real_escape_string($conn, $search_query) . "%')";
}

// Add sorting
switch ($sort_option) {
    case 'price_low':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY price DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY name DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY id DESC";
        break;
}

// Execute query
$result = mysqli_query($conn, $sql);

// Get all categories for filter
$categories_sql = "SELECT DISTINCT category FROM products ORDER BY category";
$categories_result = mysqli_query($conn, $categories_sql);
$categories = [];
while ($category_row = mysqli_fetch_assoc($categories_result)) {
    $categories[] = $category_row['category'];
}
?>

<div class="products-container">
    <div class="products-header">
        <h1>Products</h1>
        <div class="products-filters">
            <form action="products.php" method="get" class="filter-form">
                <?php if (!empty($category_filter)): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                <?php endif; ?>
                
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </div>
                
                <div class="sort-box">
                    <label for="sort">Sort by:</label>
                    <select name="sort" id="sort" onchange="this.form.submit()">
                        <option value="newest" <?php echo $sort_option == 'newest' ? 'selected' : ''; ?>>Newest</option>
                        <option value="price_low" <?php echo $sort_option == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort_option == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name_asc" <?php echo $sort_option == 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                        <option value="name_desc" <?php echo $sort_option == 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                    </select>
                </div>
            </form>
        </div>
    </div>
    
    <div class="products-content">
        <div class="products-sidebar">
            <h3>Categories</h3>
            <ul class="category-list">
                <li><a href="products.php<?php echo !empty($search_query) ? '?search=' . urlencode($search_query) : ''; ?>" class="<?php echo empty($category_filter) ? 'active' : ''; ?>">All Products</a></li>
                <?php foreach ($categories as $category): ?>
                    <li>
                        <a href="products.php?category=<?php echo urlencode($category); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="<?php echo $category_filter == $category ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($category); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="products-grid">
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($product = mysqli_fetch_assoc($result)) {
                    ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="assets/images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
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
                echo "<p class='no-products'>No products found matching your criteria.</p>";
            }
            ?>
        </div>
    </div>
</div>

<?php
// Include footer
require_once "includes/footer.php";
?> 
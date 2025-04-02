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

// Process delete product
if(isset($_POST["delete_product"]) && !empty($_POST["product_id"])){
    $product_id = $_POST["product_id"];
    
    // Check if product exists
    $sql = "SELECT * FROM products WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) == 1){
                $product = mysqli_fetch_assoc($result);
                
                // Delete product
                $sql = "DELETE FROM products WHERE id = ?";
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "i", $product_id);
                    
                    if(mysqli_stmt_execute($stmt)){
                        // Delete product image if exists
                        if(!empty($product["image"]) && file_exists("../uploads/" . $product["image"])){
                            unlink("../uploads/" . $product["image"]);
                        }
                        
                        $_SESSION["success"] = "Product deleted successfully.";
                    } else {
                        $_SESSION["error"] = "Oops! Something went wrong. Please try again later.";
                    }
                    
                    mysqli_stmt_close($stmt);
                }
            } else {
                $_SESSION["error"] = "Product not found.";
            }
        } else {
            $_SESSION["error"] = "Oops! Something went wrong. Please try again later.";
        }
    }
    
    header("location: products.php");
    exit;
}

// Get products with search, filter, and pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Prepare SQL query
$sql = "SELECT * FROM products";
$count_sql = "SELECT COUNT(*) as total FROM products";

// Add filters
$where_clauses = [];

if (!empty($search)) {
    $search_term = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $where_clauses[] = "(name LIKE '$search_term' OR description LIKE '$search_term')";
}

if (!empty($category_filter)) {
    $category = mysqli_real_escape_string($conn, $category_filter);
    $where_clauses[] = "category = '$category'";
}

if (!empty($where_clauses)) {
    $where_clause = " WHERE " . implode(" AND ", $where_clauses);
    $sql .= $where_clause;
    $count_sql .= $where_clause;
}

// Add sorting and pagination
$sql .= " ORDER BY created_at DESC LIMIT $offset, $limit";

// Get total products count
$total_products = 0;
$result = mysqli_query($conn, $count_sql);
if($result){
    $row = mysqli_fetch_assoc($result);
    $total_products = $row['total'];
}

// Calculate total pages
$total_pages = ceil($total_products / $limit);

// Get products
$products = [];
$result = mysqli_query($conn, $sql);
if($result){
    while($row = mysqli_fetch_assoc($result)){
        $products[] = $row;
    }
}

// Get distinct categories for filter
$categories = [];
$category_sql = "SELECT DISTINCT category FROM products ORDER BY category";
$result = mysqli_query($conn, $category_sql);
if($result){
    while($row = mysqli_fetch_assoc($result)){
        $categories[] = $row['category'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - MobileStore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h3>Admin Panel</h3>
            <ul>
                <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php" class="active"><i class="fas fa-mobile-alt"></i> Products</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Back to Site</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h2>Manage Products</h2>
                <a href="add_product.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Product</a>
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
                <form action="products.php" method="get" class="filter-form">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                    
                    <div class="filter-box">
                        <label for="category">Filter by Category:</label>
                        <select name="category" id="category" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category_filter == $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <div class="admin-table-container">
                <?php if(empty($products)): ?>
                    <p>No products found.</p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($products as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td>
                                        <img src="../uploads/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-thumbnail">
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo $product['stock']; ?></td>
                                    <td>
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')"><i class="fas fa-trash"></i> Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if($page > 1): ?>
                                <a href="products.php?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>" class="btn btn-secondary">&laquo; Previous</a>
                            <?php endif; ?>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="products.php?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>" class="btn <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            
                            <?php if($page < $total_pages): ?>
                                <a href="products.php?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>" class="btn btn-secondary">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Confirm Delete</h2>
            <p>Are you sure you want to delete the product: <span id="product-name"></span>?</p>
            <p>This action cannot be undone.</p>
            <form action="products.php" method="post">
                <input type="hidden" name="product_id" id="product_id">
                <div class="form-group">
                    <button type="submit" name="delete_product" class="btn btn-danger">Delete</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script>
        // Modal functions
        const modal = document.getElementById('deleteModal');
        
        function confirmDelete(productId, productName) {
            document.getElementById('product_id').value = productId;
            document.getElementById('product-name').textContent = productName;
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
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

// Check if product ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    $_SESSION["error"] = "Product ID is required.";
    header("location: products.php");
    exit;
}

$product_id = $_GET["id"];

// Define variables and initialize with empty values
$name = $description = $price = $category = $stock = $image = "";
$name_err = $description_err = $price_err = $category_err = $stock_err = $image_err = "";
$current_image = "";

// Get product details
$sql = "SELECT * FROM products WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $product = mysqli_fetch_assoc($result);
            
            // Set values
            $name = $product["name"];
            $description = $product["description"];
            $price = $product["price"];
            $category = $product["category"];
            $stock = $product["stock"];
            $current_image = $product["image"];
        } else {
            // Product not found
            $_SESSION["error"] = "Product not found.";
            header("location: products.php");
            exit;
        }
    } else {
        $_SESSION["error"] = "Oops! Something went wrong. Please try again later.";
        header("location: products.php");
        exit;
    }
    
    mysqli_stmt_close($stmt);
} else {
    $_SESSION["error"] = "Oops! Something went wrong. Please try again later.";
    header("location: products.php");
    exit;
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate name
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter a product name.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter a product description.";
    } else {
        $description = trim($_POST["description"]);
    }
    
    // Validate price
    if(empty(trim($_POST["price"]))){
        $price_err = "Please enter a product price.";
    } elseif(!is_numeric(trim($_POST["price"])) || floatval(trim($_POST["price"])) <= 0){
        $price_err = "Please enter a valid price.";
    } else {
        $price = trim($_POST["price"]);
    }
    
    // Validate category
    if(empty(trim($_POST["category"]))){
        $category_err = "Please enter a product category.";
    } else {
        $category = trim($_POST["category"]);
    }
    
    // Validate stock
    if(empty(trim($_POST["stock"]))){
        $stock_err = "Please enter product stock.";
    } elseif(!is_numeric(trim($_POST["stock"])) || intval(trim($_POST["stock"])) < 0){
        $stock_err = "Please enter a valid stock number.";
    } else {
        $stock = trim($_POST["stock"]);
    }
    
    // Check if image is uploaded
    $new_image = $current_image; // Default to current image
    
    if(isset($_FILES["image"]) && $_FILES["image"]["error"] != 4){ // 4 means no file was uploaded
        // Check if image file is an actual image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check === false) {
            $image_err = "File is not an image.";
        }
        
        // Check file size (max 5MB)
        elseif ($_FILES["image"]["size"] > 5000000) {
            $image_err = "Sorry, your file is too large. Max size is 5MB.";
        }
        
        // Allow certain file formats
        else {
            $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
                $image_err = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            } else {
                // Generate unique filename
                $new_image = uniqid() . "." . $imageFileType;
                $target_file = "../uploads/" . $new_image;
                
                // Upload file
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // Delete old image if exists and different from default
                    if(!empty($current_image) && file_exists("../uploads/" . $current_image)){
                        unlink("../uploads/" . $current_image);
                    }
                } else {
                    $image_err = "Sorry, there was an error uploading your file.";
                    $new_image = $current_image; // Keep current image on error
                }
            }
        }
    }
    
    // Check input errors before updating in database
    if(empty($name_err) && empty($description_err) && empty($price_err) && empty($category_err) && empty($stock_err) && empty($image_err)){
        
        // Prepare an update statement
        $sql = "UPDATE products SET name = ?, description = ?, price = ?, category = ?, stock = ?, image = ? WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ssdsisi", $name, $description, $price, $category, $stock, $new_image, $product_id);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Product updated successfully
                $_SESSION["success"] = "Product updated successfully.";
                header("location: products.php");
                exit();
            } else {
                $_SESSION["error"] = "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - MobileStore</title>
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
                <h2>Edit Product</h2>
                <a href="products.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Products</a>
            </div>
            
            <?php if(isset($_SESSION["error"])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION["error"]; ?>
                    <?php unset($_SESSION["error"]); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $product_id; ?>" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" name="name" id="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($name); ?>">
                        <span class="invalid-feedback"><?php echo $name_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="5"><?php echo htmlspecialchars($description); ?></textarea>
                        <span class="invalid-feedback"><?php echo $description_err; ?></span>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="price">Price ($)</label>
                            <input type="number" name="price" id="price" step="0.01" min="0" class="form-control <?php echo (!empty($price_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($price); ?>">
                            <span class="invalid-feedback"><?php echo $price_err; ?></span>
                        </div>
                        
                        <div class="form-group col-md-4">
                            <label for="category">Category</label>
                            <input type="text" name="category" id="category" class="form-control <?php echo (!empty($category_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($category); ?>">
                            <span class="invalid-feedback"><?php echo $category_err; ?></span>
                        </div>
                        
                        <div class="form-group col-md-4">
                            <label for="stock">Stock</label>
                            <input type="number" name="stock" id="stock" min="0" class="form-control <?php echo (!empty($stock_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($stock); ?>">
                            <span class="invalid-feedback"><?php echo $stock_err; ?></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <div class="image-preview-container">
                            <?php if(!empty($current_image)): ?>
                                <div class="current-image">
                                    <img src="../uploads/<?php echo $current_image; ?>" alt="Current Product Image" class="image-preview">
                                    <p>Current Image</p>
                                </div>
                            <?php endif; ?>
                            <div class="image-upload">
                                <input type="file" name="image" id="image" class="form-control-file <?php echo (!empty($image_err)) ? 'is-invalid' : ''; ?>" accept="image/*">
                                <small class="form-text text-muted">Leave empty to keep current image. Max size: 5MB. Allowed formats: JPG, JPEG, PNG, GIF.</small>
                                <span class="invalid-feedback"><?php echo $image_err; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Update Product</button>
                        <a href="products.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script>
        // Image preview
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    // Create preview if it doesn't exist
                    let preview = document.querySelector('.new-image');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'new-image';
                        document.querySelector('.image-preview-container').appendChild(preview);
                    }
                    
                    // Update preview
                    preview.innerHTML = `
                        <img src="${event.target.result}" alt="New Product Image" class="image-preview">
                        <p>New Image</p>
                    `;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 
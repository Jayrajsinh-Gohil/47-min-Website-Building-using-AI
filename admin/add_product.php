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

// Define variables and initialize with empty values
$name = $description = $price = $category = $stock = $image = "";
$name_err = $description_err = $price_err = $category_err = $stock_err = $image_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate name
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter product name.";
    } else{
        $name = trim($_POST["name"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter product description.";
    } else{
        $description = trim($_POST["description"]);
    }
    
    // Validate price
    if(empty(trim($_POST["price"]))){
        $price_err = "Please enter product price.";
    } elseif(!is_numeric($_POST["price"]) || $_POST["price"] <= 0){
        $price_err = "Please enter a valid price.";
    } else{
        $price = trim($_POST["price"]);
    }
    
    // Validate category
    if(empty(trim($_POST["category"]))){
        $category_err = "Please enter product category.";
    } else{
        $category = trim($_POST["category"]);
    }
    
    // Validate stock
    if(empty(trim($_POST["stock"]))){
        $stock_err = "Please enter product stock.";
    } elseif(!is_numeric($_POST["stock"]) || $_POST["stock"] < 0){
        $stock_err = "Please enter a valid stock quantity.";
    } else{
        $stock = trim($_POST["stock"]);
    }
    
    // Validate image
    if(empty($_FILES["image"]["name"])){
        $image_err = "Please select an image.";
    } else{
        $target_dir = "../assets/images/";
        $image = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check === false) {
            $image_err = "File is not an image.";
        }
        
        // Check file size
        if ($_FILES["image"]["size"] > 5000000) {
            $image_err = "Sorry, your file is too large.";
        }
        
        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
            $image_err = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        }
    }
    
    // Check input errors before inserting in database
    if(empty($name_err) && empty($description_err) && empty($price_err) && empty($category_err) && empty($stock_err) && empty($image_err)){
        // Upload image
        if(move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)){
            // Prepare an insert statement
            $sql = "INSERT INTO products (name, description, price, image, stock, category) VALUES (?, ?, ?, ?, ?, ?)";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "ssdsss", $param_name, $param_description, $param_price, $param_image, $param_stock, $param_category);
                
                // Set parameters
                $param_name = $name;
                $param_description = $description;
                $param_price = $price;
                $param_image = $image;
                $param_stock = $stock;
                $param_category = $category;
                
                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){
                    // Product added successfully
                    $_SESSION["success"] = "Product added successfully.";
                    header("location: products.php");
                    exit;
                } else{
                    $_SESSION["error"] = "Oops! Something went wrong. Please try again later.";
                }
                
                // Close statement
                mysqli_stmt_close($stmt);
            }
        } else{
            $_SESSION["error"] = "Sorry, there was an error uploading your file.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - MobileStore</title>
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
                <h2>Add New Product</h2>
                <a href="products.php" class="btn btn-secondary">Back to Products</a>
            </div>
            
            <?php if(isset($_SESSION["error"])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION["error"]; ?>
                    <?php unset($_SESSION["error"]); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container admin-form">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" class="validate">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>" required>
                        <?php if(!empty($name_err)): ?>
                            <div class="error-message"><?php echo $name_err; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" class="form-control <?php echo (!empty($category_err)) ? 'is-invalid' : ''; ?>" required>
                            <option value="">Select Category</option>
                            <option value="Smartphone" <?php echo ($category == "Smartphone") ? "selected" : ""; ?>>Smartphone</option>
                            <option value="Tablet" <?php echo ($category == "Tablet") ? "selected" : ""; ?>>Tablet</option>
                            <option value="Laptop" <?php echo ($category == "Laptop") ? "selected" : ""; ?>>Laptop</option>
                            <option value="Accessory" <?php echo ($category == "Accessory") ? "selected" : ""; ?>>Accessory</option>
                        </select>
                        <?php if(!empty($category_err)): ?>
                            <div class="error-message"><?php echo $category_err; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Price ($)</label>
                        <input type="number" name="price" step="0.01" min="0.01" class="form-control <?php echo (!empty($price_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $price; ?>" required>
                        <?php if(!empty($price_err)): ?>
                            <div class="error-message"><?php echo $price_err; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Stock</label>
                        <input type="number" name="stock" min="0" class="form-control <?php echo (!empty($stock_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $stock; ?>" required>
                        <?php if(!empty($stock_err)): ?>
                            <div class="error-message"><?php echo $stock_err; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="5" required><?php echo $description; ?></textarea>
                        <?php if(!empty($description_err)): ?>
                            <div class="error-message"><?php echo $description_err; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Product Image</label>
                        <input type="file" name="image" id="product-image" class="form-control <?php echo (!empty($image_err)) ? 'is-invalid' : ''; ?>" required>
                        <?php if(!empty($image_err)): ?>
                            <div class="error-message"><?php echo $image_err; ?></div>
                        <?php endif; ?>
                        <div class="form-text">Allowed file types: JPG, JPEG, PNG, GIF. Max size: 5MB.</div>
                        <div class="image-preview">
                            <img id="image-preview" src="#" alt="Image Preview" style="display: none; max-width: 200px; max-height: 200px; margin-top: 10px;">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Add Product</button>
                        <button type="reset" class="btn btn-secondary">Reset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html> 
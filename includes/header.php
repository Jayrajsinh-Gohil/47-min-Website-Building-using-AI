<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config file
require_once "config.php";

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
}

// Function to check if user is admin
function isAdmin() {
    return isLoggedIn() && isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === 1;
}

// Get cart count
$cart_count = 0;
if(isLoggedIn()) {
    $user_id = $_SESSION["id"];
    $cart_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
    if($stmt = mysqli_prepare($conn, $cart_query)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if(mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            mysqli_stmt_bind_result($stmt, $cart_count);
            mysqli_stmt_fetch($stmt);
            if($cart_count === null) {
                $cart_count = 0;
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MobileStore - Premium Mobile Devices</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <h1>MobileStore</h1>
                </a>
            </div>
            <nav>
                <ul class="menu">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </nav>
            <div class="user-actions">
                <?php if(isLoggedIn()): ?>
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if($cart_count > 0): ?>
                            <span class="cart-count"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="user-menu">
                        <span class="username"><?php echo htmlspecialchars($_SESSION["username"]); ?> <i class="fas fa-chevron-down"></i></span>
                        <div class="dropdown-menu">
                            <a href="profile.php">My Profile</a>
                            <a href="orders.php">My Orders</a>
                            <?php if(isAdmin()): ?>
                                <a href="admin/index.php">Admin Panel</a>
                            <?php endif; ?>
                            <a href="logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-login">Login</a>
                    <a href="register.php" class="btn btn-register">Sign Up</a>
                <?php endif; ?>
                <button class="mobile-menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>
    <div class="mobile-menu">
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="about.php">About Us</a></li>
            <li><a href="contact.php">Contact</a></li>
            <?php if(isLoggedIn()): ?>
                <li><a href="cart.php">Cart (<?php echo $cart_count; ?>)</a></li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <?php if(isAdmin()): ?>
                    <li><a href="admin/index.php">Admin Panel</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <main class="container"><?php if(isset($page_title)): ?><h1 class="page-title"><?php echo $page_title; ?></h1><?php endif; ?> 
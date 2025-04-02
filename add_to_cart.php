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

// Check if product ID and quantity are provided
if(!isset($_POST["product_id"]) || !isset($_POST["quantity"])){
    header("location: products.php");
    exit;
}

$product_id = $_POST["product_id"];
$quantity = $_POST["quantity"];
$user_id = $_SESSION["id"];

// Validate product ID and quantity
if(!is_numeric($product_id) || !is_numeric($quantity) || $quantity < 1){
    header("location: products.php");
    exit;
}

// Check if product exists and has enough stock
$sql = "SELECT * FROM products WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $product = mysqli_fetch_assoc($result);
            
            // Check if there's enough stock
            if($product["stock"] < $quantity){
                // Not enough stock
                $_SESSION["cart_error"] = "Sorry, we don't have enough stock for this product. Available: " . $product["stock"];
                header("location: product.php?id=" . $product_id);
                exit;
            }
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

// Check if product is already in cart
$sql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            // Product already in cart, update quantity
            $cart_item = mysqli_fetch_assoc($result);
            $new_quantity = $cart_item["quantity"] + $quantity;
            
            // Check if new quantity exceeds stock
            if($new_quantity > $product["stock"]){
                $new_quantity = $product["stock"];
                $_SESSION["cart_message"] = "Quantity adjusted to maximum available stock.";
            }
            
            $sql = "UPDATE cart SET quantity = ? WHERE id = ?";
            if($update_stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($update_stmt, "ii", $new_quantity, $cart_item["id"]);
                
                if(mysqli_stmt_execute($update_stmt)){
                    $_SESSION["cart_success"] = "Cart updated successfully.";
                    header("location: cart.php");
                    exit;
                } else {
                    echo "Oops! Something went wrong. Please try again later.";
                }
                
                mysqli_stmt_close($update_stmt);
            }
        } else {
            // Product not in cart, add it
            $sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
            if($insert_stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($insert_stmt, "iii", $user_id, $product_id, $quantity);
                
                if(mysqli_stmt_execute($insert_stmt)){
                    $_SESSION["cart_success"] = "Product added to cart successfully.";
                    header("location: cart.php");
                    exit;
                } else {
                    echo "Oops! Something went wrong. Please try again later.";
                }
                
                mysqli_stmt_close($insert_stmt);
            }
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "Oops! Something went wrong. Please try again later.";
}

// Close connection
mysqli_close($conn);
?> 
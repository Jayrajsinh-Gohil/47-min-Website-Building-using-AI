<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then return error
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

// Include config file
require_once "includes/config.php";

// Check if product ID and quantity are provided
if(!isset($_POST["product_id"]) || !isset($_POST["quantity"])){
    echo json_encode(["success" => false, "message" => "Missing product ID or quantity"]);
    exit;
}

$product_id = $_POST["product_id"];
$quantity = $_POST["quantity"];
$user_id = $_SESSION["id"];

// Validate product ID and quantity
if(!is_numeric($product_id) || !is_numeric($quantity) || $quantity < 1){
    echo json_encode(["success" => false, "message" => "Invalid product ID or quantity"]);
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
                echo json_encode([
                    "success" => false, 
                    "message" => "Not enough stock. Available: " . $product["stock"]
                ]);
                exit;
            }
        } else {
            // Product not found
            echo json_encode(["success" => false, "message" => "Product not found"]);
            exit;
        }
    } else {
        echo json_encode(["success" => false, "message" => "Database error"]);
        exit;
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}

// Update cart item
$sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "iii", $quantity, $user_id, $product_id);
    
    if(mysqli_stmt_execute($stmt)){
        // Calculate new cart total
        $total_sql = "SELECT SUM(p.price * c.quantity) as total, SUM(c.quantity) as cart_count
                      FROM cart c 
                      JOIN products p ON c.product_id = p.id 
                      WHERE c.user_id = ?";
        
        if($total_stmt = mysqli_prepare($conn, $total_sql)){
            mysqli_stmt_bind_param($total_stmt, "i", $user_id);
            
            if(mysqli_stmt_execute($total_stmt)){
                $total_result = mysqli_stmt_get_result($total_stmt);
                $total_row = mysqli_fetch_assoc($total_result);
                $cart_total = $total_row["total"];
                $cart_count = $total_row["cart_count"];
                
                // Calculate item subtotal
                $item_subtotal = $product["price"] * $quantity;
                
                echo json_encode([
                    "success" => true,
                    "message" => "Cart updated successfully",
                    "total" => (float)$cart_total,
                    "item_subtotal" => (float)$item_subtotal,
                    "cart_count" => (int)$cart_count
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Error calculating total"]);
            }
            
            mysqli_stmt_close($total_stmt);
        } else {
            echo json_encode(["success" => false, "message" => "Error calculating total"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Error updating cart"]);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
}

// Close connection
mysqli_close($conn);
?> 
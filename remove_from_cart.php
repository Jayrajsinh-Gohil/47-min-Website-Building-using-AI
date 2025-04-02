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

// Check if cart item ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: cart.php");
    exit;
}

$cart_id = $_GET["id"];
$user_id = $_SESSION["id"];

// Validate cart ID
if(!is_numeric($cart_id)){
    header("location: cart.php");
    exit;
}

// Check if cart item belongs to the user
$sql = "SELECT * FROM cart WHERE id = ? AND user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) != 1){
            // Cart item not found or doesn't belong to the user
            header("location: cart.php");
            exit;
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "Oops! Something went wrong. Please try again later.";
}

// Delete cart item
$sql = "DELETE FROM cart WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $cart_id);
    
    if(mysqli_stmt_execute($stmt)){
        $_SESSION["cart_success"] = "Item removed from cart successfully.";
        header("location: cart.php");
        exit;
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
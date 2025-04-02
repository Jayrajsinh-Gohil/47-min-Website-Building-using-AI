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

// Process delete user
if(isset($_GET["delete"]) && !empty($_GET["delete"])){
    $user_id = $_GET["delete"];
    
    // Check if user exists and is not the current user
    if($user_id != $_SESSION["id"]){
        $sql = "SELECT * FROM users WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                
                if(mysqli_num_rows($result) == 1){
                    // User exists, delete it
                    $sql = "DELETE FROM users WHERE id = ?";
                    if($delete_stmt = mysqli_prepare($conn, $sql)){
                        mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
                        
                        if(mysqli_stmt_execute($delete_stmt)){
                            // User deleted successfully
                            $_SESSION["success"] = "User deleted successfully.";
                            header("location: users.php");
                            exit;
                        } else {
                            $_SESSION["error"] = "Oops! Something went wrong. Please try again later.";
                        }
                        
                        mysqli_stmt_close($delete_stmt);
                    }
                } else {
                    // User not found
                    $_SESSION["error"] = "User not found.";
                    header("location: users.php");
                    exit;
                }
            } else {
                $_SESSION["error"] = "Oops! Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    } else {
        $_SESSION["error"] = "You cannot delete your own account.";
        header("location: users.php");
        exit;
    }
}

// Process toggle admin status
if(isset($_GET["toggle_admin"]) && !empty($_GET["toggle_admin"])){
    $user_id = $_GET["toggle_admin"];
    
    // Check if user exists and is not the current user
    if($user_id != $_SESSION["id"]){
        $sql = "SELECT * FROM users WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                
                if(mysqli_num_rows($result) == 1){
                    $user = mysqli_fetch_assoc($result);
                    $new_status = $user["is_admin"] ? 0 : 1;
                    
                    // Toggle admin status
                    $sql = "UPDATE users SET is_admin = ? WHERE id = ?";
                    if($update_stmt = mysqli_prepare($conn, $sql)){
                        mysqli_stmt_bind_param($update_stmt, "ii", $new_status, $user_id);
                        
                        if(mysqli_stmt_execute($update_stmt)){
                            // Status updated successfully
                            $_SESSION["success"] = "User admin status updated successfully.";
                            header("location: users.php");
                            exit;
                        } else {
                            $_SESSION["error"] = "Oops! Something went wrong. Please try again later.";
                        }
                        
                        mysqli_stmt_close($update_stmt);
                    }
                } else {
                    // User not found
                    $_SESSION["error"] = "User not found.";
                    header("location: users.php");
                    exit;
                }
            } else {
                $_SESSION["error"] = "Oops! Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    } else {
        $_SESSION["error"] = "You cannot change your own admin status.";
        header("location: users.php");
        exit;
    }
}

// Get users with search and pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Prepare SQL query
$sql = "SELECT * FROM users";
$count_sql = "SELECT COUNT(*) as total FROM users";

// Add search filter if set
if (!empty($search)) {
    $search_term = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $sql .= " WHERE username LIKE '$search_term' OR email LIKE '$search_term' OR full_name LIKE '$search_term'";
    $count_sql .= " WHERE username LIKE '$search_term' OR email LIKE '$search_term' OR full_name LIKE '$search_term'";
}

// Add sorting and pagination
$sql .= " ORDER BY id DESC LIMIT $offset, $limit";

// Get total users count
$total_users = 0;
$result = mysqli_query($conn, $count_sql);
if($result){
    $row = mysqli_fetch_assoc($result);
    $total_users = $row['total'];
}

// Calculate total pages
$total_pages = ceil($total_users / $limit);

// Get users
$users = [];
$result = mysqli_query($conn, $sql);
if($result){
    while($row = mysqli_fetch_assoc($result)){
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - MobileStore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h3>Admin Panel</h3>
            <ul>
                <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-mobile-alt"></i> Products</a></li>
                <li><a href="users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Back to Site</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h2>Manage Users</h2>
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
                <form action="users.php" method="get" class="search-form">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
            
            <div class="admin-table-container">
                <?php if(empty($users)): ?>
                    <p>No users found.</p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Full Name</th>
                                <th>Role</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td>
                                        <?php if($user['is_admin']): ?>
                                            <span class="badge badge-admin">Admin</span>
                                        <?php else: ?>
                                            <span class="badge badge-user">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if($user['id'] != $_SESSION['id']): ?>
                                            <a href="users.php?toggle_admin=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm" onclick="return confirm('Are you sure you want to <?php echo $user['is_admin'] ? 'remove admin rights from' : 'make admin'; ?> this user?')">
                                                <?php echo $user['is_admin'] ? 'Remove Admin' : 'Make Admin'; ?>
                                            </a>
                                            <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm delete-btn" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                        <?php else: ?>
                                            <span class="text-muted">Current User</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if($page > 1): ?>
                                <a href="users.php?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-secondary">&laquo; Previous</a>
                            <?php endif; ?>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="users.php?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            
                            <?php if($page < $total_pages): ?>
                                <a href="users.php?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-secondary">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html> 
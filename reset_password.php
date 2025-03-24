<?php
session_start();

$conn = mysqli_connect("localhost", "root", "", "employee_db");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$token = $new_password = $confirm_password = "";
$token_err = $password_err = "";
$success = false;

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    error_log("Processing reset token: " . $token);
    
    // Verify token and check expiration
    $sql = "SELECT id, email, reset_token_expiry FROM users WHERE reset_token = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $user_id, $email, $expiry);
                mysqli_stmt_fetch($stmt);
                
                // Check if token has expired
                if (strtotime($expiry) > time()) {
                    error_log("Valid token found for user ID: " . $user_id);
                } else {
                    error_log("Token expired. Expiry time: " . $expiry);
                    $token_err = "Reset token has expired. Please request a new password reset.";
                }
            } else {
                error_log("No matching token found in database");
                $token_err = "Invalid reset token.";
            }
        } else {
            error_log("Error executing token verification query: " . mysqli_error($conn));
            $token_err = "Error verifying reset token.";
        }
        mysqli_stmt_close($stmt);
    }
} else {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($token_err)) {
    // Validate password
    if (empty(trim($_POST['new_password']))) {
        $password_err = "Please enter a new password.";
    } elseif (strlen(trim($_POST['new_password'])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $new_password = trim($_POST['new_password']);
    }
    
    // Validate confirm password
    if (empty(trim($_POST['confirm_password']))) {
        $password_err = "Please confirm the password.";
    } else {
        $confirm_password = trim($_POST['confirm_password']);
        if ($new_password != $confirm_password) {
            $password_err = "Passwords do not match.";
        }
    }
    
    if (empty($password_err)) {
        $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = true;
            } else {
                $password_err = "Error updating password.";
            }
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
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        body {
            background-color: #000000;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            overflow-x: hidden;
        }
        .container-fluid {
            flex: 1;
            padding: 0;
            margin: 0;
            display: flex;
        }
        .split-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }
        .welcome-side {
            flex: 1;
            background-color: #000000;
            color: #ffffff;
            padding: 4rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .welcome-side::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, #1a1a1a 0%, #000000 100%);
            z-index: 1;
        }
        .welcome-content {
            position: relative;
            z-index: 2;
        }
        .form-side {
            flex: 1;
            background-color: #ffffff;
            padding: 4rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reset-card {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
        }
        .btn-dark {
            background-color: #000000;
            text-color: white;
            border: 2px solid #000000;
            border-radius: 8px;
            padding: 1rem;
            width: 100%;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.2s ease-in-out;
            margin-top: 1rem;
        }
        .btn-dark:hover {
            background-color: #333333;
            color: #ffffff;
            border-color: #333333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="split-container">
            <div class="welcome-side">
                <div class="welcome-content">
                    <h1>Reset Your Password</h1>
                    <p>Please enter your new password below.</p>
                </div>
            </div>
            <div class="form-side">
                <div class="reset-card">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            Your password has been reset successfully. You can now <a href="login.php">login</a> with your new password.
                        </div>
                    <?php else: ?>
                        <h2>Reset Password</h2>
                        
                        <?php if (!empty($token_err)): ?>
                            <div class="alert alert-danger">
                                <?php echo $token_err; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($password_err)): ?>
                            <div class="alert alert-danger">
                                <?php echo $password_err; ?>
                            </div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?token=' . $token; ?>" method="post">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <div class="form-text">Password must be at least 6 characters long.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                            <button type="submit" class="btn btn-dark">Reset Password</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
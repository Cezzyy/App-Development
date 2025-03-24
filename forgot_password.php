<?php
session_start();

$conn = mysqli_connect("localhost", "root", "", "employee_db");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$email = $email_err = "";
$success_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate email
    if (empty(trim($_POST['email']))) {
        $email_err = "Please enter your email address.";
    } else {
        $email = trim($_POST['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        }
    }

    if (empty($email_err)) {
        // Check if email exists in database
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    // Generate reset token
                    $token = bin2hex(random_bytes(32));
                    $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    // Store token in database
                    $update_sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?";
                    if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                        mysqli_stmt_bind_param($update_stmt, "sss", $token, $token_expiry, $email);

                        if (mysqli_stmt_execute($update_stmt)) {
                            // Send reset email
                            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/AppDev/reset_password.php?token=" . $token;
                            $to = $email;
                            $subject = "Password Reset Request";
                            $message = "Hello,\n\nYou have requested to reset your password. Please click the link below to reset your password:\n\n" . $reset_link . "\n\nThis link will expire in 1 hour.\n\nIf you did not request this, please ignore this email.";

                            require_once 'config/mailer.php';
                            if (sendEmail($to, $subject, $message)) {
                                $success_message = "If the email exists in our system, a password reset link has been sent. Please check your email.";
                            } else {
                                error_log("Failed to send password reset email to: " . $email);
                                $email_err = "Error sending reset email. Please check your email configuration or try again later.";
                            }
                        } else {
                            $email_err = "Error processing request. Please try again later.";
                        }
                        mysqli_stmt_close($update_stmt);
                    }
                } else {
                    // For security, don't reveal if email exists or not
                    $success_message = "If the email exists in our system, a password reset link has been sent. Please check your email.";
                }
            } else {
                $email_err = "Error processing request. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
        .forgot-card {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
        }
        .btn-dark {
            background-color: #000000;
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
                    <p>Enter your email address and we'll send you instructions to reset your password.</p>
                </div>
            </div>
            <div class="form-side">
                <div class="forgot-card">
                    <h2>Forgot Password</h2>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($email_err)): ?>
                        <div class="alert alert-danger">
                            <?php echo $email_err; ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>">
                        </div>
                        <button type="submit" class="btn btn-dark">Send Reset Link</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="login.php" class="text-dark">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
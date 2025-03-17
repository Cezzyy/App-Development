<?php
session_start();

$conn = mysqli_connect("localhost", "root", "", "employee_db");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$username = $password = "";
$username_err = $password_err = "";
$login_identifier = ""; // Can be either username or email

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate login identifier (username or email)
    if (empty(trim($_POST['username']))) {
        $username_err = "Please enter your username or email.";
    } else {
        $login_identifier = trim($_POST['username']);
        // Check if input is an email
        if (filter_var($login_identifier, FILTER_VALIDATE_EMAIL)) {
            $sql = "SELECT id, username, password FROM users WHERE email = ?";
        } else {
            $sql = "SELECT id, username, password FROM users WHERE username = ?";
        }
    }

    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST['password']);
    }

    // Check input errors before processing
    if (empty($username_err) && empty($password_err)) {
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $login_identifier);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            $_SESSION['user_id'] = $id;
                            $_SESSION['username'] = $username;
                            header("Location: index.php");
                            exit();
                        } else {
                            $password_err = "The password you entered is incorrect.";
                        }
                    }
                } else {
                    $username_err = "No account found with that username.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
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
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .welcome-side h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .welcome-side p {
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        .form-side {
            flex: 1;
            background-color: #ffffff;
            padding: 4rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
        }
        .login-card h2 {
            color: #000000;
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 2rem;
        }
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 1rem;
            transition: all 0.3s;
            background-color: #f8f8f8;
            font-size: 1rem;
        }
        .form-control:focus {
            border-color: #000000;
            box-shadow: none;
            background-color: #ffffff;
        }
        .form-label {
            color: #000000;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            transition: all 0.3s;
            margin-top: 1rem;
        }
        .btn-dark:hover {
            background-color: #ffffff;
            color: #000000;
            transform: translateY(-2px);
        }
        .error {
            background-color: #ff3333;
            color: white;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 500;
        }
        .register-link {
            text-align: center;
            margin-top: 2rem;
        }
        .register-link a {
            color: #000000;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .register-link a:hover {
            opacity: 0.7;
        }
        @media (max-width: 768px) {
            .split-container {
                flex-direction: column;
            }
            .welcome-side, .form-side {
                padding: 2rem;
            }
            .welcome-side h1 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="split-container">
            <div class="welcome-side">
                <div class="welcome-content">
                    <h1>Welcome!</h1>
                    <p>Log in to access your account and manage your employees efficiently.</p>
                </div>
            </div>
            <div class="form-side">
                <div class="login-card">
                    <h2>Sign In</h2>
                    <?php if (isset($error)): ?>
                        <div class="error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form method="POST" action="" id="loginForm" novalidate>
                        <div class="mb-4">
                            <label for="username" class="form-label">Username or Email</label>
                            <input type="text" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" 
                                   id="username" name="username" required 
                                   value="<?php echo htmlspecialchars($username); ?>"
                                   minlength="3" maxlength="50">
                            <div class="invalid-feedback">
                                <?php echo $username_err; ?>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                                   id="password" name="password" required 
                                   minlength="6">
                            <div class="invalid-feedback">
                                <?php echo $password_err; ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-dark">Sign In</button>
                    </form>
                    <div class="register-link">
                        <p class="mb-0">Don't have an account? <a href="register.php">Register here</a></p>
                        <p class="mb-0 mt-2"><a href="forgot_password.php">Forgot Password?</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Client-side form validation
    document.getElementById('loginForm').addEventListener('submit', function(event) {
        let form = this;
        let isValid = true;
        
        // Username validation
        let username = form.username.value.trim();
        if (username === '') {
            form.username.classList.add('is-invalid');
            form.username.nextElementSibling.textContent = 'Please enter your username';
            isValid = false;
        } else if (username.length < 3) {
            form.username.classList.add('is-invalid');
            form.username.nextElementSibling.textContent = 'Username must be at least 3 characters long';
            isValid = false;
        } else {
            form.username.classList.remove('is-invalid');
            form.username.classList.add('is-valid');
        }

        // Password validation
        let password = form.password.value;
        if (password === '') {
            form.password.classList.add('is-invalid');
            form.password.nextElementSibling.textContent = 'Please enter your password';
            isValid = false;
        } else if (password.length < 6) {
            form.password.classList.add('is-invalid');
            form.password.nextElementSibling.textContent = 'Password must be at least 6 characters long';
            isValid = false;
        } else {
            form.password.classList.remove('is-invalid');
            form.password.classList.add('is-valid');
        }

        if (!isValid) {
            event.preventDefault();
        }
    });

    // Real-time validation
    document.querySelectorAll('#loginForm input').forEach(input => {
        input.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.classList.remove('is-invalid');
            }
        });
    });
    </script>
</body>
</html>
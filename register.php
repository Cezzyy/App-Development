<?php
session_start();

$conn = mysqli_connect("localhost", "root", "", "employee_db");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$username = $password = $confirm_password = $email = "";
$username_err = $password_err = $confirm_password_err = $email_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))) {
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } elseif (strlen(trim($_POST["username"])) < 3) {
        $username_err = "Username must be at least 3 characters long.";
    } else {
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim($_POST["username"]);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', trim($_POST["password"]))) {
        $password_err = "Password must contain at least one uppercase letter, one lowercase letter, and one number.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = trim($_POST["email"]);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $email_err = "This email is already taken.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Check input errors before inserting in database
    if (empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($email_err)) {
        $sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_password, $param_email);
            
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            
            if (mysqli_stmt_execute($stmt)) {
                header("location: login.php");
                exit();
            } else {
                echo "Something went wrong. Please try again later.";
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
    <title>Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .wrapper {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
        }
        h2 {
            color: #000000;
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
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
        label {
            color: #000000;
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn {
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
            margin: 0 0.5rem;
            border-radius: 8px;
        }
        .btn-primary {
            background-color: #000000;
            border: 2px solid #000000;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #ffffff;
            border-color: #000000;
            color: #000000;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background-color: #ffffff;
            border: 2px solid #000000;
            color: #000000;
        }
        .btn-secondary:hover {
            background-color: #000000;
            border-color: #000000;
            color: #ffffff;
            transform: translateY(-2px);
        }
        .invalid-feedback {
            color: #ff3333;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .login-link {
            text-align: center;
            margin-top: 2rem;
        }
        .login-link a {
            color: #000000;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .login-link a:hover {
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
                    <h1>Create an Account now!</h1>
                    <p>Get access to all our features on managing your employees.</p>
                </div>
            </div>
            <div class="form-side">
                <div class="wrapper">
                    <h2>Create Account</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="registerForm" novalidate>

                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" 
                                   class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" 
                                   value="<?php echo htmlspecialchars($username); ?>"
                                   required minlength="3" maxlength="50"
                                   pattern="^[a-zA-Z0-9_]+$">
                            <div class="invalid-feedback">
                                <?php echo $username_err; ?>
                            </div>
                            <small class="form-text text-muted">Username must be at least 3 characters long and can only contain letters, numbers, and underscores.</small>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" 
                                   class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" 
                                   value="<?php echo htmlspecialchars($email); ?>"
                                   required>
                            <div class="invalid-feedback">
                                <?php echo $email_err; ?>
                            </div>
                            <small class="form-text text-muted">Please enter a valid email address. This will be used for password recovery.</small>
                        </div>    
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" 
                                   class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                                   required minlength="6">
                            <div class="invalid-feedback">
                                <?php echo $password_err; ?>
                            </div>
                            <small class="form-text text-muted">Password must be at least 6 characters long and contain at least one uppercase letter, one lowercase letter, and one number.</small>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" 
                                   class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" 
                                   required>
                            <div class="invalid-feedback">
                                <?php echo $confirm_password_err; ?>
                            </div>
                        </div>

                        <div class="form-group text-center">
                            <input type="submit" class="btn btn-primary" value="Create Account">
                            <input type="reset" class="btn btn-secondary" value="Reset">
                        </div>
                        <div class="login-link">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
    // Client-side form validation
    document.getElementById('registerForm').addEventListener('submit', function(event) {
        let form = this;
        let isValid = true;
        
        // Username validation
        let username = form.username.value.trim();
        if (username === '') {
            form.username.classList.add('is-invalid');
            form.username.nextElementSibling.textContent = 'Please enter a username';
            isValid = false;
        } else if (username.length < 3) {
            form.username.classList.add('is-invalid');
            form.username.nextElementSibling.textContent = 'Username must be at least 3 characters long';
            isValid = false;
        } else if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            form.username.classList.add('is-invalid');
            form.username.nextElementSibling.textContent = 'Username can only contain letters, numbers, and underscores';
            isValid = false;
        } else {
            form.username.classList.remove('is-invalid');
            form.username.classList.add('is-valid');
        }

        // Email validation
        let email = form.email.value.trim();
        if (email === '') {
            form.email.classList.add('is-invalid');
            form.email.nextElementSibling.textContent = 'Please enter an email address';
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            form.email.classList.add('is-invalid');
            form.email.nextElementSibling.textContent = 'Please enter a valid email address';
            isValid = false;
        } else {
            form.email.classList.remove('is-invalid');
            form.email.classList.add('is-valid');
        }

        // Password validation
        let password = form.password.value;
        if (password === '') {
            form.password.classList.add('is-invalid');
            form.password.nextElementSibling.textContent = 'Please enter a password';
            isValid = false;
        } else if (password.length < 6) {
            form.password.classList.add('is-invalid');
            form.password.nextElementSibling.textContent = 'Password must be at least 6 characters long';
            isValid = false;
        } else if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(password)) {
            form.password.classList.add('is-invalid');
            form.password.nextElementSibling.textContent = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
            isValid = false;
        } else {
            form.password.classList.remove('is-invalid');
            form.password.classList.add('is-valid');
        }

        // Confirm Password validation
        let confirmPassword = form.confirm_password.value;
        if (confirmPassword === '') {
            form.confirm_password.classList.add('is-invalid');
            form.confirm_password.nextElementSibling.textContent = 'Please confirm your password';
            isValid = false;
        } else if (confirmPassword !== password) {
            form.confirm_password.classList.add('is-invalid');
            form.confirm_password.nextElementSibling.textContent = 'Passwords do not match';
            isValid = false;
        } else {
            form.confirm_password.classList.remove('is-invalid');
            form.confirm_password.classList.add('is-valid');
        }

        if (!isValid) {
            event.preventDefault();
        }
    });

    // Real-time validation
    document.querySelectorAll('#registerForm input').forEach(input => {
        input.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                // Remove invalid class when user starts typing
                this.classList.remove('is-invalid');
                
                // For confirm password, check match when typing
                if (this.name === 'confirm_password') {
                    let password = document.querySelector('input[name="password"]').value;
                    if (this.value === password) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    }
                }
            }
        });
    });
    </script>
</body>
</html>
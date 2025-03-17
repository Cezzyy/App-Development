<?php
session_start();

$conn = mysqli_connect("localhost", "root", "", "employee_db");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$errors = [];
$success = false;

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = $_GET['id'];
$id = mysqli_real_escape_string($conn, $id);

// Fetch existing employee data
$result = mysqli_query($conn, "SELECT * FROM employees WHERE id=$id");
if (!$result || mysqli_num_rows($result) == 0) {
    header('Location: index.php');
    exit();
}
$employee = mysqli_fetch_array($result);

if (isset($_POST['submit'])) {
    // Validations
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $position = trim($_POST['position']);
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);

    // Check if empty
    if (empty($first_name) || empty($last_name) || empty($position) || empty($contact_number) || empty($email)) {
        $errors['form'] = 'Please fill in all required fields';
    } else {
        // Validations
        if (strlen($first_name) > 50 || strlen($last_name) > 50) {
            $errors['form'] = 'Names must not exceed 50 characters';
        } elseif (!preg_match('/^[0-9]{11}$/', $contact_number)) {
            $errors['form'] = 'Contact number must be 11 digits';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['form'] = 'Please enter a valid email address'; 
        }
    }

    // Insert into database
    if (empty($errors)) {
        $first_name = mysqli_real_escape_string($conn, $first_name);
        $last_name = mysqli_real_escape_string($conn, $last_name);
        $position = mysqli_real_escape_string($conn, $position);
        $contact_number = mysqli_real_escape_string($conn, $contact_number);
        $email = mysqli_real_escape_string($conn, $email);

        $query = "UPDATE employees SET 
                    first_name='$first_name', 
                    last_name='$last_name', 
                    position='$position', 
                    contact_number='$contact_number', 
                    email='$email' 
                 WHERE id=$id";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = 'edit';
            header("Location: index.php");
            exit();
        } else {
            $errors['db'] = 'Database error: ' . mysqli_error($conn);
        }
    } else {
        $_SESSION['errors'] = $errors;
        header("Location: edit.php?id=$id");
        exit();
    }
}

if (isset($_SESSION['errors'])) {
    $errors = $_SESSION['errors'];
    unset($_SESSION['errors']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header">
                        <h2 class="mb-0">Edit Employee</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="employeeForm" novalidate>
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" name="first_name" id="first_name" 
                                       class="form-control <?php echo isset($errors['form']) ? 'is-invalid' : ''; ?>"
                                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : htmlspecialchars($employee['first_name']); ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" name="last_name" id="last_name" 
                                       class="form-control <?php echo isset($errors['form']) ? 'is-invalid' : ''; ?>"
                                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : htmlspecialchars($employee['last_name']); ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="position" class="form-label">Position</label>
                                <input type="text" name="position" id="position" 
                                       class="form-control <?php echo isset($errors['form']) ? 'is-invalid' : ''; ?>"
                                       value="<?php echo isset($_POST['position']) ? htmlspecialchars($_POST['position']) : htmlspecialchars($employee['position']); ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" id="contact_number" 
                                       class="form-control <?php echo isset($errors['form']) ? 'is-invalid' : ''; ?>"
                                       value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : htmlspecialchars($employee['contact_number']); ?>"
                                       placeholder="11 digits number" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" 
                                       class="form-control <?php echo isset($errors['form']) ? 'is-invalid' : ''; ?>"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($employee['email']); ?>"
                                       required>
                            </div>

                            <?php if (isset($errors['form'])): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $errors['form']; ?>
                                </div>
                            <?php endif; ?>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                                <button type="submit" name="submit" class="btn btn-primary">Update Employee</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script>
    document.getElementById('employeeForm').addEventListener('submit', function(e) {
        const form = this;
        const requiredFields = ['first_name', 'last_name', 'position', 'contact_number', 'email'];
        let isEmpty = false;

        requiredFields.forEach(field => {
            if (!form[field].value.trim()) {
                isEmpty = true;
            }
        });

        if (isEmpty) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please fill in all required fields',
                confirmButtonColor: '#dc3545'
            });
        }
    });

    <?php if (isset($errors['form'])): ?>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?php echo addslashes($errors['form']); ?>',
        confirmButtonColor: '#dc3545'
    });
    <?php endif; ?>
    </script>
</body>
</html>
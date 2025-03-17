<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "employee_db");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check ID
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Delete the employee
    $query = "DELETE FROM employees WHERE id = '$id'";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = 'delete';
    } else {
        $_SESSION['error'] = "Error deleting record: " . mysqli_error($conn);
    }
}

header("Location: index.php");
exit();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <header class="navbar navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <span class="navbar-brand">Employee Management</span>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </header>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
        <div class="offcanvas-header bg-dark text-white">
            <h5 class="offcanvas-title" id="sidebarLabel">Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="d-grid gap-2">
                <a href="settings.php" class="btn btn-primary mb-2">Settings</a>
                <a href="logout.php" class="btn btn-danger" onclick="confirmLogout(event)">Logout</a>
            </div>
        </div>
    </div>

    <?php
    session_start();

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $conn = mysqli_connect("localhost", "root", "", "employee_db");
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Search
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    if (!empty($search)) {
        $_SESSION['search_term'] = $search;
    } else {
        unset($_SESSION['search_term']);
    }

    // Pagination settings
    $records_per_page = 5;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $records_per_page;

    // Handle success messages
    $successMessage = '';
    if (isset($_SESSION['success'])) {
        $action = $_SESSION['success'];
        switch ($action) {
            case 'add':
                $successMessage = 'Employee added successfully!';
                break;
            case 'edit':
                $successMessage = 'Employee updated successfully!';
                break;
            case 'delete':
                $successMessage = 'Employee deleted successfully!';
                break;
        }
        unset($_SESSION['success']);
    }
    ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="mb-0">Employee Table</h2>
                        <a href="add.php" class="btn btn-primary">Add New Employee</a>
                    </div>
                    <div class="card-body">
                        <!-- Search Form -->
                        <form method="GET" class="mb-4" id="searchForm">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search by first or last name..." value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-outline-secondary">Search</button>
                                <?php if (!empty($search)): ?>
                                    <a href="index.php" class="btn btn-outline-danger">Clear</a>
                                <?php endif; ?>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>First Name</th>
                                        <th>Last Name</th>
                                        <th>Position</th>
                                        <th>Contact Number</th>
                                        <th>Email</th>
                                        <th width="150">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Pagination query
                                    $count_query = "SELECT COUNT(*) as total FROM employees";
                                    if (!empty($search)) {
                                        $search = mysqli_real_escape_string($conn, $search);
                                        $count_query .= " WHERE first_name LIKE '%$search%' OR last_name LIKE '%$search%'";
                                    }
                                    $count_result = mysqli_query($conn, $count_query);
                                    $total_records = mysqli_fetch_assoc($count_result)['total'];
                                    $total_pages = ceil($total_records / $records_per_page);

                                    $query = "SELECT * FROM employees";
                                    if (!empty($search)) {
                                        $query .= " WHERE first_name LIKE '%$search%' OR last_name LIKE '%$search%'";
                                    }
                                    $query .= " LIMIT $offset, $records_per_page";
                                    $result = mysqli_query($conn, $query);
                                    
                                    if (mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_array($result)) {
                                            echo "<tr>";
                                            echo "<td data-label='ID'>".$row['id']."</td>";
                                            echo "<td data-label='First Name'>".htmlspecialchars($row['first_name'])."</td>";
                                            echo "<td data-label='Last Name'>".htmlspecialchars($row['last_name'])."</td>";
                                            echo "<td data-label='Position'>".htmlspecialchars($row['position'])."</td>";
                                            echo "<td data-label='Contact Number'>".htmlspecialchars($row['contact_number'])."</td>";
                                            echo "<td data-label='Email'>".htmlspecialchars($row['email'])."</td>";
                                            echo "<td data-label='Actions'>
                                                    <div class='btn-group'>
                                                        <a href='edit.php?id=".$row['id']."' class='btn btn-info'>Edit</a>
                                                        <button onclick='confirmDelete(".$row['id'].")' class='btn btn-danger'>Delete</button>
                                                    </div>
                                                  </td>";
                                            echo "</tr>";
                                        }
                                        // Clear notifications after use
                                        unset($_SESSION['search_notification']);
                                    } else {
                                        echo "<tr><td colspan='7' class='text-center'>No employees found";
                                        if (!empty($search)) {
                                            echo " for search term: '" . htmlspecialchars($search) . "'";
                                            $_SESSION['search_notification'] = [
                                                'term' => $search,
                                                'shown' => false
                                            ];
                                        }
                                        echo "</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($page - 1) . (!empty($search) ? '&search=' . urlencode($search) : ''); ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i . (!empty($search) ? '&search=' . urlencode($search) : ''); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($page + 1) . (!empty($search) ? '&search=' . urlencode($search) : ''); ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script>
    function confirmLogout(event) {
        event.preventDefault();
        Swal.fire({
            title: 'Are you sure you want to logout?',
            text: "You will need to login again to access the system.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#212529',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, logout'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        });
    }
    <?php if ($successMessage): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '<?php echo $successMessage; ?>',
            confirmButtonText: 'OK',
            confirmButtonColor: '#212529'
        });
    <?php endif; ?>

    <?php 
    if (isset($_SESSION['search_notification']) && !$_SESSION['search_notification']['shown']) {
        $_SESSION['search_notification']['shown'] = true;
    ?>
        Swal.fire({
            icon: 'info',
            title: 'No Results',
            text: 'No employees found for search term: "<?php echo htmlspecialchars($_SESSION['search_notification']['term']); ?>"',
            confirmButtonText: 'OK',
            confirmButtonColor: '#212529'
        });
    <?php 
    }
    if (isset($_SESSION['search_notification']) && $_SESSION['search_notification']['shown']) {
        unset($_SESSION['search_notification']);
    }
    ?>

    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#212529',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'delete.php?id=' + id;
            }
        });
    }
    </script>
</body>
</html>
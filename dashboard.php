<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$username    = $_SESSION['username'];
$role        = $_SESSION['role'];
$hospital_id = $_SESSION['hospital_id'];

$error_user = "";


/* ADD USER  */
if (isset($_POST['add_user'])) {

    if ($role == "admin") {

        $new_username = trim($_POST['username']);
        $password     = trim($_POST['password']);
        $new_role     = $_POST['role'];

        /* USERNAME */
        if (empty($new_username) || empty($password)) {
            $error_user = "Username and password are required.";
        }

        elseif (strlen($new_username) < 5) {
            $error_user = "Username must be at least 5 characters.";
        }

        elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $new_username)) {
            $error_user = "Username can contain only letters, numbers and _.";
        }

        /* PASSWORD */
        elseif (
            strlen($password) < 8 ||
            !preg_match("/[A-Z]/", $password) ||
            !preg_match("/[a-z]/", $password) ||
            !preg_match("/[0-9]/", $password) ||
            !preg_match("/[\W]/", $password)
        ) {
            $error_user = "Password must be 8+ chars and include uppercase, lowercase, number, and special character.";
        }

        else {

            /* CHECK DUPLICATE */
            $check = $conn->prepare("
                SELECT id FROM users
                WHERE username = ? AND hospital_id = ?
            ");

            $check->bind_param("si", $new_username, $hospital_id);
            $check->execute();
            $check_result = $check->get_result();

            if ($check_result->num_rows > 0) {
                $error_user = "Username already exists.";
            } else {

                $hashed = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("
                    INSERT INTO users (username, password, role, hospital_id)
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->bind_param("sssi", $new_username, $hashed, $new_role, $hospital_id);
                $stmt->execute();

                $_SESSION['success'] = "User created successfully.";
                header("Location: dashboard.php");
                exit();
            }
        }
    }
}

/* COUNTS */
$totalMedicines = $conn->query("SELECT id FROM medicines WHERE hospital_id=$hospital_id")->num_rows;
$totalUsers     = $conn->query("SELECT id FROM users WHERE hospital_id=$hospital_id")->num_rows;
$totalAdmins    = $conn->query("SELECT id FROM users WHERE hospital_id=$hospital_id AND role='admin'")->num_rows;

/* MEDICINES STATS */

$totalMedicines = $conn->query("
    SELECT id FROM medicines 
    WHERE hospital_id=$hospital_id
")->num_rows;

$expiredMedicines = $conn->query("
    SELECT id FROM medicines 
    WHERE hospital_id=$hospital_id 
    AND expiry_date < CURDATE()
")->num_rows;

$nearExpiryMedicines = $conn->query("
    SELECT id FROM medicines 
    WHERE hospital_id=$hospital_id 
    AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
")->num_rows;

$availableMedicines = $conn->query("
    SELECT id FROM medicines 
    WHERE hospital_id=$hospital_id 
    AND expiry_date > DATE_ADD(CURDATE(), INTERVAL 7 DAY)
")->num_rows;


/* USERS STATS */

$totalUsers = $conn->query("
    SELECT id FROM users 
    WHERE hospital_id=$hospital_id
")->num_rows;

$totalAdmins = $conn->query("
    SELECT id FROM users 
    WHERE hospital_id=$hospital_id 
    AND role='admin'
")->num_rows;

$totalPharmacists = $conn->query("
    SELECT id FROM users 
    WHERE hospital_id=$hospital_id 
    AND role='pharmacist'
")->num_rows;
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Dashboard</title>

<link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="CSS/dash.css">


</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

<h4 class="mb-4">Pharmacy Dashboard</h4>

<?php if($role == 'admin'): ?>
<button class="btn btn-dark"
data-bs-toggle="modal"
data-bs-target="#addUser">
<i class="bi bi-plus-circle"></i>
Add User
</button>
<?php endif; ?>

<a href="medicines.php" class="btn btn-success">View Medicines</a>

<?php if($role == 'admin'): ?>
<a href="users.php" class="btn btn-primary">View Users</a>
<?php endif; ?>

<a href="logout.php" class="btn btn-outline-danger">Logout</a>
</div>

<!-- MAIN -->
<div class="main">

<!-- TOP -->
<div class="topbar d-flex justify-content-between align-items-center">
<h4>Pharmacy Inventory System</h4>
<span>Welcome, <?= htmlspecialchars($username) ?></span>
</div>

<!-- SUCCESS -->
<?php if(isset($_SESSION['success'])): ?>
<div class="alert alert-success">
<?= $_SESSION['success']; unset($_SESSION['success']); ?>
</div>
<?php endif; ?>

<!-- CARDS -->
<div class="row g-4">

<!-- MEDICINES CARD -->
<div class="col-lg-6">
<a href="medicines.php" style="text-decoration:none;color:inherit;">
<div class="card card-box p-4 h-100">

<div class="d-flex justify-content-between align-items-center mb-4">
<div>
<h5 class="mb-1">Medicines Overview</h5>
<small class="text-muted">Pharmacy Inventory Status</small>
</div>

<div class="fs-1 text-success">
<i class="bi bi-capsule-pill"></i>
</div>
</div>

<div class="row text-center g-3">

<div class="col-6">
<div class="border rounded-4 p-3">
<h6 class="text-muted mb-1">Total</h6>
<h3 class="text-primary mb-0"><?= $totalMedicines ?></h3>
</div>
</div>

<div class="col-6">
<div class="border rounded-4 p-3">
<h6 class="text-muted mb-1">Available</h6>
<h3 class="text-success mb-0"><?= $availableMedicines ?></h3>
</div>
</div>

<div class="col-6">
<div class="border rounded-4 p-3">
<h6 class="text-muted mb-1">Near Expiry</h6>
<h3 class="text-warning mb-0"><?= $nearExpiryMedicines ?></h3>
</div>
</div>

<div class="col-6">
<div class="border rounded-4 p-3">
<h6 class="text-muted mb-1">Expired</h6>
<h3 class="text-danger mb-0"><?= $expiredMedicines ?></h3>
</div>
</div>

</div>

</div>
</a>
</div>

<!-- USERS CARD -->
<div class="col-lg-6">
<a href="users.php" style="text-decoration:none;color:inherit;">
<div class="card card-box p-4 h-100">

<div class="d-flex justify-content-between align-items-center mb-4">
<div>
<h5 class="mb-1">Users Overview</h5>
<small class="text-muted">Pharmacy Staff Accounts</small>
</div>

<div class="fs-1 text-primary">
<i class="bi bi-people-fill"></i>
</div>
</div>

<div class="row text-center g-3">

<div class="col-4">
<div class="border rounded-4 p-3">
<h6 class="text-muted mb-1">Users</h6>
<h3 class="text-success mb-0"><?= $totalUsers ?></h3>
</div>
</div>

<div class="col-4">
<div class="border rounded-4 p-3">
<h6 class="text-muted mb-1">Admins</h6>
<h3 class="text-dark mb-0"><?= $totalAdmins ?></h3>
</div>
</div>

<div class="col-4">
<div class="border rounded-4 p-3">
<h6 class="text-muted mb-1">Pharmacists</h6>
<h3 class="text-primary mb-0"><?= $totalPharmacists ?></h3>
</div>
</div>

</div>

</div>
</a>
</div>

</div>

<!-- ADD USER MODAL -->
<div class="modal fade" id="addUser">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header">
<h5>Add User</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<!-- ERROR -->
<?php if(!empty($error_user)): ?>
<div class="alert alert-danger">
<?= $error_user ?>
</div>
<?php endif; ?>

<form method="POST">

<input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
<input type="password" name="password" class="form-control mb-2" placeholder="Password" required>
<small class="text-muted d-block mb-3">
Password must contain: 8+ chars, uppercase, lowercase, number, special character
</small>
<div class="form-check">
<input type="radio" name="role" value="admin">
<label>Admin</label>
</div>

<div class="form-check mb-3">
<input type="radio" name="role" value="pharmacist" checked>
<label>Pharmacist</label>
</div>

<button name="add_user" class="btn btn-dark w-100">
Add User
</button>

</form>

</div>

</div>
</div>
</div>

<!-- AUTO REOPEN MODAL IF ERROR -->
<?php if(!empty($error_user)): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    var modal = new bootstrap.Modal(document.getElementById('addUser'));
    modal.show();
});
</script>
<?php endif; ?>

<script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
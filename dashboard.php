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

$today = date('Y-m-d');

/*  ADD MEDICINE */
if (isset($_POST['add_medicine'])) {

    $name   = trim($_POST['name']);
    $qty    = trim($_POST['qty']);
    $expiry = $_POST['expiry_date'];

    if (!empty($name) && !empty($qty) && !empty($expiry)) {

        $stmt = $conn->prepare("
            INSERT INTO medicines (name, quantity, expiry_date, hospital_id)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param("sisi", $name, $qty, $expiry, $hospital_id);
        $stmt->execute();

        $_SESSION['success'] = "Medicine added successfully";
    }

    header("Location: dashboard.php");
    exit();
}
$error_user = "";
/* ADD USER */
if (isset($_POST['add_user'])) {

    if ($role == "admin") {

        $new_username = trim($_POST['username']);
        $password     = trim($_POST['password']);
        $new_role     = $_POST['role'];

        /*  VALIDATION  */

        if (empty($new_username) || empty($password)) {
            $error_user = "Username and password are required";
        }

        elseif (strlen($new_username) < 5) {
            $error_user = "Username must be at least 5 characters";
        }

        elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $new_username)) {
            $error_user = "Username can only contain letters, numbers, and _";
        }

        elseif (strlen($password) < 8) {
            $error_user = "Password must be at least 8 characters";
        }

        elseif (!preg_match("/[A-Z]/", $password)) {
            $error_user = "Password must contain uppercase letter";
        }

        elseif (!preg_match("/[a-z]/", $password)) {
            $error_user = "Password must contain lowercase letter";
        }

        elseif (!preg_match("/[0-9]/", $password)) {
            $error_user = "Password must contain number";
        }

        elseif (!preg_match("/[\W]/", $password)) {
            $error_user = "Password must contain special character";
        }

        else {

            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->bind_param("s", $new_username);
            $check->execute();
            $check_result = $check->get_result();

            if ($check_result->num_rows > 0) {
                $error_user = "Username already exists";
            } else {

                $hashed = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("
                    INSERT INTO users (username, password, role, hospital_id)
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->bind_param("sssi", $new_username, $hashed, $new_role, $hospital_id);
                $stmt->execute();

                $_SESSION['success'] = "User created successfully";
            }
        }
    }
}

/*  UPDATE MEDICINE*/
if (isset($_POST['update_medicine'])) {

    $id   = $_POST['edit_id'];
    $name = $_POST['name'];
    $qty  = $_POST['qty'];
    $date = $_POST['expiry_date'];

    $stmt = $conn->prepare("
        UPDATE medicines
        SET name=?, quantity=?, expiry_date=?
        WHERE id=? AND hospital_id=?
    ");

    $stmt->bind_param("sisii", $name, $qty, $date, $id, $hospital_id);
    $stmt->execute();

    $_SESSION['success'] = "Medicine updated";

    header("Location: dashboard.php");
    exit();
}

/*  DELETE MEDICINE */
if (isset($_POST['delete_id'])) {

    $id = $_POST['delete_id'];

    $stmt = $conn->prepare("
        DELETE FROM medicines
        WHERE id=? AND hospital_id=?
    ");

    $stmt->bind_param("ii", $id, $hospital_id);
    $stmt->execute();

    $_SESSION['success'] = "Medicine deleted";

    header("Location: dashboard.php");
    exit();
}

/* SEARCH*/
$search = $_GET['search'] ?? '';

if (!empty($search)) {

    $stmt = $conn->prepare("
        SELECT * FROM medicines
        WHERE hospital_id = ?
        AND name LIKE ?
        ORDER BY expiry_date ASC
    ");

    $like = "%$search%";
    $stmt->bind_param("is", $hospital_id, $like);

} else {

    $stmt = $conn->prepare("
        SELECT * FROM medicines
        WHERE hospital_id = ?
        ORDER BY expiry_date ASC
    ");

    $stmt->bind_param("i", $hospital_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>

<link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="CSS/base.css">
<link rel="stylesheet" href="CSS/dash.css">

</head>

<body>

<!-- NAVBAR -->
<nav class="navbar bg-white shadow-sm">
  <div class="container">
    <span class="navbar-brand fw-bold">Pharmacy Inventory System</span>

    <div class="d-flex gap-3 align-items-center">
        <span>Welcome, <?= htmlspecialchars($username) ?></span>
         <a href="logout.php" class="btn btn-outline-danger btn-sm">
            Logout
        </a>
    </div>
  </div>
</nav>

<!-- ALERTS -->
<div class="container mt-3">
<?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php 
        echo $_SESSION['error']; 
        unset($_SESSION['error']); 
        ?>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['success']; 
        unset($_SESSION['success']); 
        ?>
    </div>
<?php endif; ?>

</div>

<div class="container  content">

<!-- ACTIONS -->
<div class="d-flex gap-2 mb-3">

<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMedicine">
+ Add Medicine
</button>

<?php if($role == 'admin'): ?>
<button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addUser">
+ Add User
</button>
<?php endif; ?>

</div>

<!-- SEARCH -->
<form method="GET" class="mb-3">
<input type="text"
name="search"
class="form-control"
placeholder="Search medicine..."
value="<?= htmlspecialchars($search) ?>">
</form>

<!-- TABLE -->
<div class="table-wrapper mb-3">
<div class="table-responsive">

<table class="table align-middle">
<thead>
<tr>
<th>Name</th>
<th>Qty</th>
<th>Expiry</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php while($row = $result->fetch_assoc()): ?>

<?php
$diff = (strtotime($row['expiry_date']) - strtotime($today)) / 86400;

if ($diff < 0) {
    $status = "Expired"; 
    $color = "danger";
} elseif ($diff <= 7) {
    $status = "Near Expiry";
     $color = "warning";
} else {
    $status = "Valid"; 
    $color = "success";
}
?>

<tr>
<td><?= htmlspecialchars($row['name']) ?></td>
<td><?= $row['quantity'] ?></td>
<td><?= $row['expiry_date'] ?></td>

<td>
<span class="badge bg-<?= $color ?>">
<?= $status ?>
</span>
</td>

<td>
<div class="action-icons">

<button class="icon-btn icon-edit"
data-bs-toggle="modal"
data-bs-target="#edit<?= $row['id'] ?>">
<i class="bi bi-pencil-square"></i>
</button>

<button class="icon-btn icon-delete"
data-bs-toggle="modal"
data-bs-target="#delete<?= $row['id'] ?>">
<i class="bi bi-x-lg"></i>
</button>

</div>
</td>
</tr>

<!-- EDIT MODAL -->
<div class="modal fade" id="edit<?= $row['id'] ?>">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header">
<h5>Edit Medicine</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<form method="POST">

<input type="hidden" name="edit_id" value="<?= $row['id'] ?>">

<input type="text" name="name" class="form-control mb-2"
value="<?= $row['name'] ?>">

<input type="number" name="qty" class="form-control mb-2"
value="<?= $row['quantity'] ?>">

<input type="date" name="expiry_date" class="form-control mb-2"
value="<?= $row['expiry_date'] ?>">

<button name="update_medicine" class="btn btn-primary w-100">
Update
</button>

</form>

</div>
</div>
</div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="delete<?= $row['id'] ?>">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header">
<h5 class="text-danger">Confirm Delete</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
Delete <b><?= $row['name'] ?></b> ?
</div>

<div class="modal-footer">

<button class="btn btn-secondary" data-bs-dismiss="modal">
Cancel
</button>

<form method="POST" style="display:inline;">
    <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">

    <button type="submit" class="btn btn-danger">
        Delete
    </button>
</form>

</div>

</div>
</div>
</div>

<?php endwhile; ?>

</tbody>
</table>

</div>
</div>

</div>

<!-- ADD MEDICINE MODAL -->
<div class="modal fade" id="addMedicine">
<div class="modal-dialog">
<div class="modal-content">
    <div class="modal-header">
<h5 class="modal-title">Add Medicine</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">

<form method="POST" onsubmit="return validateMedicineForm()">

<input type="text" id="medicineName" name="name" class="form-control mb-2" placeholder="Name" required>
<small id="nameError" class="text-danger"></small>

<input type="number" id="medicineQty" name="qty" class="form-control mb-2" placeholder="Qty" required>
<small id="qtyError" class="text-danger"></small>

<input type="date" id="medicineDate" name="expiry_date" class="form-control mb-2" required>
<small id="dateError" class="text-danger"></small>

<button name="add_medicine" class="btn btn-primary w-100">
Add
</button>

</form>

</div>
</div>
</div>
</div>


<!-- USER MODAL -->
<div class="modal fade" id="addUser">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header">
<h5 class="modal-title">Add User</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<?php if(!empty($error_user)): ?>
<div class="alert alert-danger">
    <?= $error_user ?>
</div>
<?php endif; ?>

<form method="POST">

<input type="text"
name="username"
class="form-control mb-2"
placeholder="Username"
required>

<input type="password"
name="password"
class="form-control mb-2"
placeholder="Password"
required>

<div class="form-check">
<input class="form-check-input" type="radio" name="role" value="admin" id="admin">
<label class="form-check-label" for="admin">Admin</label>
</div>

<div class="form-check mb-2">
<input class="form-check-input" type="radio" name="role" value="pharmacist" id="pharmacist" checked>
<label class="form-check-label" for="pharmacist">Pharmacist</label>
</div>

<button name="add_user" class="btn btn-dark w-100">
Add User
</button>

</form>

</div>
</div>
</div>
</div>

<script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script src="validation.js"></script>
<?php if(!empty($error_user)): ?>
<script>
var myModal = new bootstrap.Modal(document.getElementById('addUser'));
myModal.show();
</script>
<?php endif; ?>
</body>
</html> 

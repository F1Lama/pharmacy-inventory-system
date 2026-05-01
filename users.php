<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

$error_user = "";

/*  ADD USER */
if (isset($_POST['add_user'])) {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role     = $_POST['role'];

    if (empty($username) || empty($password)) {
        $error_user = "Username and password are required.";
    }

    elseif (strlen($username) < 5) {
        $error_user = "Username must be at least 5 characters.";
    }

    elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
        $error_user = "Username can contain only letters, numbers and _.";
    }

    elseif (
        strlen($password) < 8 ||
        !preg_match("/[A-Z]/", $password) ||
        !preg_match("/[a-z]/", $password) ||
        !preg_match("/[0-9]/", $password) ||
        !preg_match("/[\W]/", $password)
    ) {
        $error_user = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
    }

    else {

        $check = $conn->prepare("
            SELECT id FROM users
            WHERE username=? AND hospital_id=?
        ");

        $check->bind_param("si", $username, $hospital_id);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $error_user = "Username already exists.";
        } else {

            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO users (username, password, role, hospital_id)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param("sssi", $username, $hashed, $role, $hospital_id);
            $stmt->execute();

            $_SESSION['success'] = "User added successfully.";
            header("Location: users.php");
            exit();
        }
    }
}

/* UPDATE ROLE */
if (isset($_POST['update_role'])) {

    $id   = $_POST['id'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("
        UPDATE users
        SET role=?
        WHERE id=? AND hospital_id=?
    ");

    $stmt->bind_param("sii", $role, $id, $hospital_id);
    $stmt->execute();

    $_SESSION['success'] = "User updated.";
    header("Location: users.php");
    exit();
}

/* DELETE USER */
if (isset($_POST['delete_id'])) {

    $id = $_POST['delete_id'];

    $stmt = $conn->prepare("
        DELETE FROM users
        WHERE id=? AND hospital_id=?
    ");

    $stmt->bind_param("ii", $id, $hospital_id);
    $stmt->execute();

    $_SESSION['success'] = "User deleted.";
    header("Location: users.php");
    exit();
}

/* SEARCH */
$search = $_GET['search'] ?? '';

if (!empty($search)) {

    $stmt = $conn->prepare("
        SELECT * FROM users
        WHERE hospital_id = ?
        AND username LIKE ?
        ORDER BY id DESC
    ");

    $like = "%$search%";
    $stmt->bind_param("is", $hospital_id, $like);

} else {

    $stmt = $conn->prepare("
        SELECT * FROM users
        WHERE hospital_id = ?
        ORDER BY id DESC
    ");

    $stmt->bind_param("i", $hospital_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Users</title>

<link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="CSS/users.css">

</head>

<body>

<div class="container mt-5">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">

<h3>Users Management</h3>

<div class="d-flex gap-2">

<button class="btn btn-primary"
data-bs-toggle="modal"
data-bs-target="#addUser">
<i class="bi bi-plus-circle"></i> Add User
</button>

<a href="dashboard.php" class="btn btn-dark">Dashboard</a>
<a href="logout.php" class="btn btn-outline-danger">Logout</a>

</div>
</div>

<!-- SUCCESS -->
<?php if(isset($_SESSION['success'])): ?>
<div class="alert alert-success">
<?= $_SESSION['success']; unset($_SESSION['success']); ?>
</div>
<?php endif; ?>

<!-- SEARCH -->
<form method="GET" class="mb-3">
<input type="text" name="search" class="form-control"
placeholder="Search user..."
value="<?= htmlspecialchars($search) ?>">
</form>

<div class="box">

<table class="table table-hover align-middle">

<thead>
<tr>
<th>Username</th>
<th>Role</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php while($row = $result->fetch_assoc()): ?>

<tr>

<td>
<i class="bi bi-person-circle"></i>
<?= htmlspecialchars($row['username']) ?>
</td>

<td>
<?php if($row['role']=='admin'): ?>
<span class="badge bg-danger">Admin</span>
<?php else: ?>
<span class="badge bg-primary">Pharmacist</span>
<?php endif; ?>
</td>

<td>

<!-- EDIT -->
<button class="icon-btn"
data-bs-toggle="modal"
data-bs-target="#edit<?= $row['id'] ?>">
<i class="bi bi-pencil-square"></i>
</button>

<!-- DELETE -->
<button class="icon-btn text-danger"
data-bs-toggle="modal"
data-bs-target="#delete<?= $row['id'] ?>">
<i class="bi bi-x-lg"></i>
</button>

</td>

</tr>

<!-- EDIT MODAL -->
<div class="modal fade" id="edit<?= $row['id'] ?>">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header">
<h5>Edit User</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<form method="POST">

<input type="hidden" name="id" value="<?= $row['id'] ?>">

<select name="role" class="form-control mb-3">
<option value="admin" <?= $row['role']=='admin'?'selected':'' ?>>Admin</option>
<option value="pharmacist" <?= $row['role']=='pharmacist'?'selected':'' ?>>Pharmacist</option>
</select>

<button name="update_role" class="btn btn-primary w-100">
Update Role
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
Are you sure you want to delete user:
<br>
<b><?= htmlspecialchars($row['username']) ?></b> ?
</div>

<div class="modal-footer">

<button class="btn btn-secondary" data-bs-dismiss="modal">
Cancel
</button>

<form method="POST">
<input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
<button class="btn btn-danger">
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

<!-- ADD USER MODAL -->
<div class="modal fade" id="addUser">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header">
<h5>Add User</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<?php if(!empty($error_user)): ?>
<div class="alert alert-danger">
<?= $error_user ?>
</div>
<?php endif; ?>

<form method="POST">

<input type="text" name="username" class="form-control mb-2" placeholder="Username">
<input type="password" name="password" class="form-control mb-2" placeholder="Password">

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

<!-- REOPEN MODAL -->
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
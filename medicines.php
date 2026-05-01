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

/* ERRORS */
$error_name = "";
$error_qty = "";
$error_date = "";

/* ADD MEDICINE  */

if (isset($_POST['add_medicine'])) {

    $name   = trim($_POST['name'] ?? '');
    $qty    = $_POST['qty'] ?? '';
    $expiry = $_POST['expiry_date'] ?? '';

    $valid = true;

    // NAME
    if ($name === '') {
        $error_name = "Medicine name is required";
        $valid = false;
    } elseif (is_numeric($name)) {
        $error_name = "Name cannot be only numbers";
        $valid = false;
    }

    // QTY
    if ($qty === '' || !is_numeric($qty) || $qty <= 0) {
        $error_qty = "Quantity must be a number greater than 0";
        $valid = false;
    }

    // DATE 
    $dateObj = DateTime::createFromFormat('Y-m-d', $expiry);
    $dateErrors = DateTime::getLastErrors();

    $today = new DateTime();

    if ($expiry === '') {
        $error_date = "Expiry date is required";
        $valid = false;

    } elseif (
        !$dateObj ||
        $dateErrors['warning_count'] > 0 ||
        $dateErrors['error_count'] > 0 ||
        $dateObj->format('Y-m-d') !== $expiry
    ) {
        $error_date = "Please enter a valid date (YYYY-MM-DD)";
        $valid = false;

    } elseif ($dateObj < $today) {
        $error_date = "Expiry date must be in the future";
        $valid = false;
    }

    if (!$valid) {

    $_SESSION['open_add_modal'] = true;

    $_SESSION['form_error'] = [
        'name'  => $error_name,
        'qty'   => $error_qty,
        'date'  => $error_date
    ];

    header("Location: medicines.php");
    exit();
}
    $qty = (int)$qty;

    $stmt = $conn->prepare("
        INSERT INTO medicines (name, quantity, expiry_date, hospital_id)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param("sisi", $name, $qty, $expiry, $hospital_id);
    $stmt->execute();

    $_SESSION['success'] = "Medicine added successfully";

    header("Location: medicines.php");
    exit();
}

/*  UPDATE MEDICINE  */
if (isset($_POST['update_medicine'])) {

    $id   = $_POST['edit_id'] ?? 0;
    $name = trim($_POST['name'] ?? '');
    $qty  = $_POST['qty'] ?? '';
    $date = $_POST['expiry_date'] ?? '';

    $valid = true;

    // NAME ERROR
    if ($name === '') {
        $_SESSION['edit_name_error'][$id] = "Name is required";
        $valid = false;
    } elseif (is_numeric($name)) {
        $_SESSION['edit_name_error'][$id] = "Name cannot be numbers only";
        $valid = false;
    }

    // QTY ERROR
    if ($qty === '') {
        $_SESSION['edit_qty_error'][$id] = "Quantity is required";
        $valid = false;
    } elseif (!is_numeric($qty) || $qty <= 0) {
        $_SESSION['edit_qty_error'][$id] = "Quantity must be greater than 0";
        $valid = false;
    }

    // DATE ERROR
    if ($date === '') {
        $_SESSION['edit_date_error'][$id] = "Expiry date is required";
        $valid = false;
    }

    // REOPEN THE MODEL 
    if (!$valid) {
        $_SESSION['open_edit_modal'] = $id;
        header("Location: medicines.php");
        exit();
    }

    $qty = (int)$qty;

    $stmt = $conn->prepare("
        UPDATE medicines
        SET name=?, quantity=?, expiry_date=?
        WHERE id=? AND hospital_id=?
    ");

    $stmt->bind_param("sisii", $name, $qty, $date, $id, $hospital_id);
    $stmt->execute();

    $_SESSION['success'] = "Medicine updated";

    header("Location: medicines.php");
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

    header("Location: medicines.php");
    exit();
}

/* SEARCH */
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
<title>Medicines</title>

<link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="CSS/medicines.css">


</head>

<body>

<div class="container mt-5">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">

<h3>Medicines Management</h3>

<div class="d-flex gap-2">

<button class="btn btn-primary"
data-bs-toggle="modal"
data-bs-target="#addMedicine">
<i class="bi bi-plus-circle"></i> Add Medicine
</button>

<a href="dashboard.php" class="btn btn-dark">
<i class="bi bi-arrow-right"></i> Dashboard
</a>

<a href="logout.php" class="btn btn-outline-danger">
<i class="bi bi-box-arrow-right"></i> Logout
</a>

</div>
</div>

<!-- ALERTS -->
<?php if(isset($_SESSION['success'])): ?>
<div class="alert alert-success">
<?= $_SESSION['success']; unset($_SESSION['success']); ?>
</div>
<?php endif; ?>


<!-- SEARCH + TABLE -->
<div class="box">

<form method="GET" class="mb-3">
<input type="text"
name="search"
class="form-control"
placeholder="Search medicine..."
value="<?= htmlspecialchars($search) ?>">
</form>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead class="table-light">
<tr>
<th>Name</th>
<th>Quantity</th>
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

</td>
</tr>

<!-- EDIT MEDICINE MODAL -->
<div class="modal fade" id="edit<?= $row['id'] ?>">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header">
<h5>Edit Medicine</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<?php if(isset($_SESSION['edit_error'])): ?>
<small class="text-danger">
<?= $_SESSION['edit_error']; unset($_SESSION['edit_error']); ?>
</small>
<?php endif; ?>
<form method="POST" onsubmit="return validateEditMedicineForm(<?= $row['id'] ?>)">
    
<input type="hidden" name="edit_id" value="<?= $row['id'] ?>">

<input type="text"
id="editName<?= $row['id'] ?>"
name="name"
class="form-control mb-1"
value="<?= $row['name'] ?>">

<small class="text-danger">
<?= $_SESSION['edit_name_error'][$row['id']] ?? '' ?>
</small>

<input type="number"
id="editQty<?= $row['id'] ?>"
name="qty"
class="form-control mb-1 mt-2"
value="<?= $row['quantity'] ?>">

<small class="text-danger">
<?= $_SESSION['edit_qty_error'][$row['id']] ?? '' ?>
</small>

<input type="date"
id="editDate<?= $row['id'] ?>"
name="expiry_date"
class="form-control mb-1 mt-2"
value="<?= $row['expiry_date'] ?>">

<small class="text-danger">
<?= $_SESSION['edit_date_error'][$row['id']] ?? '' ?>
</small>


<button type="submit" name="update_medicine" class="btn btn-primary w-100">
Update
</button>

</form>

</div>
</div>
</div>
</div>

<!-- DELETE MEDICINE MODAL -->
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

<form method="POST">
<input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
<button class="btn btn-danger">Delete</button>
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
<h5>Add Medicine</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<form method="POST" onsubmit="return validateMedicineForm()">

<!-- NAME -->
<input type="text"
id="medicineName"
name="name"
class="form-control mb-1"
placeholder="Name"
value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">

<small class="text-danger">
    <?= $_SESSION['form_error']['name'] ?? '' ?>
</small>


<!-- QUANTITY -->
<input type="number"
id="medicineQty"
name="qty"
class="form-control mb-1 mt-2"
placeholder="Quantity"
value="<?= htmlspecialchars($_POST['qty'] ?? '') ?>">

<small class="text-danger">
    <?= $_SESSION['form_error']['qty'] ?? '' ?>
</small>


<!-- DATE -->
<input type="date"
id="medicineDate"
name="expiry_date"
class="form-control mb-1 mt-2"
value="<?= htmlspecialchars($_POST['expiry_date'] ?? '') ?>">

<small class="text-danger">
    <?= $_SESSION['form_error']['date'] ?? '' ?>
</small>


<button name="add_medicine" class="btn btn-primary w-100 mt-2">
Add Medicine
</button>

</form>

</div>
</div>
</div>
</div>
<script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>

<script src="validation.js"></script>

<!-- reopen the model  -->
<?php if(isset($_SESSION['open_edit_modal'])): ?>
<script>
window.addEventListener('load', function () {
    let id = "<?= $_SESSION['open_edit_modal'] ?>";
    let modal = new bootstrap.Modal(document.getElementById('edit' + id));
    modal.show();
});
</script>
<?php unset($_SESSION['open_edit_modal']); endif; ?>


<?php if(isset($_SESSION['open_add_modal'])): ?>
<script>
window.addEventListener('load', function () {
    let modal = new bootstrap.Modal(document.getElementById('addMedicine'));
    modal.show();
});
</script>
<?php unset($_SESSION['open_add_modal']); endif; ?>


<?php
unset($_SESSION['edit_name_error']);
unset($_SESSION['edit_qty_error']);
unset($_SESSION['edit_date_error']);
?>

<?php
unset($_SESSION['error_name']);
unset($_SESSION['error_qty']);
unset($_SESSION['error_date']);
?>
</body>
</html>
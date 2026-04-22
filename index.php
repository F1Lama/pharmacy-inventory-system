<?php
session_start();
include "db.php";

$message = "";
$type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

    $_SESSION['user_id']     = $user['id'];
    $_SESSION['username']    = $user['username'];
    $_SESSION['role']        = $user['role'];
    $_SESSION['hospital_id'] = $user['hospital_id'];

    if ($user['role'] == 'owner') {
        header("Location: owner.php");
    } else {
        header("Location: dashboard.php");
    }

    exit();
} else {
            $message = "Wrong password!";
            $type = "danger";
        }

    } else {
        $message = "User not found!";
        $type = "danger";
    }

    $stmt->close();
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>
    <link rel="stylesheet" href="bootstrap-5.3.8-dist\css\bootstrap.min.css" />
    <link rel="stylesheet" href="CSS/base.css" />
    <link rel="stylesheet" href="CSS/login.css" />
  </head>
  <body>
    <div class="container-fluid vh-100">
      <div class="row h-100 justify-content-center align-items-center">
        <div class="col-md-4 col-sm-10 d-flex justify-content-center">
          <div class="login-card w-100">
            <h2 class="mb-4 text-center">Login</h2>

            <?php if ($message != ""): ?>
            <div class="alert alert-<?php echo $type; ?> text-center">
              <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="index.php">
              <div class="mb-3">
                <label class="form-label">username</label>
                <input
                  name="username"
                  type="text"
                  class="form-control"
                  required
                />
              </div>

              <div class="mb-3">
                <label class="form-label">Password</label>
                <input
                  name="password"
                  type="password"
                  class="form-control"
                  required
                />
              </div>

              <button type="submit" class="btn btn-custom w-100">Login</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <script src="bootstrap-5.3.8-dist/js/bootstrap.min.js"></script>
  </body>
</html>

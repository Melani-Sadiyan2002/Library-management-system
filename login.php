<?php
include('dbcon.php');

if (isset($_POST['login'])) {
    session_start();

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $username_esc = mysqli_real_escape_string($con, $username);
    $password_esc = mysqli_real_escape_string($con, $password);

    $query = "SELECT * FROM students WHERE username='$username_esc' AND password='$password_esc' AND status='active'";
    $result = mysqli_query($con, $query) or die(mysqli_error($con));
    $num_row = mysqli_num_rows($result);
    $row = mysqli_fetch_array($result);

    if ($num_row > 0) {
        $_SESSION['id'] = $row['student_id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['firstname'] = $row['firstname'];
        $_SESSION['lastname'] = $row['lastname'];
        header('location:dashboard.php');
        exit();
    } else {
        header('location:access_denied.php');
        exit();
    }
}
?>
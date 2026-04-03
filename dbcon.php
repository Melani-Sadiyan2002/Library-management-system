<?php
$con = mysqli_connect("localhost", "root", "Melani@21", "lms_db");

if (!$con) {
	die("Connection failed: " . mysqli_connect_error());
}
?>
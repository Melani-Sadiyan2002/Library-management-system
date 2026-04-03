	<?php
	if (isset($_POST['submit'])){
	session_start();
	$username = $_POST['username'];
	$password = $_POST['password'];
	$query = "SELECT * FROM students WHERE username='$username' AND password='$password'";
	$result = mysql_query($query)or die(mysql_error());
	$num_row = mysql_num_rows($result);
		$row=mysql_fetch_array($result);
		if( $num_row > 0 ) {
			header('location:dashboard.php');
	$_SESSION['id']=$row['student_id'];
		$_SESSION['username']=$row['username'];
		$_SESSION['firstname']=$row['firstname'];
		$_SESSION['lastname']=$row['lastname'];
		}
		else{ ?>
	<div class="alert alert-danger">Access Denied</div>		
	<?php
	}}
	?>
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
include('dbcon.php');
?>

<?php include('header.php'); ?>

<style>
body {
	margin: 0;
	background: #111;
	font-family: "Trebuchet MS", "Segoe UI", Arial, sans-serif;
}
.top-nav {
	height: 58px;
	display: flex;
	align-items: center;
	justify-content: flex-start;
	padding: 0 22px;
	background: linear-gradient(180deg, #0a224e 0%, #12336b 100%);
	box-shadow: 0 4px 12px rgba(10, 34, 78, 0.18);
	position: sticky;
	top: 0;
	z-index: 10;
}
.top-nav .back-btn {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	padding: 10px 16px;
	border-radius: 999px;
	background: rgba(255, 255, 255, 0.12);
	color: #fff;
	font-size: 14px;
	font-weight: 700;
	text-decoration: none;
	transition: background 0.2s ease, transform 0.2s ease;
}
.top-nav .back-btn:hover {
	background: rgba(255, 255, 255, 0.2);
	transform: translateX(-2px);
}
.login-shell {
	min-height: 100vh;
	display: flex;
	background: linear-gradient(180deg, #eff5ff 0%, #eef4ff 100%);
}
.login-hero {
	flex: 1.15;
	background: linear-gradient(180deg, rgba(10, 34, 78, 0.7), rgba(44, 111, 176, 0.6)), url('../images5/new2.jpg') center/cover no-repeat;
	color: #fff;
	display: flex;
	align-items: center;
	padding: 60px;
	box-sizing: border-box;
	position: relative;
	overflow: hidden;
}
.login-hero::after {
	content: "";
	position: absolute;
	right: -90px;
	top: -70px;
	width: 260px;
	height: 260px;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.12);
}
.hero-copy {
	position: relative;
	z-index: 1;
	max-width: 560px;
}
.hero-brand {
	display: flex;
	align-items: center;
	gap: 14px;
	margin-bottom: 28px;
}
.hero-brand img {
	width: 56px;
	height: 56px;
	object-fit: contain;
}
.hero-brand span {
	font-size: 26px;
	font-weight: 700;
	letter-spacing: 0.03em;
}
.hero-copy h1 {
	font-size: 54px;
	line-height: 1.05;
	margin: 0 0 12px;
	font-weight: 700;
}
.hero-copy p {
	font-size: 18px;
	margin: 0;
	opacity: 0.95;
}
.login-panel {
	flex: 0.85;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 40px 24px;
	box-sizing: border-box;
}
.login-card {
	width: 100%;
	max-width: 360px;
	background: #fff;
	border-radius: 18px;
	padding: 28px 22px 26px;
	box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
}
.login-card h2 {
	margin: 0 0 22px;
	font-size: 24px;
	font-weight: 700;
	color: #172033;
}
.login-card label {
	display: block;
	font-size: 14px;
	margin-bottom: 6px;
	color: #243045;
}
.login-card input {
	width: 100%;
	box-sizing: border-box;
	padding: 10px 12px;
	border: 1px solid #d7dde7;
	border-radius: 6px;
	margin-bottom: 16px;
	background: #fff;
}
.login-card .login-btn {
	width: 100%;
	background: #2d5be3;
	border: none;
	color: #fff;
	padding: 11px 14px;
	border-radius: 6px;
	font-size: 16px;
	font-weight: 700;
	box-shadow: 0 4px 10px rgba(45, 91, 227, 0.28);
}
.login-card .secondary-link {
	text-align: center;
	margin-top: 14px;
	font-size: 13px;
}
.login-card .secondary-link a {
	color: #2d5be3;
	font-weight: 700;
}
.login-card .forgot-link {
	text-align: right;
	margin: -6px 0 14px;
	font-size: 12px;
}
.login-card .forgot-link a {
	color: #2d5be3;
}
@media (max-width: 900px) {
	.login-shell {
		flex-direction: column;
	}
	.login-hero {
		padding: 40px 24px;
	}
	.hero-copy h1 {
		font-size: 40px;
	}
}
</style>

<div class="top-nav">
	<a class="back-btn" href="../index.php">&#8592; Back</a>
</div>

<div class="login-shell">
	<div class="login-hero">
		<div class="hero-copy">
			<div class="hero-brand">
				<img src="../images5/logo.png" alt="KDU Library">
				<span>Kotelawala Defence University</span>
			</div>
			<h1>Welcome Back to LMS</h1>
			<p>Access books easily. Borrow and return anytime.</p>
		</div>
	</div>

	<div class="login-panel">
		<div class="login-card">
			<h2>Login</h2>
			<?php
			if (isset($_POST['submit'])) {
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
					echo "<script>window.location='dashboard.php';</script>";
					exit();
				} else {
					echo '<div class="alert alert-danger">Access Denied</div>';
				}
			}
			?>
			<form method="POST">
				<label for="username">Username</label>
				<input type="text" name="username" id="username" placeholder="Enter your Username" required>

				<label for="password">Password</label>
				<input type="password" name="password" id="password" placeholder="Enter your password" required>

				<div class="forgot-link"><a href="../access_denied.php">Forgot Password ?</a></div>

				<button id="login" name="submit" type="submit" class="login-btn">Login</button>

				<div class="secondary-link">Don't have an account? <a href="../signup.php">Register</a></div>
			</form>
		</div>
	</div>
</div>

<?php include('footer.php'); ?>
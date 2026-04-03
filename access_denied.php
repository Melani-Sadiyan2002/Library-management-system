<?php include('header.php'); ?>
<?php include('navbar.php'); ?>
    <div class="container">
		<div class="margin-top">
			<div class="row">
					<?php include('head.php'); ?>
				<div class="span2">
					<?php include('sidebar.php'); ?>
				</div>
				<div class="span10">
				<div class="alert alert-danger">Please Check Your Username and Password.</div>
					<form class="form-horizontal" method="post" action="login.php">
				<div class="control-group">
					<label class="control-label" for="inputEmail">Username</label>
					<div class="controls">
					<input type="text" id="inputEmail" name="username" placeholder="Username" required>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="inputPassword">Password</label>
					<div class="controls">
					<input type="password" id="inputPassword" name="password" placeholder="Password" required>
				</div>
				</div>
				<div class="control-group">
					<div class="controls">
					<button type="submit" name="login" class="btn btn-success"><i class="icon-signin icon-large"></i>&nbsp;Sign in</button>
					<p style="margin-top:8px;">
						Don't have an account? <a href="signup.php">Register here</a>
					</p>
				</div>
				</div>
			</form>
				</div>
			</div>
		</div>
    </div>
<?php include('footer.php') ?>

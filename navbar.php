		<?php include('tooltip.php'); ?>
		<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
		<style>
		.public-nav {
			background: rgba(10, 34, 78, 0.96);
			backdrop-filter: blur(4px);
			border-bottom: 1px solid rgba(255, 255, 255, 0.12);
		}
		.public-nav .navbar-inner {
			background: transparent;
			border: 0;
			box-shadow: none;
			height: auto;
		}
		.public-nav .brand {
			color: #ffffff;
			font-weight: 700;
			letter-spacing: 0.01em;
			padding: 8px 10px;
			font-size: 16px;
			margin-left: 0;
			float: right;
			display: none;
		}
		.public-nav .nav {
			float: left;
		}
		.public-nav .nav > li > a {
			color: rgba(255, 255, 255, 0.88);
			font-weight: 600;
			padding: 7px 10px;
			margin: 5px 3px;
			font-size: 13px;
			border-radius: 999px;
			text-shadow: none;
		}
		.public-nav .nav > li > a:hover {
			background: rgba(255, 255, 255, 0.14);
			color: #ffffff;
		}
		.public-nav .nav > li.active > a,
		.public-nav .nav > li.active > a:hover {
			background: #ffffff;
			color: #0a224e;
		}
		.public-nav .nav > li.nav-cta > a {
			background: #89b4ff;
			color: #0a224e;
			font-weight: 700;
		}
		.public-nav .nav > li.nav-cta > a:hover {
			background: #a8c6ff;
			color: #0a224e;
		}
		.public-nav .btn.btn-navbar {
			margin-top: 6px;
			background: rgba(255, 255, 255, 0.16);
			border: 0;
		}
		.public-nav .btn.btn-navbar .icon-bar {
			background: #ffffff;
		}
		@media (max-width: 979px) {
			.public-nav .brand {
				float: left;
			}
			.public-nav .nav {
				float: none;
			}
			.public-nav .nav > li > a {
				margin: 3px 0;
				padding: 8px 12px;
			}
		}
		</style>

		<div class="navbar navbar-fixed-top public-nav">
			<div class="navbar-inner">
				<div class="container">
					<a class="brand" href="index.php">KDU Library</a>
					<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</a>
					<div class="nav-collapse collapse">
						<ul class="nav">
						<?php if ($current_page === 'signup.php') { ?>
							<li><a rel="tooltip" data-placement="bottom" title="Back" id="back" href="index.php"><i class="icon-arrow-left"></i>&nbsp;Back</a></li>
						<?php } else { ?>
							<li class="<?php echo ($current_page === 'index.php') ? 'active' : ''; ?>"><a rel="tooltip" data-placement="bottom" title="Home" id="home" href="index.php">Home</a></li>
							<li class="<?php echo ($current_page === 'about.php') ? 'active' : ''; ?>"><a rel="tooltip" data-placement="bottom" title="About Us" id="about" href="about.php">About Us</a></li>
							<li class="<?php echo ($current_page === 'photos.php') ? 'active' : ''; ?>"><a rel="tooltip" data-placement="bottom" title="Gallery" id="gallery" href="photos.php">Gallery</a></li>
							<li class="nav-cta"><a rel="tooltip" data-placement="bottom" title="Click Here to Login" id="login" href="student">Login</a></li>
						<?php } ?>
						</ul>
					</div>
				</div>
			</div>
		</div>




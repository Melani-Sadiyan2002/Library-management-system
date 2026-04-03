		<div class="span12">
			<style>
			.front-hero {
				position: relative;
				border-radius: 14px;
				overflow: hidden;
				min-height: 360px;
				background: linear-gradient(135deg, rgba(16, 44, 94, 0.9), rgba(28, 88, 176, 0.7)), url('images5/books1.jpg') center/cover no-repeat;
				box-shadow: 0 18px 36px rgba(16, 24, 40, 0.2);
				margin-bottom: 18px;
			}
			.hero-content {
				position: relative;
				z-index: 1;
				padding: 90px 34px 34px;
				color: #fff;
			}
			.hero-brand {
				display: flex;
				align-items: center;
				gap: 10px;
				margin-bottom: 14px;
			}
			.hero-brand img {
				width: 54px;
				height: 54px;
				object-fit: contain;
			}
			.hero-title {
				font-size: 42px;
				line-height: 1.08;
				font-weight: 700;
				margin: 0 0 8px;
			}
			.hero-subtitle {
				font-size: 16px;
				margin: 0;
				opacity: 0.95;
			}
			.hero-date {
				position: absolute;
				right: 24px;
				bottom: 10px;
				background: rgba(255, 255, 255, 0.82);
				color: #163465;
				padding: 10px 16px;
				border-radius: 999px;
				font-size: 16px;
				font-weight: 700;
				box-shadow: 0 8px 18px rgba(10, 30, 70, 0.22);
			}
			</style>
			<div class="front-hero">
				<div class="hero-content">
					<div class="hero-brand">
						<img src="images5/logo.png" alt="KDU Library Logo">
					</div>
					<h1 class="hero-title">Kotelawala Defence University Library</h1>
					<p class="hero-subtitle">Find books, manage borrowing, and access your library services quickly.</p>
				</div>
				<div class="hero-date">
					<i class="icon-calendar icon-small"></i>
					<?php
					echo date('l, F d, Y');
					?>
				</div>
			</div>
		</div>
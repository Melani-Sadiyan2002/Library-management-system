
<?php include('header.php'); ?>
<?php include('navbar.php'); ?>
<style>
body {
	background: linear-gradient(180deg, #07142b 0%, #0d2244 100%);
	color: #e6eeff;
}
.about-wrap {
	max-width: 1120px;
	margin: 0 auto 24px;
	padding: 0 12px;
}
.about-panel {
	background: rgba(10, 24, 49, 0.92);
	border: 1px solid rgba(148, 163, 184, 0.22);
	border-radius: 16px;
	padding: 24px;
	box-shadow: 0 18px 36px rgba(2, 8, 23, 0.35);
}
.about-title {
	margin: 0;
	font-size: 42px;
	line-height: 1.1;
	color: #ffffff;
	text-align: center;
}
.about-intro {
	margin: 14px 0 0;
	font-size: 18px;
	line-height: 1.8;
	text-align: center;
	color: #d9e7ff;
}
.about-grid {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 16px;
	margin-top: 22px;
}
.about-card {
	background: rgba(19, 42, 80, 0.72);
	border: 1px solid rgba(148, 163, 184, 0.24);
	border-radius: 12px;
	padding: 16px;
}
.about-card h4 {
	margin: 0 0 10px;
	font-size: 22px;
	color: #ffffff;
}
.about-card p,
.about-card li,
.about-hours td,
.about-hours th {
	color: #dbe7ff;
	line-height: 1.7;
}
.about-card ul {
	margin: 0;
	padding-left: 18px;
}
.about-hours {
	width: 100%;
	border-collapse: collapse;
	margin-top: 6px;
}
.about-hours th,
.about-hours td {
	padding: 10px 12px;
	border: 1px solid rgba(148, 163, 184, 0.25);
}
.footer {
	clear: both;
}
@media (max-width: 900px) {
	.about-title {
		font-size: 34px;
	}
	.about-intro {
		font-size: 16px;
	}
	.about-grid {
		grid-template-columns: 1fr;
	}
}
</style>
    <div class="container">
		<div class="margin-top">
			<div class="row">	
			<?php include('head.php'); ?>
				
				<div class="about-wrap">
					<div class="about-panel">
						<h2 class="about-title">About Kotelawala Defence University Library</h2>
						<p class="about-intro">KDU Library supports teaching, learning, and research with reliable access to books, reference resources, digital materials, and modern borrowing services through the library management system.</p>

						<div class="about-grid">
							<div class="about-card">
								<h4>Mission</h4>
								<p>To build an informed academic community by delivering dependable information services, promoting responsible resource use, and encouraging lifelong learning.</p>
							</div>
							<div class="about-card">
								<h4>Library Collections</h4>
								<ul>
									<li>Academic textbooks and references</li>
									<li>Journals, reports, and research resources</li>
									<li>Past papers and supplementary materials</li>
									<li>Institutional and digital publications</li>
								</ul>
							</div>
							<div class="about-card">
								<h4>Services</h4>
								<ul>
									<li>Search and reserve books online</li>
									<li>Barcode-based borrowing and returns</li>
									<li>Overdue and fine tracking</li>
									<li>Catalog and reference support</li>
								</ul>
							</div>
							<div class="about-card">
								<h4>Library Hours</h4>
								<table class="about-hours">
									<thead>
										<tr>
											<th>Day</th>
											<th>Open Hours</th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td>Monday to Friday</td>
											<td>8:00 a.m. to 12:30 a.m.</td>
										</tr>
										<tr>
											<td>Saturday and Sunday</td>
											<td>8:00 a.m. to 8:00 p.m.</td>
										</tr>
									</tbody>
								</table>
							</div>
							<div class="about-card" style="grid-column: 1 / -1;">
								<h4>Borrowing Guidelines</h4>
								<ul>
									<li>Borrow period is 14 days from the issue date</li>
									<li>Return books on or before due date to avoid fines</li>
									<li>Reserved books should be collected within the notified period</li>
									<li>Report lost or damaged books to library staff immediately</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
					</div>
			
		</div>
    </div>
<?php include('footer.php') ?>
<!-- Made by Vinit Shahdeo -->
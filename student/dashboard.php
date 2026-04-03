<?php include('header.php'); ?>
<?php include('session.php'); ?>
<?php include('dbcon.php'); ?>
<?php
$account_name = 'Account';
$account_query = mysqli_query($con, "SELECT username, firstname, lastname FROM students WHERE student_id='" . (int)$session_id . "' LIMIT 1") or die(mysqli_error($con));
$account_row = mysqli_fetch_assoc($account_query);
if ($account_row) {
	$account_name = trim($account_row['username']);
	if ($account_name === '') {
		$account_name = trim($account_row['firstname'] . ' ' . $account_row['lastname']);
	}
}
if (isset($_SESSION['username']) && trim($_SESSION['username']) !== '') {
	$account_name = trim($_SESSION['username']);
}

$show_borrow_confirmed = isset($_SESSION['borrow_confirmed']) && (string)$_SESSION['borrow_confirmed'] === '1';
unset($_SESSION['borrow_confirmed']);

$session_student_id = (int) $session_id;

function lms_dashboard_parse_date($value)
{
	$value = trim((string) $value);
	if ($value === '') {
		return null;
	}

	$formats = array('d/m/Y', 'Y-m-d H:i:s', 'Y-m-d');
	foreach ($formats as $format) {
		$date = DateTime::createFromFormat($format, $value);
		if ($date instanceof DateTime) {
			return $date;
		}
	}

	try {
		return new DateTime($value);
	} catch (Exception $exception) {
		return null;
	}
}

$overdue_books = 0;
$dashboard_now = new DateTime('now');
$overdue_query = mysqli_query($con, "
	SELECT b.due_date
	FROM borrowdetails bd
	INNER JOIN borrow b ON bd.borrow_id = b.borrow_id
	WHERE bd.borrow_status = 'pending' AND b.student_id = '$session_student_id'
") or die(mysqli_error($con));
while ($overdue_row = mysqli_fetch_assoc($overdue_query)) {
	$due_date = lms_dashboard_parse_date($overdue_row['due_date']);
	if ($due_date && $dashboard_now->getTimestamp() > $due_date->getTimestamp()) {
		$overdue_books++;
	}
}

$books_count_query = mysqli_query($con, "SELECT COUNT(*) AS total_books, COALESCE(SUM(book_copies), 0) AS total_copies FROM book WHERE status != 'Archive'") or die(mysqli_error($con));
$books_count_row = mysqli_fetch_assoc($books_count_query);
$total_books = (int) $books_count_row['total_books'];
$total_copies = (int) $books_count_row['total_copies'];

$borrowed_count_query = mysqli_query($con, "
	SELECT COUNT(*) AS total_borrowed
	FROM borrowdetails bd
	INNER JOIN borrow b ON bd.borrow_id = b.borrow_id
	WHERE bd.borrow_status = 'pending' AND b.student_id = '$session_student_id'
") or die(mysqli_error($con));
$borrowed_count_row = mysqli_fetch_assoc($borrowed_count_query);
$borrowed_books = (int) $borrowed_count_row['total_borrowed'];

$returned_count_query = mysqli_query($con, "
	SELECT COUNT(*) AS total_returned
	FROM borrowdetails bd
	INNER JOIN borrow b ON bd.borrow_id = b.borrow_id
	WHERE bd.borrow_status = 'returned' AND b.student_id = '$session_student_id'
") or die(mysqli_error($con));
$returned_count_row = mysqli_fetch_assoc($returned_count_query);
$returned_books = (int) $returned_count_row['total_returned'];

$available_books = max(0, $total_copies);

$borrowed_books_query = mysqli_query($con, "
	SELECT
		b.borrow_id,
		b.date_borrow,
		b.due_date,
		s.student_no,
		s.firstname,
		s.lastname,
		bd.borrow_details_id,
		bd.borrow_status,
		bd.date_return,
		bk.book_title
	FROM borrow b
	INNER JOIN students s ON b.student_id = s.student_id
	INNER JOIN borrowdetails bd ON b.borrow_id = bd.borrow_id
	INNER JOIN book bk ON bd.book_id = bk.book_id
	WHERE bd.borrow_status = 'pending' AND b.student_id = '$session_student_id'
	ORDER BY b.borrow_id DESC
	LIMIT 8
") or die(mysqli_error($con));
?>
<style>
body {
	background: #eff3f8;
	font-family: "Trebuchet MS", "Segoe UI", Arial, sans-serif;
}
.dashboard-shell {
	width: 96%;
	max-width: 1600px;
	margin-top: 20px;
}
.dashboard-panel {
	display: flex;
	min-height: 720px;
	background: #f7f9fd;
	border: 1px solid #d8e0ea;
	box-shadow: 0 18px 40px rgba(22, 31, 60, 0.08);
	overflow: hidden;
}
.dashboard-sidebar {
	width: 300px;
	background: linear-gradient(180deg, #0f1728 0%, #16243d 100%);
	color: #fff;
	padding: 28px 20px 24px;
	box-sizing: border-box;
	display: flex;
	flex-direction: column;
}
.dashboard-brand {
	font-size: 30px;
	line-height: 1;
	font-weight: 700;
	letter-spacing: 0.18em;
	text-align: center;
	padding-bottom: 22px;
	margin-bottom: 22px;
	border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}
.dashboard-nav {
	list-style: none;
	margin: 0;
	padding: 0;
}
.dashboard-nav li {
	margin-bottom: 10px;
}
.dashboard-nav a {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 12px 14px;
	border-radius: 12px;
	text-decoration: none;
	color: rgba(255, 255, 255, 0.86);
	background: rgba(255, 255, 255, 0.06);
	border: 1px solid transparent;
	transition: all 0.2s ease;
}
.dashboard-nav a:hover,
.dashboard-nav a.active {
	background: #ffffff;
	color: #13233d;
	border-color: #ffffff;
}
.sidebar-footer {
	margin-top: auto;
	padding-top: 20px;
}
.sidebar-footer .logout-btn {
	display: block;
	text-align: center;
	padding: 12px 14px;
	border-radius: 12px;
	background: #ef5350;
	color: #fff;
	text-decoration: none;
	font-weight: 700;
}
.dashboard-content {
	flex: 1;
	padding: 28px;
	position: relative;
	box-sizing: border-box;
	background:
		radial-gradient(circle at top right, rgba(63, 111, 217, 0.12), transparent 28%),
		radial-gradient(circle at 12% 8%, rgba(20, 184, 166, 0.10), transparent 26%),
		#f7f9fd;
}
.borrow-success-overlay {
	position: fixed;
	inset: 0;
	background: rgba(11, 22, 41, 0.45);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 9999;
	padding: 18px;
	box-sizing: border-box;
}
.borrow-success-card {
	width: min(680px, 94vw);
	background: linear-gradient(180deg, #ffffff 0%, #f4fbff 100%);
	border: 2px solid #61c28a;
	box-shadow: 0 30px 70px rgba(0, 0, 0, 0.24);
	border-radius: 22px;
	padding: 34px 26px;
	text-align: center;
}
.borrow-success-card h3 {
	margin: 0;
	font-size: 44px;
	line-height: 1.1;
	color: #0f7a3d;
	letter-spacing: 0.01em;
}
.borrow-success-card p {
	margin: 12px 0 0;
	font-size: 18px;
	font-weight: 700;
	color: #28415f;
}
.dashboard-hero {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 16px;
	margin-bottom: 22px;
}
.dashboard-hero h2 {
	margin: 0;
	font-size: 42px;
	line-height: 1;
	font-weight: 700;
	color: #13233d;
}
.dashboard-hero .eyebrow {
	text-transform: uppercase;
	letter-spacing: 0.18em;
	font-size: 12px;
	font-weight: 700;
	color: #4a6fd0;
	margin-bottom: 10px;
}
.dashboard-hero p {
	margin: 10px 0 0;
	color: #687287;
	font-size: 15px;
}
.hero-action {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	padding: 12px 18px;
	border-radius: 999px;
	background: #1d4ed8;
	color: #fff;
	text-decoration: none;
	white-space: nowrap;
	box-shadow: 0 10px 24px rgba(29, 78, 216, 0.26);
}
.overdue-warning {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 14px;
	padding: 14px 16px;
	margin-bottom: 20px;
	border-radius: 14px;
	background: linear-gradient(180deg, #fff1f1 0%, #ffdcdc 100%);
	border: 1px solid #f1a4a4;
	box-shadow: 0 10px 24px rgba(239, 68, 68, 0.18);
}
.overdue-warning-main {
	display: flex;
	align-items: center;
	gap: 12px;
	color: #7f1d1d;
	font-weight: 700;
}
.overdue-warning-main i {
	font-size: 20px;
	color: #dc2626;
}
.overdue-warning-sub {
	display: block;
	font-size: 13px;
	font-weight: 600;
	color: #9b1c1c;
	margin-top: 2px;
}
.overdue-warning-link {
	padding: 9px 14px;
	border-radius: 10px;
	background: #dc2626;
	color: #fff;
	text-decoration: none;
	font-weight: 700;
	white-space: nowrap;
}
.overdue-warning-link:hover {
	background: #b91c1c;
	color: #fff;
	text-decoration: none;
}

.stats-grid {
	display: grid;
	grid-template-columns: repeat(3, minmax(0, 1fr));
	gap: 16px;
	margin-bottom: 24px;
}
.stat-card {
	background: #fff;
	border-radius: 18px;
	padding: 20px;
	box-shadow: 0 12px 28px rgba(16, 24, 40, 0.08);
	border: 1px solid #e4e8f0;
	min-height: 128px;
	position: relative;
	overflow: hidden;
}
.stat-card::after {
	content: "";
	position: absolute;
	right: -26px;
	top: -26px;
	width: 96px;
	height: 96px;
	border-radius: 50%;
	background: rgba(29, 78, 216, 0.08);
}
.stat-card .stat-label {
	font-size: 13px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.08em;
	color: #6b7280;
}
.stat-card .stat-value {
	font-size: 36px;
	line-height: 1;
	font-weight: 700;
	margin: 14px 0 8px;
	color: #13233d;
}
.stat-card .stat-note {
	font-size: 13px;
	color: #7b8597;
}
.stat-icon {
	position: absolute;
	right: 18px;
	bottom: 16px;
	font-size: 34px;
	color: rgba(29, 78, 216, 0.18);
	z-index: 1;
}
.borrowed-section {
	background: #fff;
	border-radius: 22px;
	border: 1px solid #e4e8f0;
	box-shadow: 0 12px 28px rgba(16, 24, 40, 0.08);
	padding: 22px;
}
.section-head {
	display: flex;
	align-items: flex-end;
	justify-content: space-between;
	gap: 14px;
	margin-bottom: 18px;
}
.section-head h3 {
	margin: 0;
	font-size: 26px;
	font-weight: 700;
	color: #13233d;
}
.section-head p {
	margin: 6px 0 0;
	color: #6b7280;
}
.section-head a {
	color: #1d4ed8;
	font-weight: 700;
	text-decoration: none;
}
.book-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
	gap: 16px;
}
.book-card {
	background: linear-gradient(180deg, #f3f4f6 0%, #e7ebf1 100%);
	border: 1px solid #cfd6e1;
	border-radius: 18px;
	padding: 18px;
	box-shadow: inset 1px 1px 0 rgba(255, 255, 255, 0.82), inset -2px -2px 5px rgba(165, 172, 184, 0.32), 0 8px 18px rgba(31, 41, 55, 0.08);
	position: relative;
}
.book-card-top {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 10px;
	margin-bottom: 14px;
}
.book-badge {
	display: inline-flex;
	align-items: center;
	padding: 6px 12px;
	border-radius: 999px;
	font-size: 12px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.08em;
}
.book-badge.pending {
	background: rgba(29, 78, 216, 0.12);
	color: #1d4ed8;
}
.book-badge.returned {
	background: rgba(16, 185, 129, 0.12);
	color: #059669;
}
.book-badge.other {
	background: rgba(107, 114, 128, 0.12);
	color: #6b7280;
}
.book-id {
	font-size: 12px;
	font-weight: 700;
	color: #8b93a4;
}
.book-card h4 {
	margin: 0 0 8px;
	font-size: 22px;
	line-height: 1.2;
	color: #13233d;
}
.book-meta {
	margin: 0 0 16px;
	color: #6b7280;
	font-size: 14px;
}
.book-details {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 12px 16px;
}
.book-details div {
	background: linear-gradient(180deg, #f1f3f6 0%, #e6eaf0 100%);
	border-radius: 14px;
	border: 1px solid #d5dbe5;
	padding: 12px 14px;
}
.book-details span {
	display: block;
	font-size: 13px;
	color: #13233d;
	font-weight: 700;
	margin-top: 4px;
}
.book-details label {
	display: block;
	font-size: 11px;
	letter-spacing: 0.08em;
	text-transform: uppercase;
	color: #6b7280;
	margin: 0;
}
.book-actions {
	margin-top: 16px;
	display: flex;
	justify-content: flex-end;
}
.book-actions .btn-default {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	padding: 10px 16px;
	border-radius: 12px;
	background: #1d4ed8;
	color: #fff;
	text-decoration: none;
	border: none;
}
.empty-state {
	grid-column: 1 / -1;
	padding: 28px;
	text-align: center;
	color: #6b7280;
	background: #f8fbff;
	border: 1px dashed #ccd7ea;
	border-radius: 16px;
}
@media (max-width: 1100px) {
	.stats-grid {
		grid-template-columns: repeat(2, minmax(0, 1fr));
	}
}
@media (max-width: 900px) {
	.dashboard-shell {
		width: 100%;
	}
	.dashboard-panel {
		flex-direction: column;
	}
	.dashboard-sidebar {
		width: 100%;
	}
	.dashboard-hero {
		flex-direction: column;
		align-items: flex-start;
	}
	.overdue-warning {
		flex-direction: column;
		align-items: flex-start;
	}
	.stats-grid {
		grid-template-columns: 1fr;
	}
	.book-details {
		grid-template-columns: 1fr;
	}
}
</style>

<div class="container dashboard-shell">
	<div class="dashboard-panel">
		<div class="dashboard-sidebar">
			<div class="dashboard-brand">LMS</div>
			<ul class="dashboard-nav">
				<li><a class="active" href="dashboard.php"><i class="icon-home"></i> Dashboard</a></li>
				<li><a href="books.php"><i class="icon-book"></i> Books</a></li>
				<li><a href="fines.php"><i class="icon-exchange"></i> Fines</a></li>
				<li><a href="my_books.php"><i class="icon-list"></i> My Books</a></li>
				<li><a href="advance_search.php"><i class="icon-search"></i> Search</a></li>
			</ul>
			<div class="sidebar-footer">
				<a class="logout-btn" href="#logout" data-toggle="modal">Logout</a>
			</div>
		</div>

		<div class="dashboard-content">
			<?php if ($show_borrow_confirmed) { ?>
				<div class="borrow-success-overlay" id="borrowSuccessOverlay">
					<div class="borrow-success-card">
						<h3>Borrow Confirmed</h3>
						<p>Your book was borrowed successfully.</p>
					</div>
				</div>
			<?php } ?>

			<div class="dashboard-hero">
				<div>
					<div class="eyebrow">LMS Dashboard</div>
					<h2>Welcome Back, <?php echo htmlspecialchars($account_name); ?></h2>
					<p>Get your own book today and borrow straight from the library collection.</p>
				</div>
				<a class="hero-action" href="books.php"><i class="icon-book"></i> Browse Books</a>
			</div>

			<?php if ($overdue_books > 0) { ?>
				<div class="overdue-warning">
					<div class="overdue-warning-main">
						<i class="icon-warning-sign"></i>
						<div>
							You have <?php echo (int) $overdue_books; ?> overdue <?php echo $overdue_books === 1 ? 'book' : 'books'; ?>.
							<span class="overdue-warning-sub">Please return <?php echo $overdue_books === 1 ? 'it' : 'them'; ?> immediately to avoid higher fines.</span>
						</div>
					</div>
					<a class="overdue-warning-link" href="fines.php">View Fines</a>
				</div>
			<?php } ?>

			<div class="stats-grid">
				<div class="stat-card">
					<div class="stat-label">Borrowed Books</div>
					<div class="stat-value"><?php echo number_format($borrowed_books); ?></div>
					<div class="stat-note">Currently checked out</div>
					<i class="icon-exchange stat-icon"></i>
				</div>
				<div class="stat-card">
					<div class="stat-label">Returned Books</div>
					<div class="stat-value"><?php echo number_format($returned_books); ?></div>
					<div class="stat-note">Completed return transactions</div>
					<i class="icon-ok-sign stat-icon"></i>
				</div>
				<div class="stat-card">
					<div class="stat-label">Available Books</div>
					<div class="stat-value"><?php echo number_format($available_books); ?></div>
					<div class="stat-note">Copies ready to issue</div>
					<i class="icon-check-sign stat-icon"></i>
				</div>
			</div>

			<div class="borrowed-section">
				<div class="section-head">
					<div>
						<h3>Borrowed Books</h3>
						<p>Latest active borrow records shown as quick tiles.</p>
					</div>
				</div>

				<div class="book-grid">
					<?php if (mysqli_num_rows($borrowed_books_query) > 0) { ?>
						<?php while ($row = mysqli_fetch_array($borrowed_books_query)) {
							$borrow_details_id = $row['borrow_details_id'];
							$status_class = 'pending';
							$status_text = 'Borrowed';
							if ($row['borrow_status'] === 'returned') {
								$status_class = 'returned';
								$status_text = 'Returned';
							} elseif ($row['borrow_status'] !== 'pending') {
								$status_class = 'other';
								$status_text = ucfirst($row['borrow_status']);
							}
						?>
							<article class="book-card">
								<div class="book-card-top">
									<span class="book-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
									<span class="book-id">#<?php echo (int) $row['borrow_id']; ?></span>
								</div>
								<h4><?php echo htmlspecialchars($row['book_title']); ?></h4>
								<p class="book-meta">Student <?php echo htmlspecialchars($row['student_no'] . ' - ' . $row['firstname'] . ' ' . $row['lastname']); ?></p>
								<div class="book-details">
									<div>
										<label>Borrowed</label>
										<span><?php echo htmlspecialchars($row['date_borrow']); ?></span>
									</div>
									<div>
										<label>Due</label>
										<span><?php echo htmlspecialchars($row['due_date']); ?></span>
									</div>
									<div>
										<label>Borrower ID</label>
										<span><?php echo htmlspecialchars($row['student_no']); ?></span>
									</div>
									<div>
										<label>Returned</label>
										<span><?php echo htmlspecialchars($row['date_return'] ? $row['date_return'] : 'Pending'); ?></span>
									</div>
								</div>
								<div class="book-actions">
									<a rel="tooltip" title="Return" id="<?php echo $borrow_details_id; ?>" href="#delete_book<?php echo $borrow_details_id; ?>" data-toggle="modal" class="btn-default">Return</a>
									<?php include('modal_return.php'); ?>
								</div>
							</article>
						<?php } ?>
					<?php } else { ?>
						<div class="empty-state">No active borrowed books to show right now.</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php if ($show_borrow_confirmed) { ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
	var overlay = document.getElementById('borrowSuccessOverlay');
	if (!overlay) {
		return;
	}
	window.setTimeout(function () {
		overlay.style.display = 'none';
	}, 2600);
	overlay.addEventListener('click', function () {
		overlay.style.display = 'none';
	});
});
</script>
<?php } ?>

<?php include('search_form.php'); ?>
<?php include('footer.php'); ?>
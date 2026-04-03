<?php include('header.php'); ?>
<?php include('session.php'); ?>
<?php include('dbcon.php'); ?>
<?php
$title = isset($_REQUEST['title']) ? trim($_REQUEST['title']) : '';
$author = isset($_REQUEST['author']) ? trim($_REQUEST['author']) : '';
$category = isset($_REQUEST['category']) ? trim($_REQUEST['category']) : '';
$status = isset($_REQUEST['status']) ? trim($_REQUEST['status']) : '';

$title_esc = mysqli_real_escape_string($con, $title);
$author_esc = mysqli_real_escape_string($con, $author);
$category_esc = mysqli_real_escape_string($con, $category);
$status_esc = mysqli_real_escape_string($con, $status);

$conditions = array("1=1");
if ($title !== '') {
	$conditions[] = "b.book_title LIKE '%$title_esc%'";
}
if ($author !== '') {
	$conditions[] = "b.author LIKE '%$author_esc%'";
}
if ($category !== '') {
	$conditions[] = "c.classname LIKE '%$category_esc%'";
}
if ($status !== '') {
	$conditions[] = "b.status LIKE '%$status_esc%'";
}
$search_where = implode(' AND ', $conditions);

$author_options = array();
$title_options = array();
$category_options = array();
$status_options = array();

$title_q = mysqli_query($con, "SELECT DISTINCT book_title FROM book WHERE book_title <> '' ORDER BY book_title ASC") or die(mysqli_error($con));
while ($r = mysqli_fetch_assoc($title_q)) {
	$title_options[] = $r['book_title'];
}

$author_q = mysqli_query($con, "SELECT DISTINCT author FROM book WHERE author <> '' ORDER BY author ASC") or die(mysqli_error($con));
while ($r = mysqli_fetch_assoc($author_q)) {
	$author_options[] = $r['author'];
}

$category_q = mysqli_query($con, "SELECT DISTINCT classname FROM category WHERE classname <> '' ORDER BY classname ASC") or die(mysqli_error($con));
while ($r = mysqli_fetch_assoc($category_q)) {
	$category_options[] = $r['classname'];
}

$status_q = mysqli_query($con, "SELECT DISTINCT status FROM book WHERE status <> '' ORDER BY status ASC") or die(mysqli_error($con));
while ($r = mysqli_fetch_assoc($status_q)) {
	$status_options[] = $r['status'];
}

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
?>

<style>
body {
	background: #eff3f8;
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
	box-sizing: border-box;
	background:
		radial-gradient(circle at top right, rgba(63, 111, 217, 0.12), transparent 28%),
		radial-gradient(circle at 12% 8%, rgba(20, 184, 166, 0.10), transparent 26%),
		#f7f9fd;
}
.dashboard-hero h2 {
	margin: 0;
	font-size: 40px;
	color: #13233d;
}
.dashboard-hero p {
	margin: 10px 0 18px;
	color: #687287;
}
.search-card {
	background: #fff;
	border-radius: 22px;
	border: 1px solid #e4e8f0;
	box-shadow: 0 12px 28px rgba(16, 24, 40, 0.08);
	padding: 20px;
}
.search-row {
	display: grid;
	grid-template-columns: 1fr 1fr 1fr 1fr auto;
	gap: 10px;
	margin-bottom: 14px;
}
.search-row input {
	width: 100%;
	box-sizing: border-box;
	border: 1px solid #d4dce8;
	border-radius: 10px;
	font-size: 14px;
	padding: 11px 12px;
	background: #f9fbff;
}
.search-row .search-select {
	width: 100%;
}
.select2-container {
	width: 100% !important;
}
.select2-container .select2-choice {
	height: 40px;
	line-height: 40px;
	border: 1px solid #d4dce8;
	border-radius: 10px;
	background: #f9fbff;
}
.search-row button {
	border: none;
	border-radius: 10px;
	padding: 11px 16px;
	background: #2e65d9;
	color: #fff;
	font-weight: 700;
}
.table-wrap {
	overflow-x: auto;
}
.table th,
.table td {
	vertical-align: middle;
}
@media (max-width: 980px) {
	.dashboard-panel {
		flex-direction: column;
	}
	.dashboard-sidebar {
		width: 100%;
	}
	.search-row {
		grid-template-columns: 1fr;
	}
}
</style>

<div class="container dashboard-shell">
	<div class="dashboard-panel">
		<div class="dashboard-sidebar">
			<div class="dashboard-brand">LMS</div>
			<ul class="dashboard-nav">
				<li><a href="dashboard.php"><i class="icon-home"></i> Dashboard</a></li>
				<li><a href="books.php"><i class="icon-book"></i> Books</a></li>
				<li><a href="fines.php"><i class="icon-exchange"></i> Fines</a></li>
				<li><a href="my_books.php"><i class="icon-list"></i> My Books</a></li>
				<li><a class="active" href="advance_search.php"><i class="icon-search"></i> Search</a></li>
			</ul>
			<div class="sidebar-footer">
				<a class="logout-btn" href="#logout" data-toggle="modal">Logout</a>
			</div>
		</div>

		<div class="dashboard-content">
			<div class="dashboard-hero">
				<h2>Search Books</h2>
				<p>Welcome back, <?php echo htmlspecialchars($account_name); ?>. Search by book name and author.</p>
			</div>

			<div class="search-card">
				<form method="get" action="advance_search.php" class="search-row">
					<select name="title" id="title" class="search-select" data-placeholder="Book Name">
						<option value=""></option>
						<?php foreach ($title_options as $option) { ?>
							<option value="<?php echo htmlspecialchars($option); ?>" <?php echo $title === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
						<?php } ?>
					</select>
					<select name="author" id="author" class="search-select" data-placeholder="Author Name">
						<option value=""></option>
						<?php foreach ($author_options as $option) { ?>
							<option value="<?php echo htmlspecialchars($option); ?>" <?php echo $author === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
						<?php } ?>
					</select>
					<select name="category" id="category" class="search-select" data-placeholder="Category">
						<option value=""></option>
						<?php foreach ($category_options as $option) { ?>
							<option value="<?php echo htmlspecialchars($option); ?>" <?php echo $category === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
						<?php } ?>
					</select>
					<select name="status" id="status" class="search-select" data-placeholder="Status">
						<option value=""></option>
						<?php foreach ($status_options as $option) { ?>
							<option value="<?php echo htmlspecialchars($option); ?>" <?php echo $status === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
						<?php } ?>
					</select>
					<button type="submit">Search</button>
				</form>

				<div class="table-wrap">
					<table cellpadding="0" cellspacing="0" border="0" class="table table-bordered" id="booksResultTable">
						<thead>
							<tr>
								<th>Acc No.</th>
								<th>Book Title</th>
								<th>Category</th>
								<th>Author</th>
								<th>No. of Copies</th>
								<th>Book Pub</th>
								<th>Publisher Name</th>
								<th>ISBN</th>
								<th>Copyright Year</th>
								<th>Date Added</th>
								<th>Status</th>
								<th>Borrow</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$user_query = mysqli_query($con, "
								SELECT b.*, c.classname
								FROM book b
								LEFT JOIN category c ON b.category_id = c.category_id
								WHERE $search_where
								ORDER BY b.book_title ASC
							") or die(mysqli_error($con));

							while ($row = mysqli_fetch_array($user_query)) {
							?>
							<tr>
								<td><?php echo $row['book_id']; ?></td>
								<td><?php echo $row['book_title']; ?></td>
								<td><?php echo $row['classname']; ?></td>
								<td><?php echo $row['author']; ?></td>
								<td><?php echo (int) $row['book_copies']; ?></td>
								<td><?php echo $row['book_pub']; ?></td>
								<td><?php echo $row['publisher_name']; ?></td>
								<td><?php echo $row['isbn']; ?></td>
								<td><?php echo $row['copyright_year']; ?></td>
								<td><?php echo $row['date_added']; ?></td>
								<td><?php echo $row['status']; ?></td>
								<td>
									<?php if ((int)$row['book_copies'] > 0) { ?>
										<a href="borrow.php" class="btn-default">Borrow</a>
									<?php } else { ?>
										<span style="opacity:0.6;">Unavailable</span>
									<?php } ?>
								</td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/3.5.4/select2.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/3.5.4/select2.min.js"></script>
<script>
jQuery(function ($) {
	$('.search-select').each(function () {
		var placeholder = $(this).data('placeholder') || 'Select';
		$(this).select2({
			allowClear: true,
			placeholder: placeholder,
			width: 'resolve'
		});
	});
});
</script>

<?php include('footer.php') ?>
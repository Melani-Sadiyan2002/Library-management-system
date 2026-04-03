<?php
include('session.php');
include('dbcon.php');
include('header.php');

$account_name = 'Account';
$account_query = mysqli_query($con, "SELECT username, firstname, lastname FROM students WHERE student_id='" . (int)$session_id . "' LIMIT 1") or die("Database error: " . mysqli_error($con));
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
$book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;
$scan_status = '';
$scan_message = '';
$book = null;
$barcode_value = '';

// Handle barcode scan first to allow any book to be scanned
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scanned_barcode'])) {
    $scanned_barcode = trim($_POST['scanned_barcode']);
    
    if ($scanned_barcode === '') {
        $scan_status = 'error';
        $scan_message = 'Please scan a barcode.';
    } else {
        // Try to find book by ISBN or ID
        $scan_compact = preg_replace('/[^A-Za-z0-9]/', '', $scanned_barcode);
        $scan_esc = mysqli_real_escape_string($con, $scanned_barcode);
        $scan_compact_esc = mysqli_real_escape_string($con, $scan_compact);
        $scan_id = ctype_digit($scan_compact) ? (int) $scan_compact : 0;
        
        $query = "
            SELECT book_id, book_title, isbn FROM book
            WHERE isbn = '$scan_esc'
            OR REPLACE(REPLACE(REPLACE(REPLACE(isbn, '-', ''), ' ', ''), '/', ''), '.', '') = '$scan_compact_esc'
            OR (book_id = '$scan_id' AND '$scan_id' != '0')
            LIMIT 1
        ";
        $result = mysqli_query($con, $query) or die("Database error: " . mysqli_error($con));
        $found_book = mysqli_fetch_assoc($result);
        
        if (!$found_book) {
            $scan_status = 'error';
            $scan_message = 'Book not found! Invalid barcode.';
        } else {
            $book_id = (int) $found_book['book_id'];
            $book = $found_book;
            
            // Mark the borrowed copy as returned and available again
            $update_query = "UPDATE borrowdetails SET borrow_status='returned', date_return=NOW() 
                            WHERE book_id='$book_id' AND borrow_status='pending' LIMIT 1";
            
            if (mysqli_query($con, $update_query)) {
                if (mysqli_affected_rows($con) > 0) {
                    mysqli_query($con, "UPDATE book SET book_copies = book_copies + 1 WHERE book_id='$book_id' LIMIT 1") or die("Database error: " . mysqli_error($con));
                    $scan_status = 'success';
                    $scan_message = 'Book returned successfully!';
                } else {
                    $scan_status = 'error';
                    $scan_message = 'No pending borrow found for this book.';
                }
            } else {
                $scan_status = 'error';
                $scan_message = 'Database error: ' . mysqli_error($con);
            }
        }
    }
}

// If not scanning and no book yet, just load for scanning
if (!$book && $book_id > 0) {
    $query = "SELECT book_id, book_title, isbn FROM book WHERE book_id='$book_id' LIMIT 1";
    $result = mysqli_query($con, $query) or die("Database error: " . mysqli_error($con));
    $book = mysqli_fetch_assoc($result);
}

if (!$book) {
    $book = array(
        'book_id' => 0,
        'book_title' => 'Scan a Book',
        'isbn' => ''
    );
}
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
	.barcode-panel {
		background: #fff;
		border: 1px solid #d8dde6;
		border-radius: 10px;
		padding: 16px;
		margin: 0 8px 14px;
	}
	.return-summary {
		display: flex;
		justify-content: space-between;
		align-items: center;
		gap: 12px;
		padding: 12px 14px;
		margin-bottom: 14px;
		background: #f6f8fb;
		border: 1px solid #d8dde6;
		border-radius: 8px;
	}
	.status-pill {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		padding: 10px 18px;
		border-radius: 999px;
		font-size: 13px;
		font-weight: 700;
		background: #f39c12;
		color: #fff;
	}
	.status-pill.success {
		background: #27ae60;
	}
	.scan-stage {
		background: #d8e7ff;
		border: 1px solid #8db4ff;
		border-radius: 12px;
		min-height: 210px;
		display: flex;
		align-items: center;
		justify-content: center;
		flex-direction: column;
		padding: 18px;
		margin: 10px auto 16px;
		max-width: 560px;
		box-sizing: border-box;
	}
	.scan-stage.scanning {
		padding-bottom: 12px;
	}
	.scan-art {
		width: 100%;
		max-width: 360px;
		min-height: 120px;
		background: transparent;
		text-align: center;
		padding: 18px 10px;
	}
	.scan-lines {
		font-size: 0;
		margin-bottom: 18px;
	}
	.scan-lines span {
		display: inline-block;
		width: 6px;
		height: 58px;
		margin: 0 2px;
		background: #1a1a1a;
	}
	.scan-headline {
		font-size: 22px;
		font-weight: 700;
		color: #2a3a52;
		margin-bottom: 4px;
	}
	.scan-subtitle {
		font-size: 14px;
		color: #9ca6b6;
	}
	.scanner-toggle {
		display: inline-block;
		padding: 10px 18px;
		background: #3f6fd9;
		color: #fff;
		border: 0;
		border-radius: 6px;
		font-size: 14px;
		font-weight: 700;
		cursor: pointer;
	}
	.scanner-toggle:hover {
		background: #325ec0;
	}
	.scanner-shell {
		display: none;
		width: 100%;
		max-width: 280px;
		margin: 10px auto 0;
		padding: 6px;
		border: 1px solid #d8dde6;
		border-radius: 8px;
		background: #fff;
	}
	.scanner-view {
		width: 100%;
		height: 140px;
		min-height: 140px;
		overflow: hidden;
		border-radius: 6px;
		background: #eef4ff;
	}
	.scanner-view video,
	.scanner-view canvas {
		width: 100% !important;
		height: 100% !important;
		object-fit: cover;
		display: block;
	}
	.barcode-actions {
		display: flex;
		justify-content: center;
		gap: 12px;
		flex-wrap: wrap;
		margin-top: 14px;
	}
	.barcode-button {
		display: inline-block;
		padding: 10px 18px;
		background: #3f6fd9;
		color: #fff;
		border: 0;
		border-radius: 6px;
		font-size: 14px;
		font-weight: 700;
	}
	.barcode-button:hover {
		background: #325ec0;
	}
	.barcode-button.alt {
		background: #95a5a6;
	}
	.barcode-button.alt:hover {
		background: #7f8c8d;
	}
	.barcode-input {
		width: 100%;
		max-width: 420px;
		padding: 10px 12px;
		border: 1px solid #cfd6e0;
		border-radius: 6px;
		font-size: 14px;
		margin: 0 auto 12px;
		display: block;
	}
	.notice {
		margin: 0 8px 14px;
		padding: 12px 14px;
		border-radius: 6px;
		border: 1px solid #d8dde6;
	}
	.notice.success {
		background: #e8f7ee;
		color: #155724;
		border-color: #bfe3c8;
	}
	.return-success-overlay {
		position: fixed;
		inset: 0;
		background: rgba(11, 22, 41, 0.48);
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 9999;
		padding: 18px;
		box-sizing: border-box;
	}
	.return-success-card {
		width: min(700px, 94vw);
		background: linear-gradient(180deg, #ffffff 0%, #f4fbff 100%);
		border: 2px solid #61c28a;
		box-shadow: 0 30px 70px rgba(0, 0, 0, 0.24);
		border-radius: 22px;
		padding: 34px 26px;
		text-align: center;
	}
	.return-success-card h3 {
		margin: 0;
		font-size: 44px;
		line-height: 1.1;
		color: #0f7a3d;
	}
	.return-success-card p {
		margin: 12px 0 0;
		font-size: 18px;
		font-weight: 700;
		color: #28415f;
	}
	.notice.error {
		background: #fbeaec;
		color: #721c24;
		border-color: #f1c3cb;
	}
	.action-links {
		display: flex;
		gap: 10px;
		flex-wrap: wrap;
		margin: 0 8px 8px;
	}
	.action-links a {
		display: inline-block;
		padding: 10px 16px;
		border-radius: 6px;
		text-decoration: none;
		font-size: 14px;
		font-weight: 600;
	}
	.action-links .primary {
		background: #3f6fd9;
		color: #fff;
	}
	.action-links .secondary {
		background: #f6f8fb;
		color: #2e3642;
		border: 1px solid #d8dde6;
	}
	@media (max-width: 900px) {
		.dashboard-shell.container {
			width: 100%;
		}
		.dashboard-panel {
			flex-direction: column;
		}
		.dashboard-sidebar {
			width: 100%;
			border-right: none;
			border-bottom: 1px solid #d8dde6;
		}
		.dashboard-content {
			padding: 12px;
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
				<li><a href="advance_search.php"><i class="icon-search"></i> Search</a></li>
			</ul>
			<div class="sidebar-footer">
				<a class="logout-btn" href="#logout" data-toggle="modal">Logout</a>
			</div>
		</div>

			<div class="dashboard-content">
				<h2>Welcome Back, <?php echo htmlspecialchars($account_name); ?></h2>

				<div class="modern-content-card return-card">
					<div class="borrowed-title">Return Barcode Confirmation</div>
					<div class="return-summary">
						<?php if ($book && $book['book_id'] > 0): ?>
						<div><strong>Book:</strong> <?php echo htmlspecialchars($book['book_title']); ?></div>
							<div class="status-pill">Scan Pending</div>
						<?php endif; ?>

					</div>

					<div class="barcode-panel">
						<div class="scan-stage">
							<div class="scan-art">
								<div class="scan-lines" aria-hidden="true">
									<span style="height:52px"></span>
									<span style="height:62px"></span>
									<span style="height:48px"></span>
									<span style="height:68px"></span>
									<span style="height:56px"></span>
									<span style="height:64px"></span>
									<span style="height:46px"></span>
									<span style="height:66px"></span>
									<span style="height:54px"></span>
									<span style="height:60px"></span>
									<span style="height:50px"></span>
								</div>
								<div class="scan-headline">Scan Barcode</div>
								<div class="scan-subtitle">Place the Book Barcode under the scanner to read</div>
							</div>
							<div id="scanner-shell" class="scanner-shell">
								<div id="scanner-view" class="scanner-view"></div>
								<div style="margin-top:10px; text-align:center;">
									<button type="button" id="close-scanner" class="barcode-button alt">Close Scanner</button>
								</div>
							</div>
						</div>
						<div class="barcode-actions">
							<button type="button" id="toggle-scanner" class="scanner-toggle">Open Camera Scanner</button>
						</div>
					</div>

					<?php if ($scan_status === 'success'): ?>
						<div class="return-success-overlay" id="returnSuccessOverlay">
							<div class="return-success-card">
								<h3>Book Returned Successfully</h3>
								<p><?php echo htmlspecialchars($scan_message); ?></p>
							</div>
						</div>
					<?php elseif ($scan_status === 'error'): ?>
						<div class="notice error">✗ <?php echo $scan_message; ?></div>
					<?php endif; ?>

					<form method="POST" id="barcode-form">
						<input type="hidden" id="scanned_barcode" name="scanned_barcode">
					</form>

					<div class="action-links">
						<a href="dashboard.php" class="secondary">Cancel</a>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script src="https://unpkg.com/quagga/dist/quagga.min.js" type="text/javascript"></script>
	<script type="text/javascript">
	(function () {
		var scanner = null;
		var scannerOpen = false;
		var scanLocked = false;
		var toggleButton = document.getElementById('toggle-scanner');
		var closeButton = document.getElementById('close-scanner');
		var scannerShell = document.getElementById('scanner-shell');
		var form = document.getElementById('barcode-form');
		var barcodeInput = document.getElementById('scanned_barcode');
		var scanStage = document.querySelector('.scan-stage');

		function stopScanner() {
			if (scanner) {
				Quagga.stop();
				scanner = null;
			}
			scannerOpen = false;
			scanLocked = false;
			scannerShell.style.display = 'none';
			scanStage.classList.remove('scanning');
		}

		function startScanner() {
			if (scannerOpen) {
				return;
			}
			scannerOpen = true;
			scanLocked = false;
			scannerShell.style.display = 'block';
			scanStage.classList.add('scanning');
			scanner = true;

			Quagga.init({
				inputStream: {
					type: 'LiveStream',
					target: document.querySelector('#scanner-view'),
					constraints: {
						facingMode: 'environment'
					}
				},
				locator: {
					patchSize: 'medium',
					halfSample: true
				},
				locate: true,
				numOfWorkers: navigator.hardwareConcurrency ? Math.min(4, navigator.hardwareConcurrency) : 2,
				decoder: {
					readers: [
						'code_128_reader',
						'code_39_reader',
						'codabar_reader',
						'upc_reader',
						'upc_e_reader',
						'ean_reader',
						'ean_8_reader'
					]
				}
			}, function (err) {
				if (err) {
					scannerShell.innerHTML = '<div class="notice error">Camera access is not available in this browser or permission was denied.</div>';
					scannerOpen = false;
					scanner = null;
					return;
				}

				Quagga.start();
			});

			Quagga.offDetected();
			Quagga.onDetected(function (result) {
				if (scanLocked) {
					return;
				}

				var code = result && result.codeResult ? result.codeResult.code : '';
				if (!code) {
					return;
				}

				scanLocked = true;
				barcodeInput.value = code;
				stopScanner();
				form.submit();
			});
		}

		toggleButton.addEventListener('click', startScanner);
		closeButton.addEventListener('click', stopScanner);
	})();
	</script>
	<?php if ($scan_status === 'success'): ?>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		window.setTimeout(function () {
			window.location.href = 'dashboard.php';
		}, 2400);
	});
	</script>
	<?php endif; ?>

	<?php include('footer.php'); ?>

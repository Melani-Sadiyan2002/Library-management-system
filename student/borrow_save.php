	<?php
 	include('dbcon.php');

	$student_id = isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0;
	$scan_code = isset($_POST['scan_code']) ? trim($_POST['scan_code']) : '';
	$selected_ids = (isset($_POST['selector']) && is_array($_POST['selector'])) ? $_POST['selector'] : array();
	$due_date = date('d/m/Y', strtotime('+14 days'));

	if ($student_id <= 0) {
		header("location: borrow.php?error=Please%20select%20a%20student");
		exit();
	}

	$book_ids = array();

	if ($scan_code !== '') {
		$scan_compact = preg_replace('/[^A-Za-z0-9]/', '', $scan_code);
		$scan_esc = mysqli_real_escape_string($con, $scan_code);
		$scan_compact_esc = mysqli_real_escape_string($con, $scan_compact);
		$scan_id = ctype_digit($scan_compact) ? (int) $scan_compact : 0;
		$scan_len = strlen($scan_compact);
		$use_partial_match = $scan_len >= 8;
		$isbn_normalized_expr = "REPLACE(REPLACE(REPLACE(REPLACE(isbn, '-', ''), ' ', ''), '/', ''), '.', '')";
		$partial_match_sql = '';
		if ($use_partial_match) {
			$partial_match_sql = "
				OR $isbn_normalized_expr LIKE CONCAT('%', '$scan_compact_esc', '%')
				OR '$scan_compact_esc' LIKE CONCAT('%', $isbn_normalized_expr, '%')
			";
		}

		$book_query = mysqli_query($con, "
			SELECT book_id, book_copies
			FROM book
			WHERE 1=1
			AND (
				isbn = '$scan_esc'
				OR $isbn_normalized_expr = '$scan_compact_esc'
				OR (book_id = '$scan_id' AND '$scan_id' != '0')
				$partial_match_sql
			)
			AND book_copies > 0
			ORDER BY book_copies DESC, book_id DESC
			LIMIT 1
		") or die(mysqli_error($con));
		$book_row = mysqli_fetch_assoc($book_query);

		if (!$book_row) {
			$no_copy_query = mysqli_query($con, "
				SELECT book_id
				FROM book
				WHERE 1=1
				AND (
					isbn = '$scan_esc'
					OR $isbn_normalized_expr = '$scan_compact_esc'
					OR (book_id = '$scan_id' AND '$scan_id' != '0')
					$partial_match_sql
				)
				LIMIT 1
			") or die(mysqli_error($con));

			if (mysqli_fetch_assoc($no_copy_query)) {
				header("location: borrow.php?error=No%20available%20copies%20for%20this%20book");
			} else {
				header("location: borrow.php?error=Scanned%20barcode%20was%20not%20found");
			}
			exit();
		}

		$book_ids[] = (int) $book_row['book_id'];
	} else {
		if (empty($selected_ids)) {
			header("location: borrow.php?error=Please%20select%20a%20book%20or%20scan%20a%20barcode");
			exit();
		}

		$N = count($selected_ids);
		for ($i = 0; $i < $N; $i++) {
			$book_id = (int) $selected_ids[$i];
			if ($book_id > 0) {
				$book_ids[] = $book_id;
			}
		}
	}

	if (empty($book_ids)) {
		header("location: borrow.php?error=No%20valid%20book%20selected");
		exit();
	}

	$book_ids = array_unique($book_ids);

	for ($i = 0; $i < count($book_ids); $i++) {
		$book_id = (int) $book_ids[$i];
		$stock_query = mysqli_query($con, "SELECT book_copies FROM book WHERE book_id = '$book_id' LIMIT 1") or die(mysqli_error($con));
		$stock_row = mysqli_fetch_assoc($stock_query);
		if (!$stock_row || (int) $stock_row['book_copies'] <= 0) {
			header("location: borrow.php?error=One%20or%20more%20selected%20books%20are%20not%20available");
			exit();
		}
	}

	$date_borrow = date('Y-m-d H:i:s');
	mysqli_query($con, "insert into borrow (student_id,date_borrow,due_date) values ('$student_id','$date_borrow','$due_date')") or die(mysqli_error($con));
	$borrow_id = (int) mysqli_insert_id($con);

	for ($i = 0; $i < count($book_ids); $i++) {
		$book_id = (int) $book_ids[$i];
		mysqli_query($con, "insert borrowdetails (book_id,borrow_id,borrow_status,date_return) values('$book_id','$borrow_id','pending','')") or die(mysqli_error($con));
		mysqli_query($con, "update book set book_copies = book_copies - 1 where book_id = '$book_id' and book_copies > 0") or die(mysqli_error($con));
	}

	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
	$_SESSION['borrow_confirmed'] = 1;
	header("location: dashboard.php");
	exit();
?>
	
	

	
	
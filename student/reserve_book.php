<?php
include('session.php');
include('dbcon.php');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function lms_ensure_reservations_table($con)
{
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS book_reservations (
            reservation_id INT(11) NOT NULL AUTO_INCREMENT,
            student_id INT(11) NOT NULL,
            book_id INT(11) NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'reserved',
            reserved_at DATETIME NOT NULL,
            borrowed_at DATETIME DEFAULT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (reservation_id),
            KEY student_id (student_id),
            KEY book_id (book_id),
            KEY status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1
    ") or die(mysqli_error($con));
}

lms_ensure_reservations_table($con);

$session_student_id = (int) $session_id;
$book_id = isset($_POST['book_id']) ? (int) $_POST['book_id'] : 0;
$return_filter = isset($_POST['return_filter']) ? trim((string) $_POST['return_filter']) : 'all';
$allowed_filters = array('all', 'new', 'old', 'lost', 'damage');
if (!in_array($return_filter, $allowed_filters, true)) {
    $return_filter = 'all';
}
$return_url = 'books.php' . ($return_filter !== 'all' ? '?filter=' . urlencode($return_filter) : '');

if ($book_id <= 0) {
    $_SESSION['reserve_notice'] = 'Invalid book selected.';
    $_SESSION['reserve_notice_type'] = 'error';
    header('Location: ' . $return_url);
    exit();
}

$book_query = mysqli_query($con, "SELECT book_id, book_copies FROM book WHERE book_id = '$book_id' LIMIT 1") or die(mysqli_error($con));
$book_row = mysqli_fetch_assoc($book_query);
if (!$book_row || (int) $book_row['book_copies'] <= 0) {
    $_SESSION['reserve_notice'] = 'This book is currently unavailable for reservation.';
    $_SESSION['reserve_notice_type'] = 'error';
    header('Location: ' . $return_url);
    exit();
}

$already_borrowed_query = mysqli_query($con, "
    SELECT bd.borrow_details_id
    FROM borrowdetails bd
    INNER JOIN borrow b ON bd.borrow_id = b.borrow_id
    WHERE b.student_id = '$session_student_id' AND bd.book_id = '$book_id' AND bd.borrow_status = 'pending'
    LIMIT 1
") or die(mysqli_error($con));

if (mysqli_num_rows($already_borrowed_query) > 0) {
    $_SESSION['reserve_notice'] = 'You already borrowed this book.';
    $_SESSION['reserve_notice_type'] = 'error';
    header('Location: ' . $return_url);
    exit();
}

$already_reserved_query = mysqli_query($con, "
    SELECT reservation_id
    FROM book_reservations
    WHERE student_id = '$session_student_id' AND book_id = '$book_id' AND status = 'reserved'
    LIMIT 1
") or die(mysqli_error($con));

if (mysqli_num_rows($already_reserved_query) > 0) {
    $_SESSION['reserve_notice'] = 'You already reserved this book.';
    $_SESSION['reserve_notice_type'] = 'error';
    header('Location: ' . $return_url);
    exit();
}

mysqli_query($con, "
    INSERT INTO book_reservations (student_id, book_id, status, reserved_at, borrowed_at, updated_at)
    VALUES ('$session_student_id', '$book_id', 'reserved', NOW(), NULL, NOW())
") or die(mysqli_error($con));

$_SESSION['reserve_notice'] = 'Book reserved successfully. You can borrow it from My Books.';
$_SESSION['reserve_notice_type'] = 'success';
header('Location: ' . $return_url);
exit();
?>
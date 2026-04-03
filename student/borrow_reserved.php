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
$reservation_id = isset($_GET['reservation_id']) ? (int) $_GET['reservation_id'] : 0;

if ($reservation_id <= 0) {
    $_SESSION['my_books_notice'] = 'Invalid reservation selected.';
    $_SESSION['my_books_notice_type'] = 'error';
    header('Location: my_books.php');
    exit();
}

$reservation_query = mysqli_query($con, "
    SELECT reservation_id, book_id, status
    FROM book_reservations
    WHERE reservation_id = '$reservation_id' AND student_id = '$session_student_id'
    LIMIT 1
") or die(mysqli_error($con));

$reservation_row = mysqli_fetch_assoc($reservation_query);
if (!$reservation_row || strtolower((string) $reservation_row['status']) !== 'reserved') {
    $_SESSION['my_books_notice'] = 'This reservation is no longer available.';
    $_SESSION['my_books_notice_type'] = 'error';
    header('Location: my_books.php');
    exit();
}

$book_id = (int) $reservation_row['book_id'];
$book_query = mysqli_query($con, "SELECT book_id, book_copies FROM book WHERE book_id = '$book_id' LIMIT 1") or die(mysqli_error($con));
$book_row = mysqli_fetch_assoc($book_query);
if (!$book_row || (int) $book_row['book_copies'] <= 0) {
    $_SESSION['my_books_notice'] = 'No available copies right now. Please try again later.';
    $_SESSION['my_books_notice_type'] = 'error';
    header('Location: my_books.php');
    exit();
}

$due_date = date('d/m/Y', strtotime('+14 days'));
$date_borrow = date('Y-m-d H:i:s');

mysqli_query($con, "INSERT INTO borrow (student_id, date_borrow, due_date) VALUES ('$session_student_id', '$date_borrow', '$due_date')") or die(mysqli_error($con));
$borrow_id = (int) mysqli_insert_id($con);

mysqli_query($con, "INSERT INTO borrowdetails (book_id, borrow_id, borrow_status, date_return) VALUES ('$book_id', '$borrow_id', 'pending', '')") or die(mysqli_error($con));
mysqli_query($con, "UPDATE book SET book_copies = book_copies - 1 WHERE book_id = '$book_id' AND book_copies > 0") or die(mysqli_error($con));

mysqli_query($con, "
    UPDATE book_reservations
    SET status = 'borrowed', borrowed_at = NOW(), updated_at = NOW()
    WHERE reservation_id = '$reservation_id' AND student_id = '$session_student_id'
") or die(mysqli_error($con));

$_SESSION['my_books_notice'] = 'Reserved book borrowed successfully.';
$_SESSION['my_books_notice_type'] = 'success';
header('Location: my_books.php');
exit();
?>
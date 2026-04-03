<?php
include('dbcon.php');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
unset($_SESSION['borrow_confirmed']);

$session_student_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
if ($session_student_id <= 0) {
    header('Location: index.php');
    exit();
}

$borrow_details_id = isset($_GET['borrow_details_id']) ? (int)$_GET['borrow_details_id'] : 0;

if ($borrow_details_id <= 0 && isset($_GET['id'], $_GET['book_id'])) {
    $borrow_id = (int)$_GET['id'];
    $book_id = (int)$_GET['book_id'];

    $lookup_query = "
        SELECT bd.borrow_details_id
        FROM borrowdetails bd
        INNER JOIN borrow b ON b.borrow_id = bd.borrow_id
        WHERE bd.borrow_id='$borrow_id' AND bd.book_id='$book_id' AND b.student_id='$session_student_id'
        LIMIT 1
    ";
    $lookup_result = mysqli_query($con, $lookup_query) or die(mysqli_error($con));
    if ($lookup_row = mysqli_fetch_assoc($lookup_result)) {
        $borrow_details_id = (int)$lookup_row['borrow_details_id'];
    }
}

if ($borrow_details_id > 0) {
    // Get book_id from borrowdetails before updating
    $get_book_query = "
        SELECT bd.book_id
        FROM borrowdetails bd
        INNER JOIN borrow b ON b.borrow_id = bd.borrow_id
        WHERE bd.borrow_details_id='$borrow_details_id' AND b.student_id='$session_student_id'
        LIMIT 1
    ";
    $book_result = mysqli_query($con, $get_book_query);
    $book_row = mysqli_fetch_assoc($book_result);
    $book_id = isset($book_row['book_id']) ? (int)$book_row['book_id'] : 0;
    
    $query = "
        UPDATE borrowdetails bd
        INNER JOIN borrow b ON b.borrow_id = bd.borrow_id
        SET bd.borrow_status='returned', bd.date_return=NOW()
        WHERE bd.borrow_details_id='$borrow_details_id' AND b.student_id='$session_student_id' AND bd.borrow_status != 'returned'
    ";
    mysqli_query($con, $query) or die(mysqli_error($con));

    if (mysqli_affected_rows($con) > 0 && $book_id > 0) {
        mysqli_query($con, "UPDATE book SET book_copies = book_copies + 1 WHERE book_id='$book_id'") or die(mysqli_error($con));
    }

    if ($book_id <= 0) {
        header('Location: dashboard.php');
        exit();
    }

    header('Location: return_confirm.php?book_id=' . $book_id);
    exit();
}

die('borrow_details_id is missing');
?>
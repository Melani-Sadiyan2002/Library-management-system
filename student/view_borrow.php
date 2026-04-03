<?php include('header.php'); ?>
<?php include('session.php'); ?>
<?php include('navbar_borrow.php'); ?>
<?php include('dbcon.php'); ?>
<?php $session_student_id = (int)$session_id; ?>

<div class="container">
    <div class="margin-top">
        <div class="row">    
            <div class="span12">        
                <div class="alert alert-danger"><strong>Borrowed Books</strong></div>

                <table cellpadding="0" cellspacing="0" border="0" class="table" id="example">
                    <thead>
                        <tr>
                            <th>Book title</th>
                            <th>Borrower</th>
                            <th>Date Borrow</th>
                            <th>Due Date</th>
                            <th>Date Returned</th>
                            <th>Borrow Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $user_query = mysqli_query($con, "
                            SELECT * FROM borrow
                            LEFT JOIN students ON borrow.student_id = students.student_id
                            LEFT JOIN borrowdetails ON borrow.borrow_id = borrowdetails.borrow_id
                            LEFT JOIN book ON borrowdetails.book_id = book.book_id
                            WHERE borrow.student_id = '$session_student_id'
                            ORDER BY borrow.borrow_id DESC
                        ") or die(mysqli_error($con));

                        while ($row = mysqli_fetch_array($user_query)) {
                            $borrow_details_id = $row['borrow_details_id'];
                        ?>
                            <tr>
                                <td><?php echo $row['book_title']; ?></td>
                                <td><?php echo $row['student_id'] . " - " . $row['firstname'] . " " . $row['lastname']; ?></td>
                                <td><?php echo $row['date_borrow']; ?></td>
                                <td><?php echo $row['due_date']; ?></td>
                                <td><?php echo $row['date_return']; ?></td>
                                <td><?php echo $row['borrow_status']; ?></td>
                                <td>
                                    <a href="return_save.php?borrow_details_id=<?php echo $borrow_details_id; ?>" class="btn-default" onclick="return confirm('Are you sure you want to return this book?');">
                                        Return
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
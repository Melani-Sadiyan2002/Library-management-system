<?php include('header.php'); ?>
<?php include('session.php'); ?>
<?php include('navbar_borrow.php'); ?>
<?php include('dbcon.php'); ?>
<?php $session_student_id = (int)$session_id; ?>

<div class="container">
    <div class="margin-top">
        <div class="row">    
            <div class="span12">        
                <div class="alert alert-danger"><strong>Returned Books</strong></div>

                <table cellpadding="0" cellspacing="0" border="0" class="table" id="example">
                    <div class="pull-right">
                        <a href="" onclick="window.print()" class="btn-default"> Print</a>
                    </div>

                    <thead>
                        <tr>
                            <th>Book title</th>
                            <th>Borrower</th>
                            <th>Borrower ID</th>
                            <th>Date Borrow</th>
                            <th>Due Date</th>
                            <th>Date Returned</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $user_query = mysqli_query($con, "
                            SELECT * FROM borrow
                            LEFT JOIN students ON borrow.student_id = students.student_id
                            LEFT JOIN borrowdetails ON borrow.borrow_id = borrowdetails.borrow_id
                            LEFT JOIN book ON borrowdetails.book_id = book.book_id
                            WHERE borrowdetails.borrow_status = 'returned'
                            AND borrow.student_id = '$session_student_id'
                            ORDER BY borrow.borrow_id DESC
                        ") or die(mysqli_error($con));

                        while ($row = mysqli_fetch_array($user_query)) {
                            $id = $row['borrow_id'];
                            $book_id = $row['book_id'];
                            $borrow_details_id = $row['borrow_details_id'];
                        ?>
                            <tr class="del<?php echo $id; ?>">
                                <td><?php echo $row['book_title']; ?></td>
                                <td><?php echo $row['student_id'] . " - " . $row['firstname'] . " " . $row['lastname']; ?></td>
                                <td><?php echo $row['student_id']; ?></td>
                                <td><?php echo $row['date_borrow']; ?></td>
                                <td><?php echo $row['due_date']; ?></td>
                                <td><?php echo $row['date_return']; ?></td>
                                <td></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <script>
            $(".uniform_on").change(function(){
                var max = 3;
                if ($(".uniform_on:checked").length == max) {
                    $(".uniform_on").attr('disabled', 'disabled');
                    alert('3 Books are allowed per borrow');
                    $(".uniform_on:checked").removeAttr('disabled');
                } else {
                    $(".uniform_on").removeAttr('disabled');
                }
            });
            </script>

        </div>
    </div>
</div>

<?php include('footer.php'); ?>
<?php include('header.php'); ?>
<?php include('session.php'); ?>
<?php include('dbcon.php'); ?>
<?php
$session_student_id = (int)$session_id;

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

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$my_books_notice = isset($_SESSION['my_books_notice']) ? trim((string) $_SESSION['my_books_notice']) : '';
$my_books_notice_type = isset($_SESSION['my_books_notice_type']) ? trim((string) $_SESSION['my_books_notice_type']) : 'success';
unset($_SESSION['my_books_notice']);
unset($_SESSION['my_books_notice_type']);

function lms_my_books_parse_date($value)
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

$my_books_rows = array();

$borrow_query = mysqli_query($con, "
    SELECT
        borrow.date_borrow,
        borrow.due_date,
        borrowdetails.borrow_details_id,
        borrowdetails.date_return,
        borrowdetails.borrow_status,
        borrowdetails.book_id,
        book.book_title
    FROM borrow
    LEFT JOIN borrowdetails ON borrow.borrow_id = borrowdetails.borrow_id
    LEFT JOIN book ON borrowdetails.book_id = book.book_id
    WHERE borrow.student_id = '$session_student_id'
") or die(mysqli_error($con));

while ($borrow_row = mysqli_fetch_assoc($borrow_query)) {
    $my_books_rows[] = array(
        'book_id' => (int)$borrow_row['book_id'],
        'book_title' => $borrow_row['book_title'],
        'date_borrow' => $borrow_row['date_borrow'],
        'due_date' => $borrow_row['due_date'],
        'date_return' => $borrow_row['date_return'],
        'status' => strtolower((string) $borrow_row['borrow_status']),
        'borrow_details_id' => (int) $borrow_row['borrow_details_id'],
        'reservation_id' => 0,
        'is_reserved' => false,
    );
}

$reservation_query = mysqli_query($con, "
    SELECT
        br.reservation_id,
        br.book_id,
        br.reserved_at,
        br.status,
        book.book_title
    FROM book_reservations br
    INNER JOIN book ON br.book_id = book.book_id
    WHERE br.student_id = '$session_student_id' AND br.status = 'reserved'
") or die(mysqli_error($con));

while ($reservation_row = mysqli_fetch_assoc($reservation_query)) {
    $my_books_rows[] = array(
        'book_id' => (int)$reservation_row['book_id'],
        'book_title' => $reservation_row['book_title'],
        'date_borrow' => $reservation_row['reserved_at'],
        'due_date' => '',
        'date_return' => '',
        'status' => 'reserved',
        'borrow_details_id' => 0,
        'reservation_id' => (int) $reservation_row['reservation_id'],
        'is_reserved' => true,
    );
}

usort($my_books_rows, function ($a, $b) {
    $time_a = strtotime((string) $a['date_borrow']);
    $time_b = strtotime((string) $b['date_borrow']);
    if ($time_a === $time_b) {
        return 0;
    }
    return ($time_a > $time_b) ? -1 : 1;
});
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
.books-card {
    background: #fff;
    border-radius: 22px;
    border: 1px solid #e4e8f0;
    box-shadow: 0 12px 28px rgba(16, 24, 40, 0.08);
    padding: 20px;
}
.table-wrap {
    overflow-x: auto;
}
.table th,
.table td {
    vertical-align: middle;
}
.notice-banner {
    margin-bottom: 14px;
    border-radius: 12px;
    padding: 10px 14px;
    font-weight: 700;
}
.notice-banner.success {
    background: #e8f7ed;
    border: 1px solid #b8e4c6;
    color: #1f6b36;
}
.notice-banner.error {
    background: #fde9e9;
    border: 1px solid #efb4b4;
    color: #8a1f1f;
}
.status-pending {
    color: #1d4ed8;
    font-weight: 600;
}
.status-overdue {
    color: #dc2626;
    font-weight: 600;
    background: rgba(220, 38, 38, 0.08);
    padding: 4px 8px;
    border-radius: 4px;
    display: inline-block;
}
.status-reserved {
    color: #16a34a;
    font-weight: 600;
}
.status-returned {
    color: #6b7280;
    font-weight: 600;
}
@media (max-width: 980px) {
    .dashboard-panel {
        flex-direction: column;
    }
    .dashboard-sidebar {
        width: 100%;
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
                <li><a class="active" href="my_books.php"><i class="icon-list"></i> My Books</a></li>
                <li><a href="advance_search.php"><i class="icon-search"></i> Search</a></li>
            </ul>
            <div class="sidebar-footer">
                <a class="logout-btn" href="#logout" data-toggle="modal">Logout</a>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="dashboard-hero">
                <h2>My Books</h2>
                <p>Welcome back, <?php echo htmlspecialchars($account_name); ?>. These are your borrowed books.</p>
            </div>

            <div class="books-card">
                <?php if ($my_books_notice !== '') { ?>
                    <div class="notice-banner <?php echo $my_books_notice_type === 'error' ? 'error' : 'success'; ?>">
                        <?php echo htmlspecialchars($my_books_notice); ?>
                    </div>
                <?php } ?>

                <div class="table-wrap">
                    <table cellpadding="0" cellspacing="0" border="0" class="table table-bordered" id="myBooksTable">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Date Borrow</th>
                                <th>Due Date</th>
                                <th>Date Returned</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $current_time = new DateTime('now');
                            foreach ($my_books_rows as $row) {
                                $borrow_details_id = (int)$row['borrow_details_id'];
                                $reservation_id = (int)$row['reservation_id'];
                                $is_reserved = !empty($row['is_reserved']);
                                $is_returned = isset($row['status']) && strtolower($row['status']) === 'returned';
                                
                                // Check if overdue
                                $is_overdue = false;
                                $status_label = ucfirst($row['status']);
                                $status_class = 'status-' . strtolower($row['status']);
                                
                                if (!$is_reserved && !$is_returned && !empty($row['due_date'])) {
                                    $due_date = lms_my_books_parse_date($row['due_date']);
                                    if ($due_date && $current_time->getTimestamp() > $due_date->getTimestamp()) {
                                        $is_overdue = true;
                                        $status_label = 'Overdue';
                                        $status_class = 'status-overdue';
                                    }
                                }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($row['date_borrow']); ?></td>
                                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['date_return']); ?></td>
                                <td><span class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($status_label); ?></span></td>
                                <td>
                                    <?php if ($is_reserved) { ?>
                                        <a href="borrow.php?book_id=<?php echo $row['book_id']; ?>" class="btn-default">Borrow</a>
                                    <?php } elseif (!$is_returned) { ?>
                                        <a href="return_confirm.php?book_id=<?php echo $row['book_id']; ?>" class="btn-default">Return</a>
                                    <?php } else { ?>
                                        <span style="opacity:0.7;">Returned</span>
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

<?php include('footer.php'); ?>

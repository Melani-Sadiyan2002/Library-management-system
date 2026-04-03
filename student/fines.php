<?php include('header.php'); ?>
<?php include('session.php'); ?>
<?php include('dbcon.php'); ?>
<?php
$session_student_id = (int) $session_id;
$fine_rate = 1.00;
$fine_currency = 'Rs.';

function lms_table_exists($con, $table_name)
{
    $safe_table_name = mysqli_real_escape_string($con, $table_name);
    $result = mysqli_query($con, "SHOW TABLES LIKE '$safe_table_name'");
    return $result && mysqli_num_rows($result) > 0;
}

function lms_parse_date($value)
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

$account_name = 'Account';
$account_query = mysqli_query($con, "SELECT username, firstname, lastname FROM students WHERE student_id='" . (int) $session_id . "' LIMIT 1") or die(mysqli_error($con));
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

if (lms_table_exists($con, 'fine_settings')) {
    $settings_query = mysqli_query($con, "SELECT fine_per_day, currency_label FROM fine_settings ORDER BY setting_id ASC LIMIT 1");
    if ($settings_query && mysqli_num_rows($settings_query) > 0) {
        $settings_row = mysqli_fetch_assoc($settings_query);
        if (isset($settings_row['fine_per_day']) && is_numeric($settings_row['fine_per_day'])) {
            $fine_rate = (float) $settings_row['fine_per_day'];
        }
        if (!empty($settings_row['currency_label'])) {
            $fine_currency = $settings_row['currency_label'];
        }
    }
}

$fine_rows = array();
$total_fine = 0.0;
$overdue_books = 0;

$borrow_query = mysqli_query($con, "
    SELECT
        b.borrow_id,
        b.date_borrow,
        b.due_date AS must_return_date,
        bd.borrow_details_id,
        bd.book_id,
        bd.borrow_status,
        bd.date_return,
        book.book_title
    FROM borrow b
    LEFT JOIN borrowdetails bd ON b.borrow_id = bd.borrow_id
    LEFT JOIN book ON bd.book_id = book.book_id
    WHERE b.student_id = '$session_student_id'
    ORDER BY b.borrow_id DESC, bd.borrow_details_id DESC
") or die(mysqli_error($con));

$today = new DateTime('now');
$has_fines_table = lms_table_exists($con, 'fines');

while ($row = mysqli_fetch_assoc($borrow_query)) {
    $borrowed_date = lms_parse_date($row['date_borrow']);
    $must_return_date = lms_parse_date($row['must_return_date']);
    if (!$must_return_date) {
        continue;
    }

    $borrow_status = isset($row['borrow_status']) ? strtolower(trim($row['borrow_status'])) : 'pending';
    $return_date = lms_parse_date($row['date_return']);
    $reference_date = $borrow_status === 'returned' && $return_date ? $return_date : $today;

    if ($reference_date->getTimestamp() <= $must_return_date->getTimestamp()) {
        continue;
    }

    $days_late = (int) $must_return_date->diff($reference_date)->format('%a');
    $fine_amount = $days_late * $fine_rate;
    $total_fine += $fine_amount;
    $overdue_books++;

    $fine_rows[] = array(
        'borrow_details_id' => (int) $row['borrow_details_id'],
        'book_id' => (int) $row['book_id'],
        'book_title' => $row['book_title'],
        'borrowed_date' => $row['date_borrow'],
        'must_return_date' => $row['must_return_date'],
        'date_return' => $row['date_return'],
        'borrow_status' => $borrow_status,
        'days_late' => $days_late,
        'fine_amount' => $fine_amount,
        'fine_currency' => $fine_currency,
    );

    if ($has_fines_table && (int) $row['borrow_details_id'] > 0) {
        $borrow_details_id = (int) $row['borrow_details_id'];
        $borrow_id = (int) $row['borrow_id'];
        $book_id = (int) $row['book_id'];
        $borrowed_date_sql = mysqli_real_escape_string($con, (string) $row['date_borrow']);
        $date_return_sql = mysqli_real_escape_string($con, (string) $row['date_return']);
        $fine_status = $borrow_status === 'returned' ? 'pending_payment' : 'outstanding';
        $must_return_date_sql = mysqli_real_escape_string($con, (string) $row['must_return_date']);
        mysqli_query($con, "
            INSERT INTO fines (student_id, borrow_id, borrow_details_id, book_id, borrowed_date, must_return_date, return_date, days_late, fine_per_day, fine_amount, fine_status, updated_at)
            VALUES ('$session_student_id', '$borrow_id', '$borrow_details_id', '$book_id', '$borrowed_date_sql', '$must_return_date_sql', '$date_return_sql', '$days_late', '$fine_rate', '$fine_amount', '$fine_status', NOW())
            ON DUPLICATE KEY UPDATE
                student_id = VALUES(student_id),
                borrow_id = VALUES(borrow_id),
                book_id = VALUES(book_id),
                borrowed_date = VALUES(borrowed_date),
                must_return_date = VALUES(must_return_date),
                return_date = VALUES(return_date),
                days_late = VALUES(days_late),
                fine_per_day = VALUES(fine_per_day),
                fine_amount = VALUES(fine_amount),
                fine_status = VALUES(fine_status),
                updated_at = NOW()
        ") or die(mysqli_error($con));
    }
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
    margin: 10px 0 0;
    color: #687287;
}
.summary-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin: 18px 0 22px;
}
.summary-card {
    background: #fff;
    border-radius: 18px;
    border: 1px solid #e4e8f0;
    box-shadow: 0 10px 24px rgba(16, 24, 40, 0.06);
    padding: 18px 20px;
}
.summary-label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: #72809b;
    margin-bottom: 8px;
}
.summary-value {
    font-size: 28px;
    font-weight: 700;
    color: #13233d;
}
.fines-card {
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
.badge-overdue {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 999px;
    background: #ffe2e1;
    color: #a82424;
    font-size: 12px;
    font-weight: 700;
}
.empty-state {
    padding: 28px;
    text-align: center;
    color: #63708a;
    border: 1px dashed #cfd8e6;
    border-radius: 18px;
    background: #f9fbff;
}
@media (max-width: 980px) {
    .dashboard-panel {
        flex-direction: column;
    }
    .dashboard-sidebar {
        width: 100%;
    }
    .summary-grid {
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
                <li><a class="active" href="fines.php"><i class="icon-exchange"></i> Fines</a></li>
                <li><a href="my_books.php"><i class="icon-list"></i> My Books</a></li>
                <li><a href="advance_search.php"><i class="icon-search"></i> Search</a></li>
            </ul>
            <div class="sidebar-footer">
                <a class="logout-btn" href="#logout" data-toggle="modal">Logout</a>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="dashboard-hero">
                <h2>Fines</h2>
                <p>Calculated from each borrowed book's must return date for <?php echo htmlspecialchars($account_name); ?>.</p>
            </div>

            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-label">Overdue Books</div>
                    <div class="summary-value"><?php echo (int) $overdue_books; ?></div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Fine Rate / Day</div>
                    <div class="summary-value"><?php echo htmlspecialchars($fine_currency . ' ' . number_format($fine_rate, 2)); ?></div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Total Fine</div>
                    <div class="summary-value"><?php echo htmlspecialchars($fine_currency . ' ' . number_format($total_fine, 2)); ?></div>
                </div>
            </div>

            <div class="fines-card">
                <?php if (count($fine_rows) > 0) { ?>
                    <div class="table-wrap">
                        <table cellpadding="0" cellspacing="0" border="0" class="table table-bordered" id="finesTable">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Borrowed Date</th>
                                    <th>Must Return Date</th>
                                    <th>Return Date</th>
                                    <th>Days Late</th>
                                    <th>Fine / Day</th>
                                    <th>Total Fine</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fine_rows as $fine_row) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fine_row['book_title']); ?></td>
                                    <td><?php echo htmlspecialchars($fine_row['borrowed_date']); ?></td>
                                    <td><?php echo htmlspecialchars($fine_row['must_return_date']); ?></td>
                                    <td><?php echo htmlspecialchars($fine_row['date_return'] !== '' ? $fine_row['date_return'] : 'Pending'); ?></td>
                                    <td><?php echo (int) $fine_row['days_late']; ?></td>
                                    <td><?php echo htmlspecialchars($fine_row['fine_currency'] . ' ' . number_format($fine_rate, 2)); ?></td>
                                    <td><?php echo htmlspecialchars($fine_row['fine_currency'] . ' ' . number_format($fine_row['fine_amount'], 2)); ?></td>
                                    <td><span class="badge-overdue">Overdue</span></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } else { ?>
                    <div class="empty-state">
                        No overdue books found for this user.
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

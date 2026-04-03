<?php include('header.php'); ?>
<?php include('session.php'); ?>
<?php include('dbcon.php'); ?>
<?php
$book_filter = isset($_GET['filter']) ? strtolower(trim($_GET['filter'])) : 'all';
$book_filter_map = array(
    'all' => array('label' => 'All Books', 'query' => "SELECT b.*, c.classname FROM book b LEFT JOIN category c ON b.category_id = c.category_id ORDER BY b.book_title ASC"),
    'new' => array('label' => 'New Books', 'query' => "SELECT b.*, c.classname FROM book b LEFT JOIN category c ON b.category_id = c.category_id WHERE b.status = 'new' ORDER BY b.book_title ASC"),
    'old' => array('label' => 'Old Books', 'query' => "SELECT b.*, c.classname FROM book b LEFT JOIN category c ON b.category_id = c.category_id WHERE b.status = 'old' ORDER BY b.book_title ASC"),
    'lost' => array('label' => 'Lost Books', 'query' => "SELECT b.*, c.classname FROM book b LEFT JOIN category c ON b.category_id = c.category_id WHERE b.status = 'lost' ORDER BY b.book_title ASC"),
    'damage' => array('label' => 'Damage Books', 'query' => "SELECT b.*, c.classname FROM book b LEFT JOIN category c ON b.category_id = c.category_id WHERE b.status = 'Damage' ORDER BY b.book_title ASC"),
);
if (!isset($book_filter_map[$book_filter])) {
    $book_filter = 'all';
}
$book_filter_label = $book_filter_map[$book_filter]['label'];

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

$reserve_notice = isset($_SESSION['reserve_notice']) ? trim((string) $_SESSION['reserve_notice']) : '';
$reserve_notice_type = isset($_SESSION['reserve_notice_type']) ? trim((string) $_SESSION['reserve_notice_type']) : 'success';
unset($_SESSION['reserve_notice']);
unset($_SESSION['reserve_notice_type']);

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
$reserved_book_ids = array();
$reserved_books_query = mysqli_query($con, "
    SELECT book_id
    FROM book_reservations
    WHERE student_id = '$session_student_id' AND status = 'reserved'
") or die(mysqli_error($con));
while ($reserved_row = mysqli_fetch_assoc($reserved_books_query)) {
    $reserved_book_ids[(int) $reserved_row['book_id']] = true;
}

$borrowed_book_ids = array();
$borrowed_books_query = mysqli_query($con, "
    SELECT bd.book_id
    FROM borrowdetails bd
    INNER JOIN borrow b ON bd.borrow_id = b.borrow_id
    WHERE b.student_id = '$session_student_id' AND bd.borrow_status = 'pending'
") or die(mysqli_error($con));
while ($borrowed_row = mysqli_fetch_assoc($borrowed_books_query)) {
    $borrowed_book_ids[(int) $borrowed_row['book_id']] = true;
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
.dashboard-hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 22px;
}
.dashboard-hero h2 {
    margin: 0;
    font-size: 42px;
    line-height: 1;
    font-weight: 700;
    color: #13233d;
}
.dashboard-hero .eyebrow {
    text-transform: uppercase;
    letter-spacing: 0.18em;
    font-size: 12px;
    font-weight: 700;
    color: #4a6fd0;
    margin-bottom: 10px;
}
.dashboard-hero p {
    margin: 10px 0 0;
    color: #687287;
    font-size: 15px;
}
.books-card {
    background: #fff;
    border-radius: 22px;
    border: 1px solid #e4e8f0;
    box-shadow: 0 12px 28px rgba(16, 24, 40, 0.08);
    padding: 22px;
}
.books-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}
.books-card h3 {
    margin: 0;
    font-size: 24px;
    color: #13233d;
}
.books-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.books-btn {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 700;
    border: 1px solid #d8e0ea;
    background: #fff;
    color: #1b3358;
}
.books-btn.primary {
    background: #1d4ed8;
    border-color: #1d4ed8;
    color: #fff;
}
.book-filter-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}
.book-filter-tabs a {
    padding: 8px 12px;
    border-radius: 999px;
    border: 1px solid #d8e0ea;
    text-decoration: none;
    color: #34445f;
    background: #f8fbff;
}
.book-filter-tabs a.active {
    background: #1d4ed8;
    color: #fff;
    border-color: #1d4ed8;
}
.book-search-wrap {
    margin-bottom: 16px;
}
.book-search-input {
    width: 100%;
    box-sizing: border-box;
    padding: 11px 14px;
    border: 1px solid #d4dce8;
    border-radius: 10px;
    font-size: 14px;
    background: #f9fbff;
}
.books-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
}
.book-name-card {
    border: 1px solid #cfd6e1;
    border-radius: 14px;
    padding: 16px;
    background: linear-gradient(180deg, #f3f4f6 0%, #e7ebf1 100%);
    box-shadow: inset 1px 1px 0 rgba(255, 255, 255, 0.85), inset -2px -2px 5px rgba(165, 172, 184, 0.35), 0 8px 18px rgba(31, 41, 55, 0.08);
    display: flex;
    flex-direction: column;
    min-height: 250px;
}
.book-cover-chip {
    width: 62px;
    height: 62px;
    border-radius: 12px;
    background: linear-gradient(135deg, #d6e4ff, #a7c2ff);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 700;
    color: #1a3f8a;
    margin-bottom: 12px;
}
.book-card-title {
    font-size: 17px;
    font-weight: 700;
    line-height: 1.2;
    color: #172a45;
    margin: 0 0 6px;
}
.book-card-author {
    font-size: 13px;
    color: #5c6f8f;
    margin: 0 0 10px;
}
.book-card-meta {
    font-size: 12px;
    color: #6b7d97;
    line-height: 1.5;
    margin-bottom: 12px;
}
.book-card-actions {
    margin-top: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
}
.book-borrow-btn {
    padding: 8px 14px;
    border-radius: 999px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
    background: #2e65d9;
    color: #fff;
}
.book-reserve-btn {
    padding: 8px 14px;
    border-radius: 999px;
    border: 1px solid #d8e0ea;
    background: #fff;
    color: #1f3358;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}
.book-reserve-btn.disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
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
.icon-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 9px;
    border: 1px solid #dbe2ed;
    background: #fff;
    color: #2f3f58;
    text-decoration: none;
}
.book-empty-state {
    padding: 18px;
    border: 1px dashed #cfd8e8;
    border-radius: 12px;
    background: #f8fbff;
    color: #657793;
    font-size: 14px;
}
@media (max-width: 980px) {
    .dashboard-panel {
        flex-direction: column;
    }
    .dashboard-sidebar {
        width: 100%;
    }
    .dashboard-hero {
        flex-direction: column;
        align-items: flex-start;
    }
    .books-grid {
        grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    }
}
</style>

<div class="container dashboard-shell">
    <div class="dashboard-panel">
        <div class="dashboard-sidebar">
            <div class="dashboard-brand">LMS</div>
            <ul class="dashboard-nav">
                <li><a href="dashboard.php"><i class="icon-home"></i> Dashboard</a></li>
                <li><a class="active" href="books.php"><i class="icon-book"></i> Books</a></li>
                <li><a href="fines.php"><i class="icon-exchange"></i> Fines</a></li>
                <li><a href="my_books.php"><i class="icon-list"></i> My Books</a></li>
                <li><a href="advance_search.php"><i class="icon-search"></i> Search</a></li>
            </ul>
            <div class="sidebar-footer">
                <a class="logout-btn" href="#logout" data-toggle="modal">Logout</a>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="dashboard-hero">
                <div>
                    <div class="eyebrow">Books Catalog</div>
                    <h2>Welcome Back, <?php echo htmlspecialchars($account_name); ?></h2>
                    <p>Manage and review your full library collection from one place.</p>
                </div>
            </div>

            <div class="books-card">
                <?php if ($reserve_notice !== '') { ?>
                    <div class="notice-banner <?php echo $reserve_notice_type === 'error' ? 'error' : 'success'; ?>">
                        <?php echo htmlspecialchars($reserve_notice); ?>
                    </div>
                <?php } ?>

                <div class="books-card-head">
                    <h3>Books List</h3>
                </div>

                <div class="book-filter-tabs">
                    <a class="<?php echo $book_filter === 'all' ? 'active' : ''; ?>" href="books.php">All</a>
                    <a class="<?php echo $book_filter === 'new' ? 'active' : ''; ?>" href="books.php?filter=new">New Books</a>
                    <a class="<?php echo $book_filter === 'old' ? 'active' : ''; ?>" href="books.php?filter=old">Old Books</a>
                    <a class="<?php echo $book_filter === 'lost' ? 'active' : ''; ?>" href="books.php?filter=lost">Lost Books</a>
                    <a class="<?php echo $book_filter === 'damage' ? 'active' : ''; ?>" href="books.php?filter=damage">Damage Books</a>
                </div>

                <div class="book-search-wrap">
                    <input type="text" id="bookSearchInput" class="book-search-input" placeholder="Search books by title, author, category, or ISBN">
                </div>

                <div class="books-grid" id="booksGrid">
                    <?php
                    $user_query = mysqli_query($con, $book_filter_map[$book_filter]['query']) or die(mysqli_error($con));

                    while ($row = mysqli_fetch_array($user_query)) {
                        $id = $row['book_id'];
                        $book_copies = (int) $row['book_copies'];
                        $total = max(0, $book_copies);
                        $is_reserved = isset($reserved_book_ids[(int) $id]);
                        $is_borrowed = isset($borrowed_book_ids[(int) $id]);
                        $can_reserve = $total > 0 && !$is_reserved && !$is_borrowed;

                        $book_title = trim($row['book_title']);
                        $author = trim($row['author']);
                        $category = trim((string)$row['classname']);
                        $isbn = trim((string)$row['isbn']);
                        $search_blob = strtolower($book_title . ' ' . $author . ' ' . $category . ' ' . $isbn);
                        $cover_letter = strtoupper(substr($book_title, 0, 1));
                        if ($cover_letter === '') {
                            $cover_letter = 'B';
                        }
                    ?>
                    <div class="book-name-card" data-search="<?php echo htmlspecialchars($search_blob); ?>">
                        <div class="book-cover-chip"><?php echo htmlspecialchars($cover_letter); ?></div>
                        <h4 class="book-card-title"><?php echo htmlspecialchars($book_title); ?></h4>
                        <p class="book-card-author"><?php echo htmlspecialchars($author); ?></p>
                        <div class="book-card-meta">
                            <div><strong>Category:</strong> <?php echo htmlspecialchars($category); ?></div>
                            <div><strong>ISBN:</strong> <?php echo htmlspecialchars($isbn); ?></div>
                            <div><strong>No. of Copies:</strong> <?php echo (int) $total; ?></div>
                        </div>

                        <?php include('toolttip_edit_delete.php'); ?>

                        <div class="book-card-actions">
                            <?php if ($total > 0) { ?>
                                <a href="borrow.php" class="book-borrow-btn">Borrow</a>
                            <?php } else { ?>
                                <span class="book-borrow-btn" style="opacity:0.6; cursor:not-allowed; pointer-events:none;">Unavailable</span>
                            <?php } ?>

                            <?php if ($can_reserve) { ?>
                                <form method="post" action="reserve_book.php" style="margin:0;">
                                    <input type="hidden" name="book_id" value="<?php echo (int) $id; ?>">
                                    <input type="hidden" name="return_filter" value="<?php echo htmlspecialchars($book_filter); ?>">
                                    <button type="submit" class="book-reserve-btn">Reserve</button>
                                </form>
                            <?php } elseif ($is_reserved) { ?>
                                <span class="book-reserve-btn disabled">Reserved</span>
                            <?php } elseif ($is_borrowed) { ?>
                                <span class="book-reserve-btn disabled">Borrowed</span>
                            <?php } else { ?>
                                <span class="book-reserve-btn disabled">Unavailable</span>
                            <?php } ?>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <div id="bookSearchEmpty" class="book-empty-state" style="display:none; margin-top: 12px;">No books matched your search.</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('bookSearchInput');
    var cards = document.querySelectorAll('#booksGrid .book-name-card');
    var emptyState = document.getElementById('bookSearchEmpty');

    if (!searchInput || cards.length === 0) {
        return;
    }

    searchInput.addEventListener('input', function () {
        var query = searchInput.value.toLowerCase().trim();
        var visibleCount = 0;

        cards.forEach(function (card) {
            var searchText = (card.getAttribute('data-search') || '').toLowerCase();
            var isMatch = query === '' || searchText.indexOf(query) !== -1;
            card.style.display = isMatch ? '' : 'none';
            if (isMatch) {
                visibleCount++;
            }
        });

        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
    });
});
</script>

<?php include('footer.php'); ?>
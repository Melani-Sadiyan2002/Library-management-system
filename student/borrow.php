<?php include('header.php'); ?>
<?php include('session.php'); ?>
<?php include('dbcon.php'); ?>
<?php
$scan_prefill = '';
$auto_due_date = date('d/m/Y', strtotime('+14 days'));

$resolved_student_id = 0;
$resolved_student_fullname = '';
$resolved_student_label = '';

$session_username = isset($_SESSION['username']) ? trim($_SESSION['username']) : '';
$session_username_esc = mysqli_real_escape_string($con, $session_username);
$session_id_int = (int) $session_id;

$resolve_student_sql = "SELECT student_id, student_no, firstname, lastname, username
    FROM students
    WHERE status='active' AND (student_id='$session_id_int'";
if ($session_username !== '') {
    $resolve_student_sql .= " OR username='$session_username_esc'";
}
$resolve_student_sql .= ")
    ORDER BY CASE WHEN username='$session_username_esc' THEN 0 ELSE 1 END
    LIMIT 1";

$resolve_student_query = mysqli_query($con, $resolve_student_sql) or die(mysqli_error($con));
$resolve_student_row = mysqli_fetch_assoc($resolve_student_query);
if ($resolve_student_row) {
    $resolved_student_id = (int) $resolve_student_row['student_id'];
    $resolved_student_fullname = trim($resolve_student_row['firstname'] . ' ' . $resolve_student_row['lastname']);
    if ($resolved_student_fullname === '') {
        $resolved_student_fullname = trim($resolve_student_row['username']);
    }
    $resolved_student_label = $resolved_student_fullname;
}

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
.scan-card {
    background: #fff;
    border-radius: 22px;
    border: 1px solid #e4e8f0;
    box-shadow: 0 12px 28px rgba(16, 24, 40, 0.08);
    padding: 24px;
}
.scan-board {
    width: 56%;
    margin: 0 auto 20px;
    min-height: 180px;
    border: 1px solid #8cb0ff;
    border-radius: 14px;
    background: #d2e2fb;
    text-align: center;
    padding: 20px 16px;
    box-sizing: border-box;
}
.scan-board-idle {
    display: block;
}
.scan-bar {
    font-size: 58px;
    line-height: 0.9;
    letter-spacing: -4px;
    margin-bottom: 12px;
    color: #0c1528;
}
.scan-board h3 {
    margin: 0 0 5px;
    font-size: 28px;
    color: #22324a;
}
.scan-board p {
    margin: 0;
    font-size: 13px;
    color: #5c6f8f;
}
.camera-wrap {
    width: 56%;
    margin: 0 auto 18px;
}
.camera-controls {
    display: flex;
    gap: 10px;
    justify-content: center;
    gap: 10px;
    margin-bottom: 10px;
}
.camera-btn {
    border: 1px solid #b7c7de;
    border-radius: 10px;
    background: #ffffff;
    color: #18365f;
    font-size: 13px;
    font-weight: 700;
    padding: 10px 12px;
    cursor: pointer;
}
.camera-btn.primary {
    background: #1f64dd;
    border-color: #1f64dd;
    color: #ffffff;
}
.camera-btn[disabled] {
    opacity: 0.6;
    cursor: not-allowed;
}
.camera-preview {
    position: relative;
    border: 1px solid #8cb0ff;
    border-radius: 12px;
    background: #0a1730;
    overflow: hidden;
    width: 100%;
    max-width: 100%;
    min-height: 220px;
}
.camera-preview video {
    width: 100%;
    height: 220px;
    object-fit: cover;
    display: block;
}
.camera-preview canvas,
.camera-preview #scannerView,
.camera-preview #scannerView video,
.camera-preview #scannerView canvas {
    width: 100% !important;
    height: 220px !important;
    object-fit: cover;
    display: block;
}
.camera-overlay {
    position: absolute;
    inset: 0;
    pointer-events: none;
    border: 2px solid rgba(102, 165, 255, 0.55);
    box-sizing: border-box;
}
.camera-hint {
    margin-top: 6px;
    font-size: 12px;
    color: #5f7291;
    text-align: center;
}
.scan-fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}
.scan-group label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #334861;
    margin-bottom: 6px;
}
.scan-group input,
.scan-group select {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid #d4dce8;
    border-radius: 10px;
    font-size: 14px;
    padding: 11px 12px;
    background: #f9fbff;
}
.barcode-entry-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 8px;
    align-items: center;
}
.barcode-reset-btn {
    border: 1px solid #2e65d9;
    background: #2e65d9;
    color: #ffffff;
    border-radius: 8px;
    font-size: 9px;
    font-weight: 700;
    height: 26px;
    min-width: 26px;
    padding: 0 4px;
    line-height: 1;
    white-space: nowrap;
    cursor: pointer;
    justify-self: end;
    align-self: center;
}
.scan-actions {
    display: grid;
    grid-template-columns: 1fr;
    gap: 14px;
    margin-top: 16px;
}
.scan-btn {
    border: none;
    border-radius: 10px;
    padding: 12px;
    font-size: 14px;
    font-weight: 700;
}
.scan-btn.confirm {
    background: #2e65d9;
    color: #fff;
}
.scan-btn.clear {
    display: inline-block;
    text-decoration: none;
    text-align: center;
    background: #fff;
    color: #1b3358;
    border: 1px solid #d8e0ea;
}
.scan-warning {
    margin-top: 14px;
    border: 1px solid #f0cf80;
    border-radius: 12px;
    background: #fff6e2;
    color: #7a4a00;
    font-size: 13px;
    font-weight: 700;
    text-align: center;
    padding: 11px 12px;
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
    .scan-board {
        width: 100%;
    }
    .camera-wrap {
        width: 100%;
    }
    .scan-fields,
    .camera-controls,
    .scan-actions {
        grid-template-columns: 1fr;
    }
    .barcode-entry-row {
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
                    <div class="eyebrow">Barcode Borrow</div>
                    <h2>Scan & Confirm Borrow</h2>
                    <p>Welcome back, <?php echo htmlspecialchars($account_name); ?>.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['borrow_success']) && $_SESSION['borrow_success'] === 1) { ?>
                <div class="alert alert-success"><strong>Borrow recorded successfully.</strong></div>
                <?php unset($_SESSION['borrow_success']); ?>
            <?php } ?>
            <?php if (isset($_GET['error'])) { ?>
                <div class="alert alert-danger"><strong><?php echo htmlspecialchars($_GET['error']); ?></strong></div>
            <?php } ?>

            <div class="scan-card">
                <div class="scan-board">
                    <div class="scan-board-idle" id="scanBoardIdle">
                        <div class="scan-bar">||||||||||||</div>
                        <h3>Scan Barcode</h3>
                        <p>Place the barcode under the scanner to read</p>
                    </div>
                    <div class="camera-preview" id="cameraPreview" style="display:none;">
                        <div id="scannerView"></div>
                        <div class="camera-overlay"></div>
                    </div>
                </div>

                <div class="camera-wrap">
                    <div class="camera-controls">
                        <button type="button" class="camera-btn primary" id="openCameraBtn">Open Camera Scan</button>
                        <button type="button" class="camera-btn" id="stopCameraBtn" disabled>Stop Camera</button>
                    </div>
                    <div class="camera-hint">Camera scan works best in Chrome/Edge with HTTPS or localhost.</div>
                </div>

                <form method="post" action="borrow_save.php" id="borrowForm" autocomplete="off">
                    <div class="scan-fields">
                        <div class="scan-group">
                            <label for="student_id">Student Name</label>
                            <?php if ($resolved_student_id > 0) { ?>
                                <input type="hidden" name="student_id" id="student_id" value="<?php echo (int) $resolved_student_id; ?>">
                                <input type="text" value="<?php echo htmlspecialchars($resolved_student_label); ?>" readonly>
                            <?php } else { ?>
                                <select name="student_id" id="student_id" required>
                                    <option value="">Select student</option>
                                    <?php
                                    $student_query = mysqli_query($con, "SELECT student_id, student_no, firstname, lastname FROM students ORDER BY firstname ASC") or die(mysqli_error($con));
                                    while ($student = mysqli_fetch_assoc($student_query)) {
                                    ?>
                                    <option value="<?php echo (int) $student['student_id']; ?>"><?php echo htmlspecialchars(trim($student['firstname'] . ' ' . $student['lastname'])); ?></option>
                                    <?php } ?>
                                </select>
                            <?php } ?>
                        </div>
                        <div class="scan-group">
                            <label for="scan_code">Barcode / ISBN</label>
                            <div class="barcode-entry-row">
                                <input type="text" name="scan_code" id="scan_code" value="<?php echo htmlspecialchars($scan_prefill); ?>" placeholder="Scan barcode here" required autofocus readonly>
                                <button type="button" class="barcode-reset-btn" id="rescanBarcodeBtn" title="Re-enter barcode">↺</button>
                            </div>
                        </div>
                        <div class="scan-group">
                            <label>Scan Date</label>
                            <input type="text" value="<?php echo date('d/m/Y H:i:s'); ?>" readonly>
                        </div>
                        <div class="scan-group">
                            <label>Return Date (+2 Weeks)</label>
                            <input type="text" value="<?php echo $auto_due_date; ?>" readonly>
                        </div>
                    </div>

                    <div class="scan-actions">
                        <button type="submit" class="scan-btn confirm" id="confirmBorrowBtn">Confirm Borrow</button>
                    </div>
                </form>

                <div class="scan-warning">Late returns may incur fines. Please return books on or before the due date.</div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/quagga/dist/quagga.min.js" type="text/javascript"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var borrowForm = document.getElementById('borrowForm');
    var studentField = document.getElementById('student_id');
    var scanField = document.getElementById('scan_code');
    var confirmButton = document.getElementById('confirmBorrowBtn');
    var statusBox = document.getElementById('scanStatus');
    var rescanBarcodeBtn = document.getElementById('rescanBarcodeBtn');
    var openCameraBtn = document.getElementById('openCameraBtn');
    var stopCameraBtn = document.getElementById('stopCameraBtn');
    var cameraPreview = document.getElementById('cameraPreview');
    var scanBoardIdle = document.getElementById('scanBoardIdle');
    var scannerView = document.getElementById('scannerView');

    if (!borrowForm || !studentField || !scanField || !confirmButton) {
        return;
    }

    var submitTimer = null;
    var lastInputTime = 0;
    var scannerOpen = false;
    var scanLocked = false;
    var lastDetectedCode = '';
    var lastDetectedAt = 0;
    var cameraFillInProgress = false;
    var keyboardScanBuffer = '';
    var keyboardScanLastAt = 0;

    function updateStatus(message, type) {
        if (!statusBox) {
            return;
        }
        statusBox.textContent = message;
        statusBox.classList.remove('ok', 'error');
        if (type === 'ok' || type === 'error') {
            statusBox.classList.add(type);
        }
    }

    function normalizeScan(value) {
        return value.replace(/[\r\n\t]/g, '').trim();
    }

    function canSubmit() {
        var normalized = normalizeScan(scanField.value);
        scanField.value = normalized;

        if (studentField.value === '') {
            updateStatus('Please select student first before scanning.', 'error');
            studentField.focus();
            return false;
        }

        if (normalized === '') {
            updateStatus('Scanner ready. Waiting for barcode input.', '');
            scanField.focus();
            return false;
        }

        return true;
    }

    function submitBorrow(source) {
        if (borrowForm.dataset.submitting === '1') {
            return;
        }

        if (!canSubmit()) {
            return;
        }

        borrowForm.dataset.submitting = '1';
        confirmButton.disabled = true;
        confirmButton.textContent = 'Processing...';
        updateStatus('Barcode captured. Processing borrow (' + source + ').', 'ok');
        borrowForm.submit();
    }

    function stopCameraScan() {
        if (typeof Quagga !== 'undefined') {
            Quagga.offDetected();
            if (scannerOpen) {
                Quagga.stop();
            }
        }
        scannerOpen = false;
        scanLocked = false;
        if (scannerView) {
            scannerView.innerHTML = '';
        }
        if (cameraPreview) {
            cameraPreview.style.display = 'none';
        }
        if (scanBoardIdle) {
            scanBoardIdle.style.display = 'block';
        }
        if (openCameraBtn) {
            openCameraBtn.disabled = false;
        }
        if (stopCameraBtn) {
            stopCameraBtn.disabled = true;
        }
    }

    function processDetectedCode(rawValue) {
        var code = normalizeScan(rawValue || '');
        if (code === '') {
            return;
        }

        var now = Date.now();
        if (code === lastDetectedCode && (now - lastDetectedAt) < 1600) {
            return;
        }
        lastDetectedCode = code;
        lastDetectedAt = now;

        cameraFillInProgress = true;
        scanField.value = code;
        scanField.dispatchEvent(new Event('input', { bubbles: true }));
        window.setTimeout(function () {
            cameraFillInProgress = false;
        }, 120);

        if (studentField.value !== '') {
            updateStatus('Code filled: ' + code + '. Ready to confirm borrow.', 'ok');
        } else {
            updateStatus('Code filled: ' + code + '. Select student then confirm.', 'ok');
            studentField.focus();
        }

        scanLocked = true;
        window.setTimeout(function () {
            scanLocked = false;
        }, 500);
    }

    function processKeyboardScannedCode(code) {
        var normalized = normalizeScan(code || '');
        if (normalized === '') {
            return;
        }

        scanField.value = normalized;
        updateStatus('Code filled: ' + normalized + '. Ready to confirm borrow.', 'ok');
    }

    function openCameraScan() {
        if (scannerOpen) {
            return;
        }
        if (!scannerView) {
            updateStatus('Scanner view is not available on this page.', 'error');
            return;
        }
        if (typeof Quagga === 'undefined') {
            updateStatus('Scanner library failed to load. Check internet connection and refresh.', 'error');
            return;
        }

        scannerOpen = true;
        scanLocked = false;
        cameraPreview.style.display = 'block';
        if (scanBoardIdle) {
            scanBoardIdle.style.display = 'none';
        }
        openCameraBtn.disabled = true;
        stopCameraBtn.disabled = false;
        scannerView.innerHTML = '';

        Quagga.init({
            inputStream: {
                type: 'LiveStream',
                target: scannerView,
                constraints: {
                    facingMode: { ideal: 'environment' },
                    width: { min: 640, ideal: 1280 },
                    height: { min: 480, ideal: 720 }
                }
            },
            locator: {
                patchSize: 'large',
                halfSample: false
            },
            locate: true,
            frequency: 10,
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
                ],
                multiple: false
            }
        }, function (err) {
            if (err) {
                updateStatus('Camera access failed or permission denied. Please allow camera and retry.', 'error');
                stopCameraScan();
                return;
            }

            Quagga.start();
            updateStatus('Camera is live. Point barcode inside the frame.', 'ok');
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
            processDetectedCode(code);
        });
    }

    borrowForm.addEventListener('submit', function (event) {
        if (borrowForm.dataset.submitting === '1') {
            return;
        }
        if (!canSubmit()) {
            event.preventDefault();
            return;
        }

        borrowForm.dataset.submitting = '1';
        confirmButton.disabled = true;
        confirmButton.textContent = 'Processing...';
        updateStatus('Submitting borrow request...', 'ok');
    });

    scanField.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === 'Tab') {
            event.preventDefault();
            submitBorrow('scanner-enter');
        }
    });

    scanField.addEventListener('beforeinput', function (event) {
        event.preventDefault();
    });

    scanField.addEventListener('paste', function (event) {
        event.preventDefault();
    });

    document.addEventListener('keydown', function (event) {
        if (event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        var key = event.key;
        var now = Date.now();
        var gap = now - keyboardScanLastAt;

        if (gap > 120) {
            keyboardScanBuffer = '';
        }

        if (key === 'Enter') {
            if (keyboardScanBuffer.length >= 6) {
                event.preventDefault();
                processKeyboardScannedCode(keyboardScanBuffer);
            }
            keyboardScanBuffer = '';
            keyboardScanLastAt = now;
            return;
        }

        if (key.length === 1 && /[0-9A-Za-z\-]/.test(key)) {
            keyboardScanBuffer += key;
            keyboardScanLastAt = now;
        }
    });

    scanField.addEventListener('input', function () {
        if (cameraFillInProgress) {
            return;
        }

        var now = Date.now();
        var delta = now - lastInputTime;
        lastInputTime = now;

        if (submitTimer) {
            clearTimeout(submitTimer);
        }

        var normalized = normalizeScan(scanField.value);
        if (normalized === '') {
            updateStatus('Scanner ready. Waiting for barcode input.', '');
            return;
        }

        if (normalized.length >= 6 && delta > 0 && delta < 45) {
            updateStatus('Scanner input detected. Auto-processing...', 'ok');
            submitTimer = setTimeout(function () {
                submitBorrow('scanner-auto');
            }, 140);
        } else {
            updateStatus('Code detected. Press Enter to confirm scan.', '');
        }
    });

    studentField.addEventListener('change', function () {
        var normalized = normalizeScan(scanField.value);
        if (normalized !== '') {
            updateStatus('Student selected. Processing scanned barcode...', 'ok');
            submitBorrow('student-selected');
        } else {
            updateStatus('Student selected. Ready to scan barcode.', '');
            scanField.focus();
        }
    });

    scanField.addEventListener('blur', function () {
        setTimeout(function () {
            if (borrowForm.dataset.submitting === '1') {
                return;
            }
            if (document.activeElement !== studentField && document.activeElement !== confirmButton) {
                scanField.focus();
            }
        }, 120);
    });

    scanField.value = '';
    updateStatus('Scanner ready. Select a student, then scan barcode.', '');

    if (openCameraBtn) {
        openCameraBtn.addEventListener('click', openCameraScan);
    }
    if (rescanBarcodeBtn) {
        rescanBarcodeBtn.addEventListener('click', function () {
            if (submitTimer) {
                clearTimeout(submitTimer);
            }
            scanField.value = '';
            lastDetectedCode = '';
            lastDetectedAt = 0;
            scanLocked = false;
            borrowForm.dataset.submitting = '0';
            confirmButton.disabled = false;
            confirmButton.textContent = 'Confirm Borrow';
            if (scannerOpen) {
                updateStatus('Barcode cleared. Camera ready for next scan.', '');
            } else {
                updateStatus('Barcode cleared. Scan or type a new barcode.', '');
                scanField.focus();
            }
        });
    }
    if (stopCameraBtn) {
        stopCameraBtn.addEventListener('click', function () {
            stopCameraScan();
            updateStatus('Camera stopped. Ready for scanner input.', '');
        });
    }
    window.addEventListener('beforeunload', stopCameraScan);
});
</script>

<?php include('footer.php'); ?>
<?php
include('dbcon.php');

$student_no = '';
$username = '';
$firstname = '';
$lastname = '';
$gender = '';
$address = '';
$contact = '';
$nic_number = '';
$nic_front_path = '';
$nic_back_path = '';
$exist = '';
$a = '';
$upload_error = '';

function upload_nic_image($file_key, $target_dir)
{
	if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
		return array(false, 'Please upload both NIC images.', '');
	}

	$allowed_ext = array('jpg', 'jpeg', 'png', 'webp');
	$original_name = $_FILES[$file_key]['name'];
	$tmp_name = $_FILES[$file_key]['tmp_name'];
	$ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

	if (!in_array($ext, $allowed_ext, true)) {
		return array(false, 'Allowed image formats: JPG, JPEG, PNG, WEBP.', '');
	}

	if (!is_dir($target_dir) && !mkdir($target_dir, 0777, true)) {
		return array(false, 'Failed to create upload directory.', '');
	}

	$new_name = $file_key . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
	$destination = $target_dir . DIRECTORY_SEPARATOR . $new_name;

	if (!move_uploaded_file($tmp_name, $destination)) {
		return array(false, 'Failed to upload NIC image.', '');
	}

	return array(true, '', 'upload/nic/' . $new_name);
}

function get_existing_nic_path($posted_path)
{
	if (!is_string($posted_path) || $posted_path === '') {
		return '';
	}

	$normalized = str_replace('\\', '/', trim($posted_path));
	if (strpos($normalized, 'upload/nic/') !== 0) {
		return '';
	}

	if (preg_match('/^[A-Za-z0-9_\/.\-]+$/', $normalized) !== 1) {
		return '';
	}

	$abs_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
	if (!is_file($abs_path)) {
		return '';
	}

	return $normalized;
}

function upload_or_reuse_nic_image($file_key, $target_dir, $existing_path, $label)
{
	$safe_existing = get_existing_nic_path($existing_path);

	if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
		return upload_nic_image($file_key, $target_dir);
	}

	if ($safe_existing !== '') {
		return array(true, '', $safe_existing);
	}

	return array(false, 'Please upload NIC ' . $label . ' image.', '');
}

function normalize_nic_value($nic)
{
	$nic = strtoupper($nic);
	$nic = preg_replace('/[^A-Z0-9]/', '', $nic);
	return $nic;
}

function normalize_ocr_nic_text($text)
{
	if (!is_string($text) || $text === '') {
		return '';
	}

	$text = strtoupper($text);
	// Use only conservative substitutions to avoid corrupting valid NIC digits.
	$text = strtr($text, array(
		'O' => '0',
		'Q' => '0',
		'I' => '1',
		'L' => '1',
		'|' => '1',
	));

	return preg_replace('/[^A-Z0-9VX]/', '', $text);
}

function get_nic_from_ocr_text($text)
{
	if (!is_string($text) || trim($text) === '') {
		return '';
	}

	$raw = strtoupper($text);

	// 1) Prefer direct matches from raw OCR text with optional separators.
	if (preg_match('/\b(\d{12})\b/', $raw, $match)) {
		return $match[1];
	}
	if (preg_match('/\b(\d{9}[VX])\b/i', $raw, $match)) {
		return strtoupper($match[1]);
	}
	if (preg_match('/\b(\d{5}[\-\s]?\d{4}[\-\s]?[VX])\b/i', $raw, $match)) {
		return strtoupper(preg_replace('/[^0-9VX]/i', '', $match[1]));
	}

	// 2) Then try cleaned text with conservative OCR normalization.
	$normalized = normalize_ocr_nic_text($raw);
	if (preg_match('/\d{12}/', $normalized, $match)) {
		return $match[0];
	}
	if (preg_match('/\d{9}[VX]/', $normalized, $match)) {
		return strtoupper($match[0]);
	}

	return '';
}

function get_tesseract_binary_path()
{
	$candidates = array(
		getenv('TESSERACT_PATH'),
		'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
		'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
		'tesseract',
	);

	foreach ($candidates as $candidate) {
		if (!is_string($candidate) || $candidate === '') {
			continue;
		}

		if ($candidate === 'tesseract' || is_file($candidate)) {
			return $candidate;
		}
	}

	return 'tesseract';
}

function build_tesseract_command($image_abs_path, $psm, $use_whitelist)
{
	$binary = get_tesseract_binary_path();
	$escaped_binary = $binary === 'tesseract' ? $binary : escapeshellarg($binary);
	$escaped_path = escapeshellarg($image_abs_path);
	$error_redirect = stripos(PHP_OS, 'WIN') === 0 ? '2>NUL' : '2>/dev/null';
	$command = $escaped_binary . ' ' . $escaped_path . ' stdout -l eng --psm ' . (int)$psm;

	if ($use_whitelist) {
		$command .= ' -c tessedit_char_whitelist=0123456789VXvx';
	}

	return $command . ' ' . $error_redirect;
}

function run_tesseract_ocr($image_abs_path)
{
	$configs = array(
		array(6, true),
		array(11, true),
		array(6, false),
	);

	foreach ($configs as $config) {
		$cmd = build_tesseract_command($image_abs_path, $config[0], $config[1]);
		$output = @shell_exec($cmd);

		if (is_string($output) && trim($output) !== '') {
			return $output;
		}
	}

	return '';
}

function ocr_text_preview($text)
{
	if (!is_string($text) || trim($text) === '') {
		return '[empty]';
	}

	$text = preg_replace('/\s+/', ' ', trim($text));
	return substr($text, 0, 140);
}

function write_ocr_debug_log($front_path, $back_path, $front_text, $back_text)
{
	$log_dir = __DIR__ . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'nic';
	if (!is_dir($log_dir)) {
		@mkdir($log_dir, 0777, true);
	}

	$log_file = $log_dir . DIRECTORY_SEPARATOR . 'ocr_debug.log';
	$lines = array(
		'[' . date('Y-m-d H:i:s') . '] OCR failed to extract NIC',
		'Front image: ' . $front_path,
		'Back image: ' . $back_path,
		'Front OCR preview: ' . ocr_text_preview($front_text),
		'Back OCR preview: ' . ocr_text_preview($back_text),
		"----",
	);

	@file_put_contents($log_file, implode(PHP_EOL, $lines) . PHP_EOL, FILE_APPEND);
}

function extract_nic_from_images($front_path, $back_path, &$ocr_debug = array())
{
	$front_text = run_tesseract_ocr($front_path);
	$front_nic = get_nic_from_ocr_text($front_text);
	$ocr_debug = array(
		'front_text' => $front_text,
		'back_text' => '',
		'front_nic' => $front_nic,
		'back_nic' => '',
	);
	if ($front_nic !== '') {
		return $front_nic;
	}

	$back_text = run_tesseract_ocr($back_path);
	$back_nic = get_nic_from_ocr_text($back_text);
	$ocr_debug['back_text'] = $back_text;
	$ocr_debug['back_nic'] = $back_nic;
	if ($back_nic !== '') {
		return $back_nic;
	}

	$combined_nic = get_nic_from_ocr_text($front_text . "\n" . $back_text);
	return $combined_nic;
}

function extract_nic_candidates_from_text($text)
{
	$candidates = array();
	if (!is_string($text) || trim($text) === '') {
		return $candidates;
	}

	$raw = strtoupper($text);
	$normalized = normalize_ocr_nic_text($raw);

	if (preg_match_all('/\d{12}/', $raw, $m1)) {
		$candidates = array_merge($candidates, $m1[0]);
	}
	if (preg_match_all('/\d{9}[VX]/', $raw, $m2)) {
		$candidates = array_merge($candidates, $m2[0]);
	}
	if (preg_match_all('/\d{12}/', $normalized, $m3)) {
		$candidates = array_merge($candidates, $m3[0]);
	}
	if (preg_match_all('/\d{9}[VX]/', $normalized, $m4)) {
		$candidates = array_merge($candidates, $m4[0]);
	}

	$clean = array();
	foreach ($candidates as $c) {
		$c = normalize_nic_value($c);
		if ($c !== '') {
			$clean[] = $c;
		}
	}

	return array_values(array_unique($clean));
}

function entered_nic_matches_ocr($entered_nic, $front_text, $back_text, $extracted_nic = '')
{
	$entered_nic = normalize_nic_value($entered_nic);
	if ($entered_nic === '') {
		return false;
	}

	$front_norm = normalize_ocr_nic_text((string)$front_text);
	$back_norm = normalize_ocr_nic_text((string)$back_text);
	$combined_norm = $front_norm . $back_norm;

	if (strpos($combined_norm, $entered_nic) !== false) {
		return true;
	}

	$match_count = 0;
	$cursor = 0;
	$combined_len = strlen($combined_norm);
	for ($i = 0, $n = strlen($entered_nic); $i < $n; $i++) {
		$ch = $entered_nic[$i];
		while ($cursor < $combined_len && $combined_norm[$cursor] !== $ch) {
			$cursor++;
		}
		if ($cursor < $combined_len) {
			$match_count++;
			$cursor++;
		}
	}

	$required_matches = max(strlen($entered_nic) - 2, 8);
	if ($match_count >= $required_matches) {
		return true;
	}

	$candidates = array();
	$candidates = array_merge($candidates, extract_nic_candidates_from_text((string)$front_text));
	$candidates = array_merge($candidates, extract_nic_candidates_from_text((string)$back_text));
	$candidates = array_merge($candidates, extract_nic_candidates_from_text((string)$front_text . "\n" . (string)$back_text));
	if ($extracted_nic !== '') {
		$candidates[] = normalize_nic_value($extracted_nic);
	}
	$candidates = array_values(array_unique($candidates));

	foreach ($candidates as $candidate) {
		if ($candidate === $entered_nic) {
			return true;
		}

		if (strlen($entered_nic) === 10 && strlen($candidate) === 10) {
			if (substr($entered_nic, 0, 9) === substr($candidate, 0, 9)) {
				$last_e = substr($entered_nic, 9, 1);
				$last_c = substr($candidate, 9, 1);
				if (($last_e === 'V' || $last_e === 'X') && ($last_c === 'V' || $last_c === 'X')) {
					return true;
				}
			}
		}

		if (strlen($entered_nic) === strlen($candidate) && levenshtein($entered_nic, $candidate) <= 2) {
			return true;
		}
	}

	return false;
}

if (isset($_POST['submit'])) {
	$student_no = trim($_POST['student_no']);
	$firstname = trim($_POST['firstname']);
	$lastname = trim($_POST['lastname']);
	$gender = trim($_POST['gender']);
	$address = trim($_POST['address']);
	$contact = preg_replace('/\D+/', '', trim($_POST['contact']));
	$username = $firstname;
	$password = trim($_POST['password']);
	$cpassword = trim($_POST['cpassword']);
	$nic_number = trim($_POST['nic_number']);
	$nic_front_path = get_existing_nic_path(isset($_POST['nic_front_existing']) ? $_POST['nic_front_existing'] : '');
	$nic_back_path = get_existing_nic_path(isset($_POST['nic_back_existing']) ? $_POST['nic_back_existing'] : '');

	$student_no_esc = mysqli_real_escape_string($con, $student_no);
	$username_esc = mysqli_real_escape_string($con, $username);
	$column_result = mysqli_query($con, "SHOW COLUMNS FROM students");
	$columns = array();
	if ($column_result) {
		while ($col = mysqli_fetch_assoc($column_result)) {
			$columns[] = strtolower($col['Field']);
		}
	}
	$has_username_column = in_array('username', $columns, true);

	$check_query = mysqli_query($con, "SELECT * FROM students WHERE student_no='$student_no_esc'") or die(mysqli_error($con));
	$count = mysqli_num_rows($check_query);
	$username_count = 0;
	if ($has_username_column) {
		$username_query = mysqli_query($con, "SELECT * FROM students WHERE username='$username_esc'") or die(mysqli_error($con));
		$username_count = mysqli_num_rows($username_query);
	}

	if ($count > 0) {
		$exist = 'ID Number already exists!';
	} elseif ($username_count > 0) {
		$exist = 'First name already exists as username!';
	} elseif ($firstname === '' || $lastname === '' || $gender === '' || $address === '' || $contact === '') {
		$exist = 'Please fill First Name, Last Name, Gender, Address, and Contact Number.';
	} elseif (!preg_match('/^\d{10,12}$/', $contact)) {
		$exist = 'Contact Number must contain only digits and be 10 to 12 characters long.';
	}

	if ($cpassword != $password) {
		$a = 'Password do not Match';
	}

	if ($count == 0 && $username_count == 0 && $a == '' && $exist == '') {
		$nic_upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'nic';
		list($front_ok, $front_error, $nic_front_path) = upload_or_reuse_nic_image('nic_front', $nic_upload_dir, $nic_front_path, 'front');
		list($back_ok, $back_error, $nic_back_path) = upload_or_reuse_nic_image('nic_back', $nic_upload_dir, $nic_back_path, 'back');

		if (!$front_ok) {
			$upload_error = $front_error;
		} elseif (!$back_ok) {
			$upload_error = $back_error;
		} else {
			$nic_front_abs = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $nic_front_path);
			$nic_back_abs = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $nic_back_path);
			$ocr_debug = array();
			$extracted_nic = extract_nic_from_images($nic_front_abs, $nic_back_abs, $ocr_debug);
			$entered_nic_normalized = normalize_nic_value($nic_number);
			$entered_matches_ocr = entered_nic_matches_ocr($entered_nic_normalized, $ocr_debug['front_text'], $ocr_debug['back_text'], $extracted_nic);

			if (!$entered_matches_ocr) {
				write_ocr_debug_log($nic_front_abs, $nic_back_abs, $ocr_debug['front_text'], $ocr_debug['back_text']);
				$upload_error = 'Entered NIC number does not match the NIC image. OCR front preview: ' . ocr_text_preview($ocr_debug['front_text']) . '. Please upload a clearer image.';
			} else {
			$student_no_esc = mysqli_real_escape_string($con, $student_no);
			$username_esc = mysqli_real_escape_string($con, $username);
			$firstname_esc = mysqli_real_escape_string($con, $firstname);
			$lastname_esc = mysqli_real_escape_string($con, $lastname);
			$gender_esc = mysqli_real_escape_string($con, $gender);
			$address_esc = mysqli_real_escape_string($con, $address);
			$contact_esc = mysqli_real_escape_string($con, $contact);
			$password_esc = mysqli_real_escape_string($con, $password);
			$nic_number_esc = mysqli_real_escape_string($con, $entered_nic_normalized);
			$nic_front_esc = mysqli_real_escape_string($con, $nic_front_path);
			$nic_back_esc = mysqli_real_escape_string($con, $nic_back_path);

			$column_result = mysqli_query($con, "SHOW COLUMNS FROM students");
			$columns = array();
			if ($column_result) {
				while ($col = mysqli_fetch_assoc($column_result)) {
					$columns[] = strtolower($col['Field']);
				}
			}
			$has_username_column = in_array('username', $columns, true);

			$insert_columns = array('student_no', 'password', 'photo', 'status');
			$insert_values = array("'$student_no_esc'", "'$password_esc'", "'$nic_front_esc'", "'active'");

			if ($has_username_column) {
				$insert_columns[] = 'username';
				$insert_values[] = "'$username_esc'";
			}

			if (in_array('firstname', $columns, true)) {
				$insert_columns[] = 'firstname';
				$insert_values[] = "'$firstname_esc'";
			}
			if (in_array('lastname', $columns, true)) {
				$insert_columns[] = 'lastname';
				$insert_values[] = "'$lastname_esc'";
			}
			if (in_array('course', $columns, true)) {
				$insert_columns[] = 'course';
				$insert_values[] = "''";
			}
			if (in_array('gender', $columns, true)) {
				$insert_columns[] = 'gender';
				$insert_values[] = "'$gender_esc'";
			}
			if (in_array('address', $columns, true)) {
				$insert_columns[] = 'address';
				$insert_values[] = "'$address_esc'";
			}
			if (in_array('contact', $columns, true)) {
				$insert_columns[] = 'contact';
				$insert_values[] = "'$contact_esc'";
			}
			if (in_array('nic_number', $columns, true)) {
				$insert_columns[] = 'nic_number';
				$insert_values[] = "'$nic_number_esc'";
			}
			if (in_array('nic_front', $columns, true)) {
				$insert_columns[] = 'nic_front';
				$insert_values[] = "'$nic_front_esc'";
			}
			if (in_array('nic_back', $columns, true)) {
				$insert_columns[] = 'nic_back';
				$insert_values[] = "'$nic_back_esc'";
			}

			$insert_sql = "INSERT INTO students (" . implode(', ', $insert_columns) . ") VALUES (" . implode(', ', $insert_values) . ")";
			mysqli_query($con, $insert_sql) or die(mysqli_error($con));

			echo "<script type='text/javascript'>window.location='success.php';</script>";
			exit();
			}
		}
	}
}
?>
<form method="post" enctype="multipart/form-data">
	<div class="span5">
		<div class="form-horizontal">
			<div class="control-group">
				<label class="control-label" for="inputFirstname">First Name:</label>
				<div class="controls">
					<input type="text" id="inputFirstname" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>" placeholder="First Name" required>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="inputLastname">Last Name:</label>
				<div class="controls">
					<input type="text" id="inputLastname" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>" placeholder="Last Name" required>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="inputGender">Gender:</label>
				<div class="controls">
					<select id="inputGender" name="gender" required>
						<option value="">Select Gender</option>
						<option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
						<option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
					</select>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="inputAddress">Address:</label>
				<div class="controls">
					<input type="text" id="inputAddress" name="address" value="<?php echo htmlspecialchars($address); ?>" placeholder="Address" required>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="inputContact">Contact Number:</label>
				<div class="controls">
					<input type="text" id="inputContact" name="contact" value="<?php echo htmlspecialchars($contact); ?>" placeholder="Contact Number" minlength="10" maxlength="12" pattern="\d{10,12}" inputmode="numeric" required>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="inputEmail">Student No:</label>
				<div class="controls">
					<input type="text" id="inputEmail" name="student_no" value="<?php echo htmlspecialchars($student_no); ?>" placeholder="Student No" required>
					<?php if ($exist != '') { ?><span class="label label-important"><?php echo $exist; ?></span><?php } ?>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="inputPassword">Password</label>
				<div class="controls">
					<input type="password" name="password" placeholder="Password" required>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="inputPassword">Confirm Password</label>
				<div class="controls">
					<input type="password" name="cpassword" placeholder="Confirm Password" required>
					<?php if ($a != '') { ?><span class="label label-important"><?php echo $a; ?></span><?php } ?>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="inputNic">NIC Number</label>
				<div class="controls">
					<input type="text" id="inputNic" name="nic_number" value="<?php echo htmlspecialchars($nic_number); ?>" placeholder="Enter your NIC Number" required>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="nicFront">NIC Front</label>
				<div class="controls">
					<input type="file" id="nicFront" name="nic_front" accept="image/*" <?php echo $nic_front_path === '' ? 'required' : ''; ?>>
					<input type="hidden" name="nic_front_existing" value="<?php echo htmlspecialchars($nic_front_path); ?>">
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="nicBack">NIC Back</label>
				<div class="controls">
					<input type="file" id="nicBack" name="nic_back" accept="image/*" <?php echo $nic_back_path === '' ? 'required' : ''; ?>>
					<input type="hidden" name="nic_back_existing" value="<?php echo htmlspecialchars($nic_back_path); ?>">
					<?php if ($upload_error != '') { ?><span class="label label-important"><?php echo $upload_error; ?></span><?php } ?>
				</div>
			</div>

			<div class="control-group">
				<div class="controls">
					<button name="submit" type="submit" class="btn btn-info"><i class="icon-signin icon-large"></i>&nbsp;Confirm</button>
				</div>
			</div>
		</div>
	</div>
</form>
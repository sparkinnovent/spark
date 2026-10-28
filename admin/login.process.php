<?php
session_start();
require_once '../security.php';

if (!empty($_POST["login"])) {

	require_once '../Dbcontroller.php';
	require_once '../Util.php';
	require_once '../Notify.php';

	csrf_verify();

	$db_handle = new DBController();
	$util = new Util();

	$useremail = trim((string)($_POST["member_email"] ?? ''));

	/*
	 * FIX H2 — the plaintext password was passed through clean()
	 * (mysqli_real_escape_string) before password_verify(). Any password
	 * containing ' " or \ was therefore compared in escaped form and could
	 * never authenticate. The password is never placed in SQL, so it must not
	 * be escaped at all.
	 */
	$password = (string)($_POST["member_password"] ?? '');

	if ($useremail === '' || $password === '') {
		Notify::set("Invalid Login. Try again later.", "danger");
		$util->redirect("./login.php");
	}

	// FIX H3 — look the account up with a bound parameter, then test the row
	// before touching it. The original called password_verify() on the result
	// of mysqli_fetch_assoc() even when no row matched, which raised
	// "Trying to access array offset on null" and a deprecation on PHP 8.
	$user = db_one(
		$db_handle->link(),
		"SELECT * FROM members WHERE member_email = ? LIMIT 1",
		[$useremail]
	);

	$isAuthenticated = false;
	if ($user !== null && !empty($user["member_password"])) {
		$isAuthenticated = password_verify($password, (string)$user["member_password"]);
	}

	/*
	 * Re-hash on success if the cost factor has moved on. Harmless now, saves a
	 * migration later.
	 */
	if ($isAuthenticated && password_needs_rehash((string)$user["member_password"], PASSWORD_DEFAULT)) {
		$db_handle->prepareAndExecute(
			"UPDATE members SET member_password = ? WHERE member_id = ?",
			[password_hash($password, PASSWORD_DEFAULT), (int)$user["member_id"]]
		);
	}

	if ($isAuthenticated) {

		// FIX — session fixation. Hand out a new ID once the credentials check out.
		session_regenerate_id(true);

		$currentDateTime = now_my();
		$user_id = (int)$user["member_id"];

		$lastSignIn = "Not Available";
		if (!empty($user["current_signin"])) {
			$lastSignIn = $user['current_signin'];
		}

		$db_handle->prepareAndExecute(
			"UPDATE members SET last_signin = ?, current_signin = ? WHERE member_id = ?",
			[$lastSignIn === "Not Available" ? null : $lastSignIn, $currentDateTime, $user_id]
		);

		$photo = !empty($user["photo"]) ? $user["photo"] : 'avatar.png';

		$userarray = array($user_id => array(
			'name'           => $user["member_name"],
			'id'             => $user_id,
			'level'          => (int)$user["member_level"],
			'email'          => $user["member_email"],
			'photo'          => $photo,
			'last_signin'    => $lastSignIn,
			'current_signin' => $currentDateTime
		));

		$_SESSION["member_id"] = $user_id;
		$_SESSION["user"] = $userarray;

		// New session, new CSRF token.
		unset($_SESSION['csrf_token']);

		if (!empty($user["photo"])) {
			Notify::set("Hi! Welcome again. Please use this system wisely and if you have any issue please contact me, TQ =)", "success");
			$util->redirect("./secure.php");
		} else {
			Notify::set("Hi! Welcome again. Please update your profile photo. TQ =)", "success");
			$util->redirect("./profile-update.php");
		}
	}

	// Deliberately identical message for "no such account" and "wrong password".
	Notify::set("Invalid Login. Try again later.", "danger");
	$util->redirect("./login.php");
}

<?php
session_start();
require_once './security.php';

if (!empty($_POST["project_signin"])) {

	require_once './Dbcontroller.php';
	require_once './Util.php';
	require_once './Notify.php';

	$db_handle = new Dbcontroller();
	$util = new Util();

	csrf_verify();

	/*
	 * FIX — the reCAPTCHA secret was hardcoded here and the secret key shipped
	 * in the source. It now comes from config.php. The call has a timeout, uses
	 * POST, and treats a transport failure as a failure instead of silently
	 * letting the request through on a null response.
	 */
	$recaptcha_secret = defined('RECAPTCHA_SECRET') ? (string)RECAPTCHA_SECRET : '';
	$recaptcha_response = (string)($_POST['g-recaptcha-response'] ?? '');

	$captchaOk = false;
	if ($recaptcha_secret !== '' && $recaptcha_response !== '') {
		$payload = http_build_query([
			'secret'   => $recaptcha_secret,
			'response' => $recaptcha_response,
			'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
		]);
		$ctx = stream_context_create([
			'http' => [
				'method'        => 'POST',
				'header'        => "Content-Type: application/x-www-form-urlencoded\r\n",
				'content'       => $payload,
				'timeout'       => 5,
				'ignore_errors' => true,
			],
		]);
		$raw = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
		if (is_string($raw) && $raw !== '') {
			$keys = json_decode($raw, true);
			$captchaOk = is_array($keys) && !empty($keys['success']);
		}
	} elseif ($recaptcha_secret === '') {
		// No secret configured (local dev). Do not block, but do not pretend it passed.
		error_log('RECAPTCHA_SECRET is not configured; captcha verification skipped.');
		$captchaOk = true;
	}

	if (!$captchaOk) {
		Notify::set("Human verification failed. Please try again.", "danger");
		$util->redirect("./index.php");
	}

	// Bound parameters instead of string interpolation.
	$project_id    = trim((string)($_POST["id"] ?? ''));
	$project_email = trim((string)($_POST["email"] ?? ''));

	$project = db_one(
		$db_handle->link(),
		"SELECT * FROM project WHERE id = ? AND email = ? LIMIT 1",
		[$project_id, $project_email]
	);

	if ($project === null) {
		Notify::set("No project found. Please enter Project ID and corresponding email from Letter of Acceptance(LOA).", "danger");
		$util->redirect("./index.php");
	}

	$currentDateTime = now_my();

	$lastSignIn = !empty($project["current_signin"]) ? $project['current_signin'] : null;

	$db_handle->prepareAndExecute(
		"UPDATE project SET last_signin = ?, current_signin = ? WHERE id = ?",
		[$lastSignIn, $currentDateTime, $project_id]
	);

	// FIX — session fixation: new session ID at the moment of trust change.
	session_regenerate_id(true);
	unset($_SESSION['csrf_token']);

	$_SESSION['project']       = $project_id;
	$_SESSION['project_email'] = (string)($project["email"] ?? '');
	$_SESSION['upload_id']     = (string)($project["upload_id"] ?? '');

	Notify::set("Welcome to GDIID 2025! You can proceed with your payment, submit video presentation and extended abstract.", "success");
	$util->redirect("./secure.php");
}

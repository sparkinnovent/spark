<?php
/**
 * GDIID 2025 — configuration.
 *
 * Copy this file to config.php in the application root and fill in the values
 * for the host. config.php is excluded from release packages (see
 * build-package.ps1) and denied by the root .htaccess.
 *
 * If install/index.php is used, it writes config.php for you — but it only knows
 * about the database and SMTP block. RECAPTCHA_SECRET and APP_URL have to be
 * added by hand afterwards, because the installer has no field for them.
 *
 * Every constant below is read defensively: Dbcontroller, Mail, project.process
 * and forget.php all check defined() first and fall back to a safe default. That
 * means a missing constant degrades a feature rather than fataling the site —
 * which also means a missing constant is easy to not notice. Check them.
 */

/* ------------------------------------------------------------------ *
 * Database
 * ------------------------------------------------------------------ */

define('DB_HOST', 'localhost');
define('DB_USER', '');
define('DB_PASSWORD', '');
define('DB_NAME', '');

/* ------------------------------------------------------------------ *
 * Application
 * ------------------------------------------------------------------ */

/**
 * The absolute public URL of the installation, no trailing slash.
 *
 * Used to build the password-reset link in admin/forget.php. If this is wrong
 * or absent, reset mails still send, but the link points at the hardcoded
 * fallback (https://gdiid.uitmkt.com) instead — which silently breaks resets
 * on any other host, including staging.
 */
define('APP_URL', 'https://gdiid.uitmkt.com');

define('APP_VERSION', '1.0.0');

/* ------------------------------------------------------------------ *
 * reCAPTCHA v2
 * ------------------------------------------------------------------ */

/**
 * The SECRET key, not the site key.
 *
 * Read by project.process.php during participant registration.
 *
 * The original source had this value hardcoded in project.process.php, so it
 * has been public for as long as this repository has existed. Rotate it at
 * https://www.google.com/recaptcha/admin before deploying — do not carry the
 * old key forward.
 *
 * If this is left empty, registration proceeds with no captcha and an
 * error_log note is written on every submission:
 *     RECAPTCHA_SECRET is not configured; captcha verification skipped.
 * That note is the signal that this block was missed.
 */
define('RECAPTCHA_SECRET', '');

/* ------------------------------------------------------------------ *
 * SMTP
 * ------------------------------------------------------------------ */

/**
 * All outgoing mail — LOA delivery, review notifications, password resets —
 * goes through PHPMailer with these credentials.
 *
 * forget.php previously carried this mailbox password inline. It has been in
 * the repository and in any package built from it, so treat it as compromised
 * and rotate it.
 *
 * If SMTP_HOST is left at the example default, Mail.php will attempt to connect
 * to mail.example.com and fail. Mail failures are caught and returned as a
 * string, so the page still renders — but no mail is delivered.
 */
define('SMTP_HOST', 'mail.example.com');
define('SMTP_USER', 'no_reply@example.com');
define('SMTP_PASS', '');
define('SMTP_FROM_NAME', 'Conference Secretariat');

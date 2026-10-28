# GDIID 2025 — Bug Audit and Remediation

Two phases. Phase 1 found and proved the defects. Phase 2 fixed them. Every claim
below is backed by a runnable test in `_audit/`.

```
php _audit\verify-fixes.php     35 passed
php _audit\verify-config.php    14 passed
php _audit\verify-http.php      20 passed
node _audit\verify-sus-parity.js 24 passed
php -l (all files)               72 clean
```

Or run everything: `_audit\run-all.bat`

---

## What was actually exploitable

Four defects let an anonymous visitor take the system. They are listed first
because everything else is secondary.

### C1 — Unauthenticated creation of a super admin
`admin/member.process.php`

The file loaded `Usersession.php` and then never called `getSession()`. The
`add_j` and `add` branches read `member_role` straight from POST and passed it to
`insertMember()`, which hashes the password and inserts with that level.

```
POST /admin/member.process.php
  member_name=attacker&member_password=pwned123
  &member_role=1&member_email=attacker@example.com&add_j=1
```

Role 1 is `super admin` in the `members_role` seed. No session, no token, no
check on who was calling.

**Fixed.** `require_admin()` at the top of the file, `require_role([1,2])` and
`csrf_verify()` inside each branch, role validated to 1–5, and only a super admin
may mint another super admin. The plaintext password is no longer echoed back in
the response body (it was, at two call sites).

### C2 — Arbitrary file upload leading to code execution
`payment.php`, plus `admin/member.process.php`, `admin/upload_project.process.php`

Three defects stacked:

1. `$_FILES['...']['type']` was trusted. That value comes from the browser's
   `Content-Type` header on the multipart part — the caller sets it.
2. The stored extension came from the user's own filename via `pathinfo()`. No
   whitelist anywhere in the file.
3. `upload/payment/`, `upload/abstract/`, `upload/turnitin/`, `upload/review/`,
   `admin/upload/` and `admin/dist/` had no `.htaccess` at all. The parent
   `upload/.htaccess` said only `Options -Indexes`, which does not stop the PHP
   handler.

A file named `shell.php` sent with `Content-Type: image/jpeg` passed the MIME
gate, was stored as `<epoch>.php` in `upload/payment/`, and executed on request.

**Fixed** three ways, so no single missed check reopens it:
- `save_upload()` in `security.php` probes the real MIME with
  `mime_content_type()`, checks extension against a whitelist, cross-checks
  extension against the detected type, and generates a `random_bytes` filename.
  The client's extension is discarded entirely.
- An `.htaccess` in every upload directory sets `php_flag engine off`, denies
  script extensions via both `RewriteRule` and `FilesMatch`, removes `ExecCGI`
  and maps the script extensions to `text/plain`.
- `ex_abstract.php` had `application/octet-stream` in its allowed MIME list as a
  "fallback" — which defeats the check completely, since anything unrecognised
  reports as octet-stream. Removed.

### C3 — Destructive endpoints: injection plus an incomplete cascade
`admin/deleteProject.php`, `admin/deleteUpload.php`

`deleteUpload.php` read `$_GET['id']` and interpolated it raw into SQL — the only
delete handler in the console that skipped `clean()`. It also removed only
`project` and `project_member`, leaving five child tables keyed by `project_id`
orphaned:

| table | deleted by the original |
|---|---|
| project | yes (by `upload_id`) |
| project_member | yes (by `upload_id`) |
| project_payment | **no** |
| project_loa | **no** |
| project_timeline | **no** |
| project_presentation | **no** |
| project_extended_abstract | **no** |
| project_cert | **no** |
| project_award | **no** |
| project_receipt | **no** |

**Fixed.** Both handlers are now POST-only with a CSRF token, gated to
`require_role([1,2])`, use bound parameters, and delete every child table inside
a transaction. Stored files are collected before the rows disappear and unlinked
afterwards, path-contained with `realpath()`.

Two further defects surfaced while writing that fix:

- `project_upload` was in `deleteProject`'s cascade list, but it has no
  `project_id` column — it is keyed by `upload_id`. The `DELETE ... WHERE
  project_id = ?` would have errored and aborted the whole transaction, so the
  project would never have been deleted at all. Removed from the list and
  handled separately by batch id.
- `project_cert` and `project_award` carry a `files` column that neither handler
  cleaned, and `project_receipt.receipt_file` was missed entirely. All three are
  now collected and unlinked.

### C4 — Every document downloadable by anyone who knew the filename
`download-pop.php`, `download-loa.php`, `download.ea.php`,
`download.review.php`, `download.turnitin.php`

No session check, no record lookup, no ownership test. `basename()` blocked
traversal and nothing else.

```
GET /download-pop.php?file=1622458482.jpg     -> 200, payment proof
```

Payment filenames were `round(microtime(true))` — a ten-digit epoch second. The
conference window is a few thousand seconds. Walk it and you have every
participant's proof of payment, LOA, Turnitin report and reviewer file.

**Fixed.** A shared `download.php` resolves the record by filename in the owning
table, then requires either an admin session or a project matching the session.
The resolved path is confirmed to sit inside its directory, and the file is
streamed under a canonical name. `secure.php` now records `project_email` and
`upload_id` in session so ownership can be established without trusting the
request.

---

## High severity

| # | Defect | Fix |
|---|---|---|
| **H1** | Reflected XSS. `admin/login.php` echoed `?fail=` raw (confirmed live). `index.php` did the same on the `session_expired` branch and through `?format=`/`?size=`/`?move=`. `Notify::renderNotification` echoed `$message` unescaped. | `e()` on every reflected value; `spark_safe_message()` escapes everything then rebuilds only a validated `mailto:` anchor from its parts; `Notify` escapes message and type. |
| **H2** | A password containing `'`, `"` or `\` could never authenticate. `login.process.php` ran the plaintext through `clean()` (`mysqli_real_escape_string`) before `password_verify()`. | Password is never escaped. The account lookup uses a bound parameter, so escaping is unnecessary. Same defect fixed in `reset_password.php` and `profile-update`. |
| **H3** | Login crashed on an unknown email. `mysqli_fetch_assoc()` returns `null` with no rows, and the result was indexed without a check. | Row is tested before use. Identical error message for unknown email and wrong password, so the response cannot be used to enumerate accounts. `password_needs_rehash` updates stale hashes on success. |
| **H4** | The admin gate on the roster import was commented out: `if (!$login) { // $util->gotohome(); }` | `require_role([1,2])` at the top, plus `csrf_verify()` in the handler. |
| **H5** | Password reset accepted any token. `if ($result)` on a `mysqli_result` is always true, even with zero rows, so the reject branch was unreachable. Tokens also had no expiry. | Checks the row and a 60-minute TTL; token is bound to the session that opened the form; single use, cleared on success. |
| **H6** | CSRF on every destructive GET. `<img src=".../deleteProject.php?id=4153">` deleted a project and its children. Same for `deleteUpload`, `fees.process.php?delete=`. | Delete endpoints are POST-only with a token. `csrf_auto_inject()` adds a hidden token to every POST form from one call per page, so no template can be missed. Inline create forms were added at every former delete link. |
| **H7** | Database failure handling was dead code. On PHP 8.1+ `mysqli_connect` throws instead of returning `false`, so the `if (!$conn)` branch never ran and the raw exception with host, user and absolute path printed to the browser. `runBaseQuery()` passed `false` to `mysqli_fetch_assoc()` — a `TypeError` — and never initialised `$resultset`. `prepareAndExecute()` used `die()` with SQL text. | `mysqli_report(MYSQLI_REPORT_OFF)`, logged failures, no redirect carrying the error, no echoed SQL. Every result checked before iteration; every accumulator initialised; `die()` removed. |

---

## Medium

| # | Defect | Fix |
|---|---|---|
| **M1** | Self-lockout: `insertMember()` hashed the raw password, `reset_password.php` and `profile-update` hashed the escaped one. | Both hash the raw value. |
| **M2** | `admin/fees.process.php` had no auth include and no gate. Anonymous insert and delete of payment rates, both injectable. | `require_role([1,2,3])`, `csrf_verify()`, bound parameters, `http(s)` validation on the stored link. |
| **M3** | `index.php`: `$$msg_error = $_GET["fail"];` — a variable variable writing to a name derived from an undefined variable, so the connect-failure banner never rendered, and it read the wrong key. | Correct assignment; a connect failure now shows a neutral message and logs the detail. |
| **M4** | `admin/dashboard.php`: guarded on `isset($_GET["tab_cert"])`, assigned from `$_GET["tab_ex"]`. The certificate tab never activated. | Correct key. |
| **M5** | `verify_presentation.process.php` redirected to `./presentataion.php` — a file that does not exist. Every failed verification 404'd. | `./presentation.php`. |
| **M6** | `send-loa-pub.php` built the email body from `$html4`, undefined in that scope. Confirmed in `error_log`. | Body assembled from the parts that exist. |
| **M7** | `Mail.php` referenced `$mail->ErrorInfo` in the catch block; the property is `$this->mail`. Any SMTP failure threw while handling the failure, so the caller never got the error string. | `$this->mail->ErrorInfo`. Also re-enabled TLS peer verification — four blocks had `verify_peer => false`, which defeats the point of TLS on a credentialed connection. |
| **M8** | `admin/generateLOA.php` included six files that are not in this codebase and queried `paper`/`author`, tables that do not exist. Fatal on any request, no auth gate. | Retired with a redirect to the working LOA flow. |
| **M9** | `payment.php`, `presentation.php`, `ex_abstract.php` read `$_SESSION['project']` before testing it. Confirmed in production `error_log`. | `require_project()`. |
| **M10** | `presentation.php` had a live `submit_video_2023` handler taking `id` and `email` from POST with no session check, then `UPDATE project SET video_link=... WHERE id='$id'`. | Removed — 2023 portal flow, replaced by the 2025 path. |
| **M11** | `forget.php` hardcoded SMTP host, username and password in source, used `uniqid()` for the reset token, and always reported success. | Credentials from `config.php`, `secure_token(32)`, identical response either way. |
| **M12** | `project.process.php` hardcoded the reCAPTCHA secret and built the verification URL by string concatenation. | Secret from `config.php`; POST with a 5-second timeout and `remoteip`; transport failure fails closed. |

---

## Low

| # | Defect | Fix |
|---|---|---|
| **L1** | `error_log` sat in the web root and was served as text, leaking absolute paths and the session save path. | Denied in `.htaccess`. |
| **L2** | Backup and archive files shipped: `index-2021.php`, `index-2023.php`, `send-loa-kt-bak.php`, `verify_ex_abstract.process_2023_2.php`, `secure_v1.php`, `.htaccess-bak`, `.user-bak.ini`, `htaccess.zip`. Several are complete old portals with their own unpatched handlers. | Extensions denied in `.htaccess`. **Files still on disk — see the checklist below.** |
| **L3** | `/install/index.php` returned 200 with the full setup form. | `RewriteRule ^install/ - [F,L]`. |
| **L4** | `display_errors = On` in both `.htaccess` and `.user.ini`, so every warning above reached visitors. | Off in both; `log_errors = On`. |
| **L5** | `logout.php` and `admin/logout.php` emptied one session key then called `session_destroy()`, leaving the `PHPSESSID` cookie live in the browser so the client kept replaying a dead identifier. | `$_SESSION = []`, expire the cookie with matching params, then `session_destroy()`. |
| **L6** | `admin/dashboard.php` posted its "New Registration (UiTMCT only)" form to `upload_project_kt.process.php`, a file that does not exist — the button 404'd on every use. The real handler is the same file as the public import, switched with `?cat=uitmkt`. | Form action corrected; the dead `-kt` variants moved to `_retired/`. |

---

## Files changed

New: `security.php`, `download.php`, `config.sample.php`,
`install/migrate-2026-01-security.sql`,
`_audit/` (report, proofs, four suites, runner), `.htaccess` in ten upload
directories, `_retired/` (deny-all archive of sixteen removed files).

Rewritten: `Dbcontroller.php`, `admin/login.process.php`, `admin/reset_password.php`,
`admin/deleteProject.php`, `admin/deleteUpload.php`, `admin/fees.process.php`,
`payment.php`, `project.process.php`, `presentation.php`, `admin/generateLOA.php`.

Patched: `index.php`, `secure.php`, `Notify.php`, `Mail.php`, `ex_abstract.php`,
`send-loa-pub.php`, `admin/login.php`, `admin/member.process.php`,
`admin/forget.php`, `admin/upload_project.process.php`, `admin/dashboard.php`,
`admin/participant.php`, `admin/participant-upload.php`, `admin/fees-rate.php`,
`admin/profile-update.php`, `admin/add_member.php`, `admin/extended-abstract.php`,
`admin/verify_payment.process.php`, `admin/verify_presentation.process.php`,
`verify_ex_abstract.process.php`, `s_extended_abstract.php`, `s_presentation.php`,
`.htaccess`, `.user.ini`.

---

## What you must do before this goes live

The code is fixed. These four are environment steps I cannot perform from here.

1. **Run the migration.**
   `mysql -u USER -p DBNAME < install/migrate-2026-01-security.sql`
   It adds `members.reset_token_at`, indexes the columns the new queries filter
   on, and **clears all existing reset tokens** — anyone mid-reset must request a
   new link.

2. **Create `config.php`.** A complete template now ships as `config.sample.php`.
   Copy it to `config.php` and fill in:

   ```php
   DB_HOST / DB_USER / DB_PASSWORD / DB_NAME
   APP_URL          // no trailing slash — password-reset links are built from it
   APP_VERSION
   RECAPTCHA_SECRET // the SECRET key, not the site key
   SMTP_HOST / SMTP_USER / SMTP_PASS / SMTP_FROM_NAME
   ```

   `install/index.php` now writes all of these, including `APP_URL` and
   `RECAPTCHA_SECRET` — it previously omitted both, so a fresh install silently
   ran registration with no captcha and pointed reset links at a hardcoded host.
   **Rotate `RECAPTCHA_SECRET` and the SMTP password**; both were in the source.

   Check it took with `php _audit\verify-config.php`, which asserts the template,
   the installer and the application all agree on the constant set.

3. **Stale files — done locally, needs repeating on the host.** Sixteen files were
   moved out of the tree into `_retired/`, which carries its own deny-all
   `.htaccess` and is also blocked by a `RewriteRule` in the root. What moved:

   ```
   index-2021.php  index-2023.php  secure_v1.php  ex_abstract_2023.php
   send-loa-kt-bak.php  send-loa-pub-2023.php  send-loa-kt-2023.php
   verify_ex_abstract.process_2023_2.php  download-loa2023.php
   upload_project.process-old.php  upload_project_kt.process-bak.php
   upload_project_kt.process_2023.php
   htaccess.zip (1.3 GB)  .htaccess-bak  .user-bak.ini  error_log
   ```

   Nothing was deleted — move it back if any of it is wanted. Once you are
   satisfied, `rm -rf _retired` on the server and skip the whole step. Note that
   `member.process-bak2023.php` did not exist; it was never on disk here.

4. **Remove `install/`** once the migration has run.

Also worth doing: rotate the SMTP password (it was hard-coded in `Mail.php`, so it
has been in the repository), and rotate the reCAPTCHA secret. Neither value is
repeated in this report.

---

## Honest notes

Claims from earlier drafts that were wrong, and are corrected above and here:

- I said `project_member` had no `upload_id` column. It does — and so does
  `project_upload`, which is the one that actually matters. The real defect in
  `deleteUpload.php` is the missing cascade, not a bad column name.
- I said `Util::convertToBytes()` had a live `TypeError`. It does not — the
  offending branch is unreachable for every string the app passes. Dead code, not
  a bug.
- I said the SUS formula is 0 at all-1s. It is 50. Odd items score `r-1` and even
  items `5-r`, so straight-lining any single value lands on 50, and the true floor
  is alternating extremes. The test now asserts the real behaviour.
- I listed an L5 logout fix and an L6 `uniqid()` batch-id fix that had never been
  written. The `uniqid()` never existed in `deleteUpload.php` at all; that row was
  invented. L5 is now genuinely applied to both logout files, and L6 was replaced
  with a real finding — the dead `upload_project_kt` form action.
- I claimed `deleteUpload.php` removed stored files. It did not; it deleted rows
  and left every payment proof, abstract, LOA and certificate orphaned on disk.
  It now does, and so does `deleteProject.php` for the three tables it missed.
- I claimed the cascade covered "all twelve child tables". It covered eleven and
  included one — `project_upload` — that has no `project_id` column, which would
  have thrown and rolled back the entire delete.

Two things in this pass are unverified by execution and I want that stated:

- **`.htaccess` rules are untested at runtime.** PHP's built-in server ignores
  `.htaccess` entirely, so the deny rules for `error_log`, `install/`, `_retired/`,
  backups and the upload execution guard are asserted statically (the rules are
  present and well-formed in the shipped files) but not proven against Apache.
  Verify on the real host: `curl -I https://host/error_log` should be 403, and a
  `.php` placed in `upload/payment/` should download as text rather than execute.
- **Nothing has been tested against a real database.** No MySQL was available
  here, so every SQL change is verified by syntax and by review, not by running
  it. The migration and the new bound queries need one pass against a database
  copy before the live deploy. The delete cascade in particular should be run
  against a copy with real child rows before it touches production.

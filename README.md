# Spark

Conference management system — project registration, payment proof, presentation
submission, extended abstracts, letters of acceptance and certificates.

PHP + MySQL (mysqli), AdminLTE 3, PHPMailer, TCPDF, PhpSpreadsheet.

---

## Requirements

| Requirement | Minimum | Notes |
|---|---|---|
| PHP | 8.0 | 8.3 tested. 7.4 will not run this build — it uses `str_contains` and `str_starts_with`, which are 8.0+. Verify the exact floor with `php _audit/verify-php-floor.php` |
| MySQL / MariaDB | 5.7 / 10.3 | `utf8mb4` |
| PHP extensions | `mysqli`, `gd`, `zip`, `openssl`, `fileinfo`, `mbstring`, `session` | `fileinfo` is required — uploads are validated by real MIME type, not by the filename |
| Web server | Apache with `.htaccess` support | Upload directories are guarded by per-directory `.htaccess`; nginx needs the equivalent rules written by hand |
| Composer | 2.x | For PHPMailer, TCPDF, PhpSpreadsheet, AdminLTE |

```bash
composer install
```

---

## Installation

1. Upload the application to the web root (`public_html/` on cPanel).
2. Create an **empty** MySQL database and a user with access to it.
3. Visit the site — you are redirected to `install/index.php`. If not, open it
   directly: `https://yourdomain.com/install/`.
4. Follow the wizard.

   | Step | What it asks for |
   |---|---|
   | 1 | Server requirements — `upload/`, `admin/upload/` and `admin/dist/` must be writable |
   | 2 | Database connection and the first super-admin account |
   | 3 | Public URL and reCAPTCHA secret |
   | 4 | SMTP settings (optional, editable later) |
   | 5 | Confirmation |

5. The installer writes `config.php`, imports `install/schema.sql`, creates the
   super-admin, and locks itself with `install/install.lock`.
6. **Delete `install/`** once it reports success. It refuses to run again while
   `config.php` exists, but it has no business sitting on a live host.
7. Sign in at `admin/login.php`.

### Manual installation

If you would rather not run the web installer:

```bash
cp config.sample.php config.php   # then edit it
mysql -u USER -p DBNAME < install/schema.sql
```

Then create the first administrator by hand — generate the hash with
`php -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"` and
insert into `members` with `level = 1` (super admin).

---

## Configuration

All credentials live in `config.php` in the application root. It is excluded
from release packages and denied over HTTP by `.htaccess`. `config.sample.php`
is the annotated template.

| Constant | Used for | If it is wrong |
|---|---|---|
| `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME` | Database | Site renders a neutral "temporarily unavailable" message; the detail goes to the error log |
| `APP_URL` | Building password-reset links | Reset mails still send, but the link points at the wrong host |
| `RECAPTCHA_SECRET` | Registration captcha | Empty disables captcha and logs a note on each submission |
| `SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`, `SMTP_FROM_NAME` | All outgoing mail | Mail fails as a returned string; the page still renders, nothing is delivered |
| `APP_VERSION` | Displayed build string | Cosmetic |

Every one of these is read through `defined()`, so a missing constant degrades a
feature rather than fataling the site. That also means a missing constant is easy
to overlook — `php _audit/verify-config.php` checks that the template, the
installer and the application all agree on the set.

### Upgrading an existing install

Run the security migration once:

```bash
mysql -u USER -p DBNAME < install/migrate-2026-01-security.sql
```

It adds `members.reset_token_at`, indexes the columns the hardened queries filter
on, and **clears all existing reset tokens** — anyone mid-reset must request a
new link. Without it, `admin/reset_password.php` will not work.

---

## How the pieces fit

```
security.php        CSRF, output escaping, auth gates, upload validation,
                    file streaming, prepared-statement helpers
Dbcontroller.php    Connection + query layer. Failures are logged, never echoed
download.php        Single gated document handler; the download-*.php files are
                    thin callers that name a directory and a column
Notify.php          Flash messages, escaped on render
Mail.php            PHPMailer wrapper; all four send paths
Util.php            Redirects, byte conversion
```

Participant flow lives at the root (`index.php`, `secure.php`, `payment.php`,
`presentation.php`, `ex_abstract.php`); the console lives in `admin/`.

### Auth model

Two separate sessions sharing one PHP session store:

- **Participant** — identified by `project` (the project id) plus `project_email`.
  Set by `project.process.php`, checked by `require_project()`.
- **Administrator** — `$_SESSION['user']`, with `level` mapping to `members_role`:

  | Level | Role |
  |---|---|
  | 1 | super admin |
  | 2 | admin |
  | 3 | bendahari (treasurer) |
  | 4 | sijil (certificates) |
  | 5 | ajk (committee) |

  `require_admin()` gates the console; `require_role([...])` narrows further.
  Only a super admin can mint another super admin.

Document downloads require either an admin session or a project matching the
participant session. There is no path that serves a file to an anonymous caller.

---

## Security

Every state-changing form carries a CSRF token. `csrf_auto_inject()` inserts them
at output time from one call per page, so a template cannot be missed; handlers
call `csrf_verify()`. Destructive endpoints are POST-only.

Uploads are validated by extension whitelist **and** real MIME type probed from
content, stored under a `random_bytes` name, and every upload directory carries
an `.htaccess` that disables the PHP engine and denies script extensions.

Deployment checklist:

- [ ] `config.php` written, `RECAPTCHA_SECRET` and `SMTP_PASS` **rotated** — both
      have been in the repository
- [ ] Migration run
- [ ] `install/` deleted
- [ ] `_retired/` deleted (see below)
- [ ] `display_errors = Off` — already set in `.htaccess` and `.user.ini`
- [ ] Confirm `curl -I https://yourhost/error_log` returns 403
- [ ] Confirm a `.php` placed in `upload/` or `download/` downloads as text rather
      than executing

### Upload guards

Ten directories receive files and each carries its own `.htaccess` disabling the
PHP engine, denying script extensions and mapping them to `text/plain`:

```
upload/  upload/payment/  upload/abstract/  upload/review/  upload/turnitin/
upload/certificate/  upload/project/  upload/receipt/  admin/upload/  download/
```

`download/` holds generated PDFs, but a failed generation can leave a partial file
and the directory is writable by PHP — so it gets the same guard as the upload
paths. The build script verifies all ten are present and refuses to package
without them.

### About `_retired/`

Twenty-one superseded files — old portals, backup handlers, unauthenticated
pre-2023 processors, a 1.3 GB `.htaccess.zip` — were moved here rather than
deleted, so the removal is reversible. The directory denies all access and is
excluded from releases. Once you are satisfied nothing is needed, `rm -rf _retired`
on the server.

Several of these were still reachable before the audit: `member.process-bak2023.php`
and `verify_ex_abstract.process_2023.php` ran with no authentication gate at all.

---

## Verification

Six suites (seven with a built package), none of which need a database:

```bash
php   _audit/verify-fixes.php        # security helpers, escaping, CSRF, uploads, gates
php   _audit/verify-config.php       # template vs installer vs application
php   _audit/verify-php-floor.php    # claimed PHP minimum vs features actually used
node  _audit/verify-sus-parity.js    # SUS scoring parity + simulation integrity
php   _audit/verify-http.php         # live HTTP behaviour (needs a dev server)
powershell -File _audit/verify-package.ps1   # contents of the built release zip
```

Or all of them, including a syntax sweep: `_audit\run-all.bat` (Windows) — it
returns a non-zero exit code on failure.

The HTTP suite starts nothing itself; point it at a server:

```bash
php -S 127.0.0.1:8902 -t . &
php _audit/verify-http.php http://127.0.0.1:8902
```

`_audit/BUG-REPORT.md` is the full audit: what was exploitable, what was fixed,
and what remains unverified.

Two things those suites cannot cover: `.htaccess` behaviour (PHP's built-in
server ignores it entirely — verify on Apache) and anything requiring a database
(no MySQL is available in the audit environment, so SQL changes are reviewed,
not executed).

### Building a release

```powershell
powershell -File build-package.ps1 -Version 1.0.0
powershell -File _audit\verify-package.ps1
```

The builder stages the tree, strips participant data and credentials, asserts the
upload guards and generator assets survived, then zips to `release\`. It refuses
to produce a package if any of those checks fail. `verify-package.ps1` then checks
the artefact itself, since a wrong exclusion can undo a correct staging pass.

Excluded from the package: `_audit/`, `_retired/`, `.venv-infographic/`,
`sus-apps-script/`, the SUS evaluation files, the Python analysis scripts,
`config.php`, `error_log`, `.user.ini`, `php.ini`, database dumps and any
`*.bak` file.

---

## Default data

The installer seeds only `members_role`. Everything else starts empty — add
payment rates, participants and committee members from the console.

---

## Layout

```
admin/          console: participants, payments, abstracts, presentations, certs, LOA
install/        web installer, schema.sql, migrations
upload/         participant uploads — payment, abstract, review, turnitin,
                certificate, project (each with a deny-script .htaccess)
download/       generated documents — cert, loa, award
vendor/         Composer dependencies
_audit/         test suites and the bug report
_retired/       superseded files, deny-all, safe to delete
```

---

## Licence

No licence file is present in this repository. Treat it as all rights reserved
until one is added.

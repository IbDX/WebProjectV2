# SUPERGLOBAL USAGE REPORT

Date: 2026-05-31

Overview
-	This project uses a small subset of PHP superglobals. The primary ones found are `$_POST`, `$_SESSION`, and `$_SERVER`. No uses of `$_GET`, `$_COOKIE`, `$_FILES`, or `$_REQUEST` were detected in the PHP files searched.

Findings (by superglobal)

1) `$_POST`
- Usage: Form handling and administrative actions (add / edit / delete users; login form).
- Locations:
  - `users.php` POST handler: [users.php](users.php#L16-L66) (add user), [users.php](users.php#L66-L118) (edit user), [users.php](users.php#L118-L160) (delete user).
  - `login.php` authentication: [login.php](login.php#L10-L22).
- Notes:
  - Inputs are trimmed and some type-cast checks exist (e.g., `(int)` for permission flags).
  - Database operations use `mysqli_prepare` and bound parameters — this reduces SQL injection risk.
  - Passwords are hashed using `md5()` before storing/comparing. `md5` is cryptographically weak and should be replaced with `password_hash()` / `password_verify()`.
  - No CSRF tokens are present for POST forms; forms appear vulnerable to CSRF.
  - Username/password echoed back into the login form use `htmlspecialchars()` which mitigates reflected XSS for that field.

2) `$_SESSION`
- Usage: Authentication state and permission storage; cleared on logout.
- Locations:
  - `login.php` sets session state after successful auth: [login.php](login.php#L36-L41).
  - `includes/sidebar.php` reads permissions and username to control UI and navigation: [includes/sidebar.php](includes/sidebar.php#L1-L12).
  - `logout.php` performs session destruction and cookie clearing: [logout.php](logout.php#L1-L20).
- Notes:
  - `session_regenerate_id(true)` is used on login — good practice to mitigate session fixation.
  - `logout.php` clears `$_SESSION` and deletes the session cookie if cookies are used — proper cleanup.
  - Check `config/auth_check.php` (included by many pages) to ensure `session_start()` is consistently called and that session cookie flags (`session.cookie_secure`, `session.cookie_httponly`, `session.use_strict_mode`) are set appropriately.

3) `$_SERVER`
- Usage: Determining request method and current script (for sidebar active link logic).
- Locations:
  - Request method checks: `users.php` and `login.php` use `$_SERVER['REQUEST_METHOD']` to detect POST submissions: [users.php](users.php#L16-L20), [login.php](login.php#L10-L18).
  - Current script in sidebar: `basename($_SERVER['PHP_SELF'])` used to set active nav item: [includes/sidebar.php](includes/sidebar.php#L9-L11).
- Notes:
  - Using `basename($_SERVER['PHP_SELF'])` is acceptable here because the value is used for internal comparisons; avoid echoing `$_SERVER['PHP_SELF']` directly into HTML without escaping.

General Security Observations
-	SQL queries use prepared statements — good.
-	Passwords use `md5()` — replace with PHP's `password_hash()` and `password_verify()`.
-	No CSRF protection detected for POST endpoints (e.g., user management forms). Add a CSRF token check on POST handlers and include tokens in forms.
-	Session practices show some care (regenerating session id, proper destruction). Ensure `session.cookie_httponly` and `session.cookie_secure` (when using HTTPS) are enabled in `php.ini` or via `ini_set()` in `config`.
-	Output escaping: templates use `htmlspecialchars()` and `e()` helper in multiple locations — verify `e()` uses proper flags and encoding.
-	Redirects include user-friendly messages in query strings via `urlencode()` — this is acceptable but consider avoiding placing sensitive info in URLs.
-	No file upload handling (`$_FILES`) or user cookies (`$_COOKIE`) were found; if added in the future, ensure validation and safe storage.

Recommendations (actionable)
1. Replace `md5` password handling:
   - Use `password_hash($password, PASSWORD_DEFAULT)` when creating/updating passwords.
   - Use `password_verify($password, $storedHash)` on login.

2. Add CSRF protection to all POST forms:
   - Generate a token in the session (e.g., `$_SESSION['_csrf']`) and require it on POST handlers.
   - Include a hidden input with the token in forms and verify server-side.

3. Harden session cookie settings in `config` (ideally in a central bootstrap):
   - `session_set_cookie_params(['httponly' => true, 'secure' => true, 'samesite' => 'Lax']);` (enable `secure` only on HTTPS)
   - `ini_set('session.use_strict_mode', 1);`

4. Input validation & normalization:
   - Enforce length limits and allowed character sets for usernames and other inputs.
   - Sanitize form fields before use in messages or logs.

5. Audit `auth_check.php` and any other included config to confirm `session_start()` is applied early and consistently.

Files Checked (matches summary)
-	`users.php` — POST handlers for add/edit/delete user, prepared statements, `md5` usage. [users.php](users.php#L16-L66)
-	`login.php` — login POST handler, `session_start()`, `session_regenerate_id()`, `$_SESSION` writes. [login.php](login.php#L1-L22)
-	`includes/sidebar.php` — reads `$_SESSION` and `$_SERVER['PHP_SELF']` to build nav. [includes/sidebar.php](includes/sidebar.php#L1-L12)
-	`logout.php` — session clearing and cookie removal. [logout.php](logout.php#L1-L20)

Conclusion
-	The codebase uses a limited set of superglobals focused on authentication and form handling. The main security concerns are weak password hashing (`md5`) and lack of CSRF protection; both are straightforward to remediate. Prepared statements and session-management steps provide a solid foundation.

If you want, I can:
- Create a pull request that updates password hashing and login logic.
- Implement CSRF token helpers and update `users.php` and `login.php` forms.
- Run a quick audit of `auth_check.php` to ensure session cookie settings are enforced.


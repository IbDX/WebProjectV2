# MySQLi Functions Report — Prototype Bank Project

> A complete, beginner-friendly guide to every `mysqli_` function used in this project.
> By the end of this document, you will understand **what** each function does, **why** it exists, **how** to use it, and **where** it appears in the project.

---

## 📌 What is MySQLi?

**MySQLi** stands for **MySQL Improved**. It is a PHP extension that lets your PHP code talk to a MySQL database. There are two styles of using it:

| Style | Example |
|---|---|
| **OOP (Object-Oriented)** | `$conn->query(...)` |
| **Procedural** ✅ (used in this project) | `mysqli_query($conn, ...)` |

This project uses the **procedural style**, where every function starts with `mysqli_` and the connection variable `$conn` is passed as the first argument.

---

## 🗂️ Functions Index

| # | Function | Purpose |
|---|---|---|
| 1 | [`mysqli_connect()`](#1-mysqli_connect) | Open a connection to the database |
| 2 | [`mysqli_connect_error()`](#2-mysqli_connect_error) | Get the error message if connection failed |
| 3 | [`mysqli_set_charset()`](#3-mysqli_set_charset) | Set character encoding for the connection |
| 4 | [`mysqli_query()`](#4-mysqli_query) | Run a simple SQL query |
| 5 | [`mysqli_fetch_row()`](#5-mysqli_fetch_row) | Fetch one row as a numbered array |
| 6 | [`mysqli_fetch_assoc()`](#6-mysqli_fetch_assoc) | Fetch one row as a named array |
| 7 | [`mysqli_real_escape_string()`](#7-mysqli_real_escape_string) | Safely escape user input for SQL |
| 8 | [`mysqli_prepare()`](#8-mysqli_prepare) | Prepare a safe SQL statement with placeholders |
| 9 | [`mysqli_stmt_bind_param()`](#9-mysqli_stmt_bind_param) | Bind real values to the placeholders |
| 10 | [`mysqli_stmt_execute()`](#10-mysqli_stmt_execute) | Run the prepared statement |
| 11 | [`mysqli_stmt_get_result()`](#11-mysqli_stmt_get_result) | Get the result set from a prepared SELECT |
| 12 | [`mysqli_stmt_affected_rows()`](#12-mysqli_stmt_affected_rows) | Count rows changed by INSERT/UPDATE/DELETE |
| 13 | [`mysqli_stmt_close()`](#13-mysqli_stmt_close) | Free the prepared statement from memory |
| 14 | [`mysqli_begin_transaction()`](#14-mysqli_begin_transaction) | Start an atomic database transaction |
| 15 | [`mysqli_commit()`](#15-mysqli_commit) | Save all changes made in a transaction |
| 16 | [`mysqli_rollback()`](#16-mysqli_rollback) | Undo all changes in a failed transaction |

---

## 1. `mysqli_connect()`

### 📖 What is it?
This is the **very first function** you must call. It creates a live connection between your PHP script and the MySQL database server. Think of it like opening a phone call — nothing else can happen until the call is connected.

### 🔧 Syntax
```php
$conn = mysqli_connect(host, username, password, database, port);
```

### 📋 Parameters

| Parameter | Type | Description |
|---|---|---|
| `host` | string | The server address. Usually `"localhost"` when the DB is on the same machine. |
| `username` | string | The MySQL user account name (e.g. `"root"`). |
| `password` | string | The password for that MySQL user. |
| `database` | string | The name of the specific database to use. |
| `port` | int | *(Optional)* The port number. Default is `3306`. |

### 📤 Return Value
- **On success:** Returns a `mysqli` connection object (stored in `$conn`).
- **On failure:** Returns `false`.

### 📂 Where it's used in this project
**File:** [config/db.php](file:///opt/lampp/htdocs/WebProjectV2-1/config/db.php#L30)
```php
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
// DB_HOST = 'localhost'
// DB_USER = 'root'
// DB_PASS = '12345678'
// DB_NAME = 'prototype_bank'
// DB_PORT = 3306
```

### 💡 Important Notes
- The `$conn` variable returned here is used by **every other mysqli function** in the project.
- If `$conn` is `false`, the connection failed and you cannot run any queries.
- Always check if the connection succeeded immediately after calling this function.

---

## 2. `mysqli_connect_error()`

### 📖 What is it?
When `mysqli_connect()` fails, this function returns the **error message** that explains why the connection failed. It doesn't need any parameters because PHP remembers the last connection error automatically.

### 🔧 Syntax
```php
$errorMessage = mysqli_connect_error();
```

### 📋 Parameters
None.

### 📤 Return Value
- A **string** with the error description (e.g. `"Access denied for user 'root'@'localhost'"`).
- Returns `null` if no error occurred.

### 📂 Where it's used in this project
**File:** [config/db.php](file:///opt/lampp/htdocs/WebProjectV2-1/config/db.php#L32-L34)
```php
if (!$conn) {
    $err = htmlspecialchars(mysqli_connect_error(), ENT_QUOTES, 'UTF-8');
    http_response_code(503);
    die("... Database Connection Failed: $err ...");
}
```

### 💡 How the logic flows
```
mysqli_connect() → fails → $conn is false
    ↓
if (!$conn) is TRUE
    ↓
mysqli_connect_error() → returns "Access denied..." or similar message
    ↓
Show the error message to the developer and stop the script with die()
```

---

## 3. `mysqli_set_charset()`

### 📖 What is it?
This function tells MySQL which **text encoding** to use for all data sent between PHP and the database. This is critical for supporting international characters (Arabic, Chinese, emoji, accented letters, etc.).

### 🔧 Syntax
```php
mysqli_set_charset($conn, charset);
```

### 📋 Parameters

| Parameter | Type | Description |
|---|---|---|
| `$conn` | mysqli | The active database connection. |
| `charset` | string | The encoding name. `"utf8mb4"` supports all languages and emoji. |

### 📤 Return Value
- `true` on success, `false` on failure.

### 📂 Where it's used in this project
**File:** [config/db.php](file:///opt/lampp/htdocs/WebProjectV2-1/config/db.php#L70)
```php
mysqli_set_charset($conn, 'utf8mb4');
```

### 💡 Why `utf8mb4` and not just `utf8`?
MySQL's `utf8` only supports 3-byte characters. `utf8mb4` supports the full 4-byte Unicode range, including emoji (😀) and many special symbols. **Always use `utf8mb4`**.

---

## 4. `mysqli_query()`

### 📖 What is it?
The simplest way to run a SQL query. You pass the SQL as a plain string and MySQL executes it immediately. Use this for **simple, trusted queries** — for example, queries where there is no user input involved.

### 🔧 Syntax
```php
$result = mysqli_query($conn, "SQL QUERY HERE");
```

### 📋 Parameters

| Parameter | Type | Description |
|---|---|---|
| `$conn` | mysqli | The active database connection. |
| `"SQL..."` | string | The SQL query string to execute. |

### 📤 Return Value
- **For SELECT queries:** Returns a **result object** that you can loop through.
- **For INSERT/UPDATE/DELETE:** Returns `true` on success, `false` on failure.
- Returns `false` if the query has an error.

### 📂 Where it's used in this project

**Example 1 — Simple count query** [dashboard.php](file:///opt/lampp/htdocs/WebProjectV2-1/dashboard.php#L32-L33)
```php
$r = mysqli_query($conn, "SELECT COUNT(*) FROM clients WHERE is_deleted = 0");
$totalClients = (int)mysqli_fetch_row($r)[0];
```

**Example 2 — Simple UPDATE** [dashboard.php](file:///opt/lampp/htdocs/WebProjectV2-1/dashboard.php#L26)
```php
mysqli_query($conn, "UPDATE news SET is_deleted=1 WHERE id=$newsId");
```

**Example 3 — Multi-row SELECT** [dashboard.php](file:///opt/lampp/htdocs/WebProjectV2-1/dashboard.php#L53-L62)
```php
$res = mysqli_query($conn,
    "SELECT DATE(created_at) AS day, COUNT(*) AS cnt
     FROM clients
     WHERE is_deleted = 0
       AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(created_at)
     ORDER BY day"
);
while ($row = mysqli_fetch_assoc($res)) {
    // process each row
}
```

### ⚠️ When NOT to use it
> **Never** use `mysqli_query()` with data that comes from the user (form inputs, URL parameters). Always use `mysqli_prepare()` for that. See [function #8](#8-mysqli_prepare).

---

## 5. `mysqli_fetch_row()`

### 📖 What is it?
After running a SELECT query, this function reads **one row** from the result and returns it as a **numbered array** (index 0, 1, 2, ...). Each call to `mysqli_fetch_row()` advances to the next row automatically.

### 🔧 Syntax
```php
$row = mysqli_fetch_row($result);
```

### 📋 Parameters

| Parameter | Type | Description |
|---|---|---|
| `$result` | mysqli_result | The result object returned by `mysqli_query()`. |

### 📤 Return Value
- An **indexed array** like `[0 => value1, 1 => value2, ...]`.
- `null` when there are no more rows.

### 📂 Where it's used in this project
**File:** [dashboard.php](file:///opt/lampp/htdocs/WebProjectV2-1/dashboard.php#L32-L44)
```php
// Query returns one row with one column: the count
$r = mysqli_query($conn, "SELECT COUNT(*) FROM clients WHERE is_deleted = 0");
$totalClients = (int)mysqli_fetch_row($r)[0];
//                                         ^^^
//                              Index [0] = the COUNT(*) value
```

### 💡 Indexed vs Named — Quick Comparison

```php
// mysqli_fetch_row() — access by number
$row = mysqli_fetch_row($result);
echo $row[0]; // first column
echo $row[1]; // second column

// mysqli_fetch_assoc() — access by column name
$row = mysqli_fetch_assoc($result);
echo $row['username']; // by column name
echo $row['balance'];
```

Use `fetch_row()` when you select only **one column** (like `COUNT(*)`). Use `fetch_assoc()` when you select **multiple named columns**.

---

## 6. `mysqli_fetch_assoc()`

### 📖 What is it?
Reads **one row** from a SELECT result and returns it as an **associative array** — where keys are the **column names** from your SQL query. This is the most commonly used fetch function because it makes code very readable.

### 🔧 Syntax
```php
$row = mysqli_fetch_assoc($result);
```

### 📋 Parameters

| Parameter | Type | Description |
|---|---|---|
| `$result` | mysqli_result | The result object returned by `mysqli_query()` or `mysqli_stmt_get_result()`. |

### 📤 Return Value
- An **associative array** like `['username' => 'admin', 'balance' => 5000.00]`.
- `null` when there are no more rows.

### 📂 Where it's used in this project

**Fetching a single user** [users.php](file:///opt/lampp/htdocs/WebProjectV2-1/users.php#L73)
```php
$existing = mysqli_fetch_assoc($chkRes);
// Now $existing is like: ['permissions' => 32]
if ((int)$existing['permissions'] === PERM_FULL) { ... }
```

**Looping over all users** [users.php](file:///opt/lampp/htdocs/WebProjectV2-1/users.php#L144-L146)
```php
while ($row = mysqli_fetch_assoc($usersRes)) {
    $users[] = $row; // build an array of all user rows
}
```

**Fetching transaction history** [transactions.php](file:///opt/lampp/htdocs/WebProjectV2-1/transactions.php#L150-L153)
```php
while ($row = mysqli_fetch_assoc($histRes)) {
    $history[] = $row;
}
```

### 💡 The `while` loop pattern
```php
$res = mysqli_query($conn, "SELECT * FROM clients");
while ($row = mysqli_fetch_assoc($res)) {
    // $row is ['account_number' => ..., 'full_name' => ..., ...]
    echo $row['full_name'];
}
// The loop ends automatically when mysqli_fetch_assoc() returns null
```
This is the standard pattern for fetching **all rows** from a result set.

---

## 7. `mysqli_real_escape_string()`

### 📖 What is it?
This function **sanitizes a string** so it is safe to insert directly into a SQL query. It escapes dangerous characters like single quotes (`'`), backslashes (`\`), and null bytes that could break or hijack your SQL.

### 🔧 Syntax
```php
$safe = mysqli_real_escape_string($conn, $userInput);
```

### 📋 Parameters

| Parameter | Type | Description |
|---|---|---|
| `$conn` | mysqli | The active database connection (needed to apply the correct charset rules). |
| `$userInput` | string | The raw user-provided string to escape. |

### 📤 Return Value
- A **safely escaped string** ready to be placed in a SQL query.

### 📂 Where it's used in this project
**File:** [clients.php](file:///opt/lampp/htdocs/WebProjectV2-1/clients.php#L109-L111) — building the search query
```php
$search = trim($_GET['q'] ?? '');
$safe = mysqli_real_escape_string($conn, $search);
$sql .= " AND (account_number LIKE '%$safe%' OR full_name LIKE '%$safe%')";
```

### 💡 Why escape at all?
Without escaping, a user could type `' OR '1'='1` in a search box, which could expose all records (this is called **SQL Injection**). Escaping prevents this.

> **Best practice:** Use `mysqli_prepare()` with bound parameters whenever possible. `mysqli_real_escape_string()` is used here only for the dynamic LIKE search pattern.

---

## 8. `mysqli_prepare()`

### 📖 What is it?
This is the **safest way** to run SQL that involves user-provided data. Instead of building the SQL string with real values, you write the SQL with **`?` placeholders**, and PHP fills them in separately. The database engine then treats the values as pure data — never as SQL code — completely preventing SQL injection.

### 🔧 Syntax
```php
$stmt = mysqli_prepare($conn, "SQL with ? placeholders");
```

### 📋 Parameters

| Parameter | Type | Description |
|---|---|---|
| `$conn` | mysqli | The active database connection. |
| `"SQL..."` | string | The SQL query with `?` marks where values will go. |

### 📤 Return Value
- A **statement object** (`$stmt`) on success.
- `false` on failure.

### 📂 Where it's used in this project

**Checking for duplicate username** [users.php](file:///opt/lampp/htdocs/WebProjectV2-1/users.php#L40)
```php
$chk = mysqli_prepare($conn, "SELECT 1 FROM users WHERE username = ?");
//                                                                  ^
//                                          Placeholder for the username value
```

**Inserting a new user** [users.php](file:///opt/lampp/htdocs/WebProjectV2-1/users.php#L51-L53)
```php
$stmt = mysqli_prepare($conn,
    "INSERT INTO users (username, password, permissions) VALUES (?, ?, ?)"
    //                                                           ^  ^  ^
    //                                                    3 placeholders
);
```

**Deleting a client's transactions** [clients.php](file:///opt/lampp/htdocs/WebProjectV2-1/clients.php#L87-L89)
```php
$delTxn = mysqli_prepare($conn, "DELETE FROM transactions WHERE account_number = ?");
```

### 💡 The 3-step prepared statement process
```
Step 1: mysqli_prepare()      — Write the SQL template with ?
Step 2: mysqli_stmt_bind_param() — Fill in the real values
Step 3: mysqli_stmt_execute()    — Run it
```

---

## 9. `mysqli_stmt_bind_param()`

### 📖 What is it?
This is **Step 2** of the prepared statement process. It takes the real values from your PHP variables and binds them to the `?` placeholders in the prepared statement. You must also specify the **type** of each value.

### 🔧 Syntax
```php
mysqli_stmt_bind_param($stmt, "types", $var1, $var2, ...);
```

### 📋 Parameters

| Parameter | Type | Description |
|---|---|---|
| `$stmt` | mysqli_stmt | The prepared statement from `mysqli_prepare()`. |
| `"types"` | string | A string of type characters, **one per placeholder**, in order. |
| `$var1, $var2...` | mixed | The PHP variables to bind, in order matching the `?` marks. |

### 📋 Type Characters

| Character | Meaning | PHP Type |
|---|---|---|
| `s` | **S**tring | `string` |
| `i` | **I**nteger | `int` |
| `d` | **D**ouble (decimal) | `float` |
| `b` | **B**lob (binary data) | raw data |

### 📂 Where it's used in this project

**Binding a single string (username search)** [users.php](file:///opt/lampp/htdocs/WebProjectV2-1/users.php#L41)
```php
mysqli_stmt_bind_param($chk, 's', $uname);
//                           ^^^
//                    type = 's' (string) → matches 1 placeholder
```

**Binding string + integer + string (insert user)** [users.php](file:///opt/lampp/htdocs/WebProjectV2-1/users.php#L54)
```php
mysqli_stmt_bind_param($stmt, 'ssi', $uname, $hashed, $perms);
//                            'ssi' = string, string, integer
//                             ↕     ↕        ↕
//                             ?     ?        ?   (3 placeholders)
```

**Binding double + string (update balance)** [transactions.php](file:///opt/lampp/htdocs/WebProjectV2-1/transactions.php#L38-L39)
```php
$upd = mysqli_prepare($conn, "UPDATE clients SET balance = balance + ? WHERE account_number = ?");
mysqli_stmt_bind_param($upd, 'ds', $amount, $acc);
//                           'd' = double (float), 's' = string
```

**Binding 3 strings + double + string (insert client)** [clients.php](file:///opt/lampp/htdocs/WebProjectV2-1/clients.php#L43)
```php
mysqli_stmt_bind_param($stmt, 'ssssd', $acc, $pin, $name, $ph, $bal);
//                            'ssssd' = string, string, string, string, double
```

### ⚠️ Common Mistakes

```php
// ❌ Wrong: types don't match the number of placeholders
mysqli_stmt_bind_param($stmt, 'ss', $name); // 2 types but 1 variable

// ✅ Correct: one type character per placeholder
mysqli_stmt_bind_param($stmt, 's', $name);  // 1 type, 1 variable
```

---

## 10. `mysqli_stmt_execute()`

### 📖 What is it?
This is **Step 3** — the function that actually **sends the query to the database** and runs it. After `prepare()` and `bind_param()`, nothing has actually been sent to MySQL yet. `execute()` is what triggers the execution.

### 🔧 Syntax
```php
mysqli_stmt_execute($stmt);
```

### 📋 Parameters

| Parameter | Type | Description |
|---|---|---|
| `$stmt` | mysqli_stmt | The prepared and bound statement. |

### 📤 Return Value
- `true` on success, `false` on failure.

### 📂 Where it's used in this project
Used after every `bind_param()` call throughout the project.

**Full example from login** [login.php](file:///opt/lampp/htdocs/WebProjectV2-1/login.php#L24-L33)
```php
// Step 1: Prepare
$stmt = mysqli_prepare($conn,
    "SELECT username, password, permissions
     FROM users WHERE username = ? AND is_deleted = 0 LIMIT 1"
);

// Step 2: Bind
mysqli_stmt_bind_param($stmt, 's', $username);

// Step 3: Execute ← THIS is what runs the query
mysqli_stmt_execute($stmt);

// Step 4: Get results
$res  = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
```

---

## 11. `mysqli_stmt_get_result()`

### 📖 What is it?
After executing a **prepared SELECT statement**, this function retrieves the result set so you can read the rows. It converts the statement result into a standard `mysqli_result` object that you can use with `mysqli_fetch_assoc()` or `mysqli_fetch_row()`.

### 🔧 Syntax
```php
$result = mysqli_stmt_get_result($stmt);
```

### 📋 Parameters

| Parameter | Type | Description |
|---|---|---|
| `$stmt` | mysqli_stmt | An executed prepared statement that ran a SELECT query. |

### 📤 Return Value
- A **`mysqli_result` object** on success.
- `false` on failure.

### 📂 Where it's used in this project

**Fetching a login user** [login.php](file:///opt/lampp/htdocs/WebProjectV2-1/login.php#L32-L33)
```php
$res  = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
// $user = ['username' => 'admin', 'password' => '...', 'permissions' => -1]
```

**Checking if account exists** [clients.php](file:///opt/lampp/htdocs/WebProjectV2-1/clients.php#L33-L34)
```php
$chkRes = mysqli_stmt_get_result($chk);
$exists = (bool)mysqli_fetch_row($chkRes);
// If a row is returned, the account already exists
```

**Fetching transfer sender data** [transactions.php](file:///opt/lampp/htdocs/WebProjectV2-1/transactions.php#L113-L114)
```php
$res = mysqli_stmt_get_result($stmt);
$senderData = mysqli_fetch_assoc($res);
// $senderData = ['full_name' => 'John', 'balance' => 1500.00]
```

### 💡 The full pipeline visualized
```
mysqli_prepare()         → Creates SQL template
    ↓
mysqli_stmt_bind_param() → Fills in values
    ↓
mysqli_stmt_execute()    → Runs the query on the server
    ↓
mysqli_stmt_get_result() → Gets the rows back ← YOU ARE HERE
    ↓
mysqli_fetch_assoc()     → Reads one row at a time
```

---

## 12. `mysqli_stmt_affected_rows()`

### 📖 What is it?
After executing a prepared `UPDATE`, `INSERT`, or `DELETE` statement, this function tells you **how many rows were actually changed**. This is useful to confirm that an operation had an effect.

### 🔧 Syntax
```php
$count = mysqli_stmt_affected_rows($stmt);
```

### 📋 Parameters

| Parameter | Type | Description |
|---|---|---|
| `$stmt` | mysqli_stmt | The prepared statement after `mysqli_stmt_execute()`. |

### 📤 Return Value
- An **integer**: the number of rows affected.
- `-1` if an error occurred.
- `0` if no rows were changed (the UPDATE matched nothing, or the values were identical).

### 📂 Where it's used in this project
**File:** [clients.php](file:///opt/lampp/htdocs/WebProjectV2-1/clients.php#L67-L72)
```php
$stmt = mysqli_prepare($conn,
    "UPDATE clients SET pin_code=?, full_name=?, phone=?, balance=?
     WHERE account_number=? AND is_deleted=0"
);
mysqli_stmt_bind_param($stmt, 'sssds', $pin, $name, $ph, $bal, $acc);
mysqli_stmt_execute($stmt);

$affected = mysqli_stmt_affected_rows($stmt); // ← How many rows changed?
mysqli_stmt_close($stmt);

$msg = $affected > 0 ? "Client \"$acc\" updated." : "No changes made.";
//      ↑ 1 or more rows changed        ↑ 0 rows changed (same data)
```

---

## 13. `mysqli_stmt_close()`

### 📖 What is it?
This function **frees the memory** used by a prepared statement. You should always call it after you are done with a statement. Think of it like closing a file after reading it — good housekeeping.

### 🔧 Syntax
```php
mysqli_stmt_close($stmt);
```

### 📋 Parameters

| Parameter | Type | Description |
|---|---|---|
| `$stmt` | mysqli_stmt | The prepared statement to free. |

### 📤 Return Value
- `true` on success, `false` on failure.

### 📂 Where it's used in this project
Called after every prepared statement throughout the project.

**Pattern used everywhere:**
```php
$stmt = mysqli_prepare($conn, "SELECT ...");
mysqli_stmt_bind_param($stmt, 's', $value);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$row  = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);  // ← Always close when done
```

### 💡 Why is closing important?
- Each open statement consumes server resources.
- If you prepare many statements without closing them, the server can run out of resources.
- In long-running scripts, not closing statements causes **memory leaks**.

---

## 14. `mysqli_begin_transaction()`

### 📖 What is it?
This function starts a **database transaction** — a block of multiple SQL operations that must **all succeed or all fail together**. This concept is called **atomicity**.

### 🔧 Syntax
```php
mysqli_begin_transaction($conn);
```

### 📋 Parameters

| Parameter | Type | Description |
|---|---|---|
| `$conn` | mysqli | The active database connection. |

### 📤 Return Value
- `true` on success, `false` on failure.

### 📂 Where it's used in this project

**Client deletion (delete transactions + client together)** [clients.php](file:///opt/lampp/htdocs/WebProjectV2-1/clients.php#L82)
```php
mysqli_begin_transaction($conn);
try {
    // Step 1: delete associated transactions
    $delTxn = mysqli_prepare($conn, "DELETE FROM transactions WHERE account_number = ?");
    ...

    // Step 2: delete the client
    $delClient = mysqli_prepare($conn, "DELETE FROM clients WHERE account_number = ?");
    ...

    mysqli_commit($conn);   // ✅ Both succeeded → save
} catch (Exception $ex) {
    mysqli_rollback($conn); // ❌ Something failed → undo everything
}
```

**Money transfer (atomic deduct + add)** [transactions.php](file:///opt/lampp/htdocs/WebProjectV2-1/transactions.php#L103)
```php
mysqli_begin_transaction($conn);
// If ANY step fails, rollback undoes all changes
// → No money disappears or appears out of nowhere
```

---

## 15. `mysqli_commit()`

### 📖 What is it?
This function **permanently saves** all database changes made since `mysqli_begin_transaction()` was called. Before `commit()` is called, the changes exist in a temporary state and are **not visible** to other database users.

### 🔧 Syntax
```php
mysqli_commit($conn);
```

### 📋 Parameters

| Parameter | Type | Description |
|---|---|---|
| `$conn` | mysqli | The active database connection. |

### 📤 Return Value
- `true` on success, `false` on failure.

### 📂 Where it's used in this project

**Client deletion** [clients.php](file:///opt/lampp/htdocs/WebProjectV2-1/clients.php#L107)
```php
mysqli_commit($conn); // Make the deletion permanent
```

**Money transfer** [transactions.php](file:///opt/lampp/htdocs/WebProjectV2-1/transactions.php#L153)
```php
mysqli_commit($conn); // Both accounts updated → save permanently
```

---

## 16. `mysqli_rollback()`

### 📖 What is it?
The **safety net** of transactions. If something goes wrong during a transaction (an error, an exception, invalid data), `rollback()` **completely undoes** all the SQL changes made since `begin_transaction()`. The database is left exactly as it was before the transaction started.

### 🔧 Syntax
```php
mysqli_rollback($conn);
```

### 📋 Parameters

| Parameter | Type | Description |
|---|---|---|
| `$conn` | mysqli | The active database connection. |

### 📤 Return Value
- `true` on success, `false` on failure.

### 📂 Where it's used in this project

**Client deletion rollback** [clients.php](file:///opt/lampp/htdocs/WebProjectV2-1/clients.php#L110)
```php
} catch (Exception $ex) {
    mysqli_rollback($conn);
    $msg = 'Deletion failed: ' . $ex->getMessage();
}
```

**Transfer failure rollback** [transactions.php](file:///opt/lampp/htdocs/WebProjectV2-1/transactions.php#L158-L160)
```php
} catch (Exception $ex) {
    mysqli_rollback($conn);
    $formError = $ex->getMessage(); // e.g. "Insufficient sender balance"
}
```

---

## 🔄 How Transactions Work — Complete Visualization

```
mysqli_begin_transaction($conn)
        │
        ▼
    ┌─────────────────────────────────────────┐
    │  SQL Operation 1: DELETE transactions    │
    │  SQL Operation 2: DELETE client          │
    │  SQL Operation 3: INSERT log entry       │
    └─────────────────────────────────────────┘
        │
        ├── ALL succeeded? ──▶ mysqli_commit($conn)
        │                         └─▶ Changes are PERMANENT ✅
        │
        └── ANY failed?    ──▶ mysqli_rollback($conn)
                                  └─▶ Database back to original state ❌
```

**Real-world analogy:** Imagine a bank transfer. The system must deduct $500 from Account A AND add $500 to Account B. If the system crashes after deducting but before adding, the money vanishes. A transaction prevents this — either both steps happen, or neither does.

---

## 🗺️ Complete Flow — From Connection to Data

Here is the complete journey of a typical database operation in this project:

```
1. config/db.php is included
        │
        ▼
2. mysqli_connect()           → Open connection to MySQL
3. mysqli_connect_error()     → Check if connection failed
4. mysqli_set_charset()       → Set utf8mb4 encoding
        │
        ▼
5. mysqli_prepare()           → Write SQL with ? placeholders
6. mysqli_stmt_bind_param()   → Fill in actual values safely
7. mysqli_stmt_execute()      → Send query to MySQL server
        │
        ├── For SELECT queries:
        │   8a. mysqli_stmt_get_result()  → Get result object
        │   8b. mysqli_fetch_assoc()      → Read rows one by one
        │       OR
        │   8b. mysqli_fetch_row()        → Read row as [0, 1, 2...]
        │
        └── For INSERT/UPDATE/DELETE:
            8a. mysqli_stmt_affected_rows() → Check rows changed
        │
        ▼
9. mysqli_stmt_close()        → Free statement memory
        │
        ▼
   (For complex operations with multiple steps:)
10. mysqli_begin_transaction() → Start atomic block
11. [multiple queries...]
12. mysqli_commit()            → Save all changes
    OR
12. mysqli_rollback()          → Undo all changes on error
```

---

## 📊 Function Usage Summary — By File

| Function | db.php | login.php | dashboard.php | clients.php | transactions.php | users.php | api_balance.php |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `mysqli_connect` | ✅ | | | | | | |
| `mysqli_connect_error` | ✅ | | | | | | |
| `mysqli_set_charset` | ✅ | | | | | | |
| `mysqli_query` | | | ✅ | ✅ | ✅ | ✅ | |
| `mysqli_fetch_row` | | | ✅ | ✅ | | ✅ | |
| `mysqli_fetch_assoc` | | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `mysqli_real_escape_string` | | | | ✅ | | | |
| `mysqli_prepare` | | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `mysqli_stmt_bind_param` | | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `mysqli_stmt_execute` | | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `mysqli_stmt_get_result` | | ✅ | | ✅ | ✅ | ✅ | ✅ |
| `mysqli_stmt_affected_rows` | | | | ✅ | | | |
| `mysqli_stmt_close` | | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `mysqli_begin_transaction` | | | | ✅ | ✅ | | |
| `mysqli_commit` | | | | ✅ | ✅ | | |
| `mysqli_rollback` | | | | ✅ | ✅ | | |

---

*Report generated for Prototype Bank — WebProjectV2 | MySQLi Procedural Edition*

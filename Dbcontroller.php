<?php
/**
 * Database layer.
 *
 * Changes from the original:
 *   - mysqli_report(OFF) so a failed connect returns false instead of throwing
 *     an uncaught exception that prints a stack trace (H7)
 *   - connect failure is logged, never redirected to with the error in the query
 *     string, and never printed to the browser
 *   - runBaseQuery / runQuery / run / insert / update all check their result
 *     before iterating, and initialise their return value (H7)
 *   - prepareAndExecute no longer die()s with SQL text
 *   - clean() is kept for legacy callers but new code should use prepared
 *     statements; it also no longer returns null for a null input
 */

class DBController
{
    private string $host     = 'localhost';
    private string $user     = 'root';
    private string $password = '';
    private string $database = 'certificatedb';
    private ?mysqli $conn     = null;

    public function __construct()
    {
        $configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($configFile)) {
            require_once $configFile;
            if (defined('DB_HOST'))     $this->host     = (string)DB_HOST;
            if (defined('DB_USER'))     $this->user     = (string)DB_USER;
            if (defined('DB_PASSWORD')) $this->password = (string)DB_PASSWORD;
            if (defined('DB_NAME'))     $this->database = (string)DB_NAME;
        }
        $this->conn = $this->connectDB();
    }

    /** Raw connection, for callers that pass it to the security.php helpers. */
    public function link(): ?mysqli
    {
        return $this->conn;
    }

    public function isConnected(): bool
    {
        return $this->conn instanceof mysqli;
    }

    private function connectDB(): ?mysqli
    {
        // PHP 8.1+ throws by default. Turn that off so the failure path is ours.
        if (function_exists('mysqli_report')) {
            mysqli_report(MYSQLI_REPORT_OFF);
        }

        try {
            $conn = @mysqli_connect($this->host, $this->user, $this->password, $this->database);
        } catch (Throwable $e) {
            error_log('DB connect threw: ' . $e->getMessage());
            return null;
        }

        if (!$conn) {
            // Logged, never echoed, never put in a redirect URL.
            error_log('DB connect failed: ' . mysqli_connect_error());
            return null;
        }

        @mysqli_set_charset($conn, 'utf8mb4');
        return $conn;
    }

    /**
     * Run a query and return all rows.
     * Returns [] on failure and on "no rows" alike — callers only ever need
     * emptiness, and a broken query should not look like data.
     */
    public function runBaseQuery(string $query): array
    {
        if (!$this->conn instanceof mysqli) {
            return [];
        }
        $result = @mysqli_query($this->conn, $query);
        if (!$result instanceof mysqli_result) {
            if (mysqli_error($this->conn)) {
                error_log('Query failed: ' . mysqli_error($this->conn));
            }
            return [];
        }

        $resultset = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $resultset[] = $row;
        }
        mysqli_free_result($result);
        return $resultset;
    }

    /** Prepared query returning all rows. */
    public function runQuery(string $query, string $param_type = '', array $param_value_array = []): array
    {
        if (!$this->conn instanceof mysqli) {
            return [];
        }
        $stmt = $this->conn->prepare($query);
        if (!$stmt instanceof mysqli_stmt) {
            error_log('Prepare failed: ' . $this->conn->error);
            return [];
        }
        if ($param_type !== '' && $param_value_array) {
            $this->bindQueryParams($stmt, $param_type, $param_value_array);
        }
        if (!$stmt->execute()) {
            error_log('Execute failed: ' . $stmt->error);
            $stmt->close();
            return [];
        }

        $resultset = [];
        $result = $stmt->get_result();
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $resultset[] = $row;
            }
            $result->free();
        }
        $stmt->close();
        return $resultset;
    }

    private function bindQueryParams(mysqli_stmt $stmt, string $param_type, array $param_value_array): void
    {
        $refs = [$param_type];
        foreach ($param_value_array as $k => $v) {
            $refs[] = &$param_value_array[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    /** Raw query. Returns the mysqli_result, or false. */
    public function run(string $query)
    {
        if (!$this->conn instanceof mysqli) {
            return false;
        }
        $result = @mysqli_query($this->conn, $query);
        if ($result === false && mysqli_error($this->conn)) {
            error_log('Query failed: ' . mysqli_error($this->conn));
        }
        return $result;
    }

    public function getLastInsertID(): int
    {
        if (!$this->conn instanceof mysqli) {
            return 0;
        }
        return (int)mysqli_insert_id($this->conn);
    }

    /** Legacy escaping. Prefer prepared statements in new code. */
    public function clean(?string $variable): string
    {
        if ($variable === null) {
            return '';
        }
        if (!$this->conn instanceof mysqli) {
            return $variable;
        }
        return (string)mysqli_real_escape_string($this->conn, $variable);
    }

    public function setCharset(string $charset): bool
    {
        if (!$this->conn instanceof mysqli) {
            return false;
        }
        return $this->conn->set_charset($charset);
    }

    public function closeConnection(): void
    {
        if ($this->conn instanceof mysqli) {
            mysqli_close($this->conn);
            $this->conn = null;
        }
    }

    /* ---------------------------- transactions --------------------------- */

    public function beginTransaction(): void
    {
        if ($this->conn instanceof mysqli) {
            $this->conn->begin_transaction();
        }
    }

    public function commitTransaction(): void
    {
        if ($this->conn instanceof mysqli) {
            $this->conn->commit();
        }
    }

    public function rollbackTransaction(): void
    {
        if ($this->conn instanceof mysqli) {
            $this->conn->rollback();
        }
    }

    /* ------------------------- prepared execution ------------------------ */

    /**
     * Execute a statement with bound parameters.
     *
     * @return mysqli_result|int  rows for SELECT, affected rows / insert id otherwise
     */
    public function prepareAndExecute(string $query, array $params = [])
    {
        if (!$this->conn instanceof mysqli) {
            return 0;
        }
        $stmt = $this->conn->prepare($query);
        if (!$stmt instanceof mysqli_stmt) {
            error_log('Prepare failed: ' . $this->conn->error);
            return 0;
        }

        $param_types = $this->detectParamTypes($params);
        if ($params) {
            $refs = [$param_types];
            foreach ($params as $k => $v) {
                $refs[] = &$params[$k];
            }
            call_user_func_array([$stmt, 'bind_param'], $refs);
        }

        if (!$stmt->execute()) {
            error_log('Execute failed: ' . $stmt->error);
            $stmt->close();
            return 0;
        }

        $upper = strtoupper(ltrim($query));
        if (str_starts_with($upper, 'SELECT') || str_starts_with($upper, 'WITH')) {
            $result = $stmt->get_result();
            $stmt->close();
            return $result;
        }
        if (str_starts_with($upper, 'INSERT')) {
            $id = (int)$stmt->insert_id;
            $stmt->close();
            return $id;
        }
        $affected = (int)$stmt->affected_rows;
        $stmt->close();
        return $affected;
    }

    private function detectParamTypes(array $params): string
    {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param))        $types .= 'i';
            elseif (is_float($param))  $types .= 'd';
            else                       $types .= 's';
        }
        return $types;
    }

    /* ------------------------------ writes ------------------------------- */

    /** INSERT. Returns true on success. */
    public function insert(string $query, string $param_type, array $param_value_array): bool
    {
        return $this->runWrite($query, $param_type, $param_value_array);
    }

    public function insertNew(string $query, string $param_type, array $param_value_array): bool
    {
        return $this->runWrite($query, $param_type, $param_value_array);
    }

    /** UPDATE. Returns true on success. */
    public function update(string $query, string $param_type, array $param_value_array): bool
    {
        return $this->runWrite($query, $param_type, $param_value_array);
    }

    private function runWrite(string $query, string $param_type, array $param_value_array): bool
    {
        if (!$this->conn instanceof mysqli) {
            return false;
        }
        $stmt = $this->conn->prepare($query);
        if (!$stmt instanceof mysqli_stmt) {
            error_log('Prepare failed: ' . $this->conn->error);
            return false;
        }
        if ($param_type !== '' && $param_value_array) {
            $this->bindQueryParams($stmt, $param_type, $param_value_array);
        }
        $ok = $stmt->execute();
        if (!$ok) {
            error_log('Execute failed: ' . $stmt->error);
        }
        $stmt->close();
        return $ok;
    }

    /** INSERT returning the statement, for callers that inspect affected_rows. */
    public function insertReturnSQL(string $query, string $param_type, array $param_value_array)
    {
        if (!$this->conn instanceof mysqli) {
            return null;
        }
        $stmt = $this->conn->prepare($query);
        if (!$stmt instanceof mysqli_stmt) {
            error_log('Prepare failed: ' . $this->conn->error);
            return null;
        }
        if ($param_type !== '' && $param_value_array) {
            $this->bindQueryParams($stmt, $param_type, $param_value_array);
        }
        if (!$stmt->execute()) {
            error_log('Execute failed: ' . $stmt->error);
        }
        return $stmt;
    }

    /* ----------------------------- lookups ------------------------------- */

    public function getMemberByUsername(string $username): array
    {
        return $this->runQuery(
            'SELECT * FROM members WHERE member_name = ?',
            's',
            [$username]
        );
    }

    public function insertMember(string $name, string $password, $role, string $email)
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        return $this->insertReturnSQL(
            'INSERT INTO members (member_name, member_password, member_email, member_level) VALUES (?,?,?,?)',
            'sssi',
            [$name, $hash, $email, (int)$role]
        );
    }
}

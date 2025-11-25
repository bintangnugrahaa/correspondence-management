<?php
date_default_timezone_set("Asia/Jakarta");

/**
 * Database connection function with error handling
 */
function conn($host, $username, $password, $database)
{
    try {
        $conn = mysqli_connect($host, $username, $password, $database);

        if (!$conn) {
            throw new RuntimeException("Database connection failed: " . mysqli_connect_error());
        }

        // Set character set to UTF-8
        mysqli_set_charset($conn, "utf8mb4");

        return $conn;
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Convert date to Indonesian format
 */
function indoDate($date)
{
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }

    try {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date; // Return original if invalid date
        }

        $day = date('d', $timestamp);
        $month = month(date('m', $timestamp));
        $year = date('Y', $timestamp);

        return $day . ' ' . $month . ' ' . $year;
    } catch (Exception $e) {
        error_log("Date conversion error: " . $e->getMessage());
        return $date;
    }
}

/**
 * Convert month number to Indonesian month name
 */
function month($monthNumber)
{
    $months = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember'
    ];

    return $months[$monthNumber] ?? $monthNumber;
}

/**
 * Backup database to SQL file
 */
function backup($host, $user, $pass, $dbname, $fileName, $tables)
{
    try {
        $link = conn($host, $user, $pass, $dbname);
        $backupContent = generateBackupContent($link, $tables);

        return saveBackupFile($fileName, $backupContent);

    } catch (Exception $e) {
        error_log("Backup error: " . $e->getMessage());
        throw new RuntimeException("Backup failed: " . $e->getMessage());
    }
}

/**
 * Generate backup SQL content
 */
function generateBackupContent($connection, $tables)
{
    $content = "";

    // Get tables to backup
    $tablesToBackup = getTablesToBackup($connection, $tables);

    foreach ($tablesToBackup as $table) {
        $content .= backupTableStructure($connection, $table);
        $content .= backupTableData($connection, $table);
    }

    return $content;
}

/**
 * Get list of tables to backup
 */
function getTablesToBackup($connection, $tables)
{
    if ($tables === '*') {
        $tables = [];
        $result = mysqli_query($connection, 'SHOW TABLES');
        if (!$result) {
            throw new RuntimeException("Failed to get table list: " . mysqli_error($connection));
        }

        while ($row = mysqli_fetch_row($result)) {
            $tables[] = $row[0];
        }
    } else {
        $tables = is_array($tables) ? $tables : explode(',', $tables);
    }

    return $tables;
}

/**
 * Backup table structure
 */
function backupTableStructure($connection, $table)
{
    $content = "DROP TABLE IF EXISTS `$table`;\n";

    $result = mysqli_query($connection, "SHOW CREATE TABLE `$table`");
    if (!$result) {
        throw new RuntimeException("Failed to get table structure for $table: " . mysqli_error($connection));
    }

    $row = mysqli_fetch_row($result);
    $content .= $row[1] . ";\n\n";

    return $content;
}

/**
 * Backup table data
 */
function backupTableData($connection, $table)
{
    $content = "";
    $result = mysqli_query($connection, "SELECT * FROM `$table`");

    if (!$result) {
        throw new RuntimeException("Failed to get table data for $table: " . mysqli_error($connection));
    }

    $numFields = mysqli_num_fields($result);

    while ($row = mysqli_fetch_row($result)) {
        $content .= "INSERT INTO `$table` VALUES(";

        for ($j = 0; $j < $numFields; $j++) {
            $row[$j] = mysqli_real_escape_string($connection, $row[$j]);
            $row[$j] = str_replace("\n", "\\n", $row[$j]);

            if (isset($row[$j])) {
                $content .= '"' . $row[$j] . '"';
            } else {
                $content .= 'NULL';
            }

            if ($j < ($numFields - 1)) {
                $content .= ',';
            }
        }
        $content .= ");\n";
    }

    $content .= "\n\n";
    return $content;
}

/**
 * Save backup to file
 */
function saveBackupFile($fileName, $content)
{
    $backupDir = "backup/";

    // Create backup directory if it doesn't exist
    if (!is_dir($backupDir)) {
        if (!mkdir($backupDir, 0755, true)) {
            throw new RuntimeException("Failed to create backup directory");
        }
    }

    // Validate filename
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.sql$/', $fileName)) {
        throw new InvalidArgumentException("Invalid backup filename");
    }

    $filePath = $backupDir . $fileName;

    if (file_put_contents($filePath, $content) === false) {
        throw new RuntimeException("Failed to write backup file");
    }

    return $filePath;
}

/**
 * Restore database from SQL file
 */
function restore($host, $user, $pass, $dbname, $uploadedFile)
{
    try {
        validateRestoreInput($uploadedFile);

        $connection = conn($host, $user, $pass, $dbname);
        $filePath = processUploadedFile($uploadedFile);

        executeSqlFile($connection, $filePath);

        // Clean up temporary file
        unlink($filePath);

        $_SESSION['succRestore'] = 'SUKSES! Database berhasil direstore';
        header("Location: ./admin.php?page=sett&sub=rest");
        exit();

    } catch (Exception $e) {
        error_log("Restore error: " . $e->getMessage());

        // Clean up on error
        if (isset($filePath) && file_exists($filePath)) {
            unlink($filePath);
        }

        throw $e;
    }
}

/**
 * Validate restore input parameters
 */
function validateRestoreInput($uploadedFile)
{
    if (empty($uploadedFile['name']) || empty($_REQUEST['password'])) {
        $_SESSION['errEmpty'] = 'ERROR! Semua Form wajib diisi';
        header("Location: ./admin.php?page=sett&sub=rest");
        exit();
    }

    if (!verifyUserPassword($_SESSION['id_user'], $_REQUEST['password'])) {
        session_destroy();
        throw new RuntimeException("Invalid password provided for restore operation");
    }
}

/**
 * Verify user password for restore operation
 */
function verifyUserPassword($userId, $password)
{
    global $config;

    $userId = mysqli_real_escape_string($config, $userId);
    $passwordHash = md5($password); // Note: Consider upgrading to password_hash()

    $query = mysqli_query(
        $config,
        "SELECT password FROM tbl_user WHERE id_user='$userId' AND password='$passwordHash'"
    );

    return $query && mysqli_num_rows($query) > 0;
}

/**
 * Process and validate uploaded SQL file
 */
function processUploadedFile($uploadedFile)
{
    // Validate file upload
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("File upload failed with error code: " . $uploadedFile['error']);
    }

    // Validate file type
    $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
    if ($fileExtension !== 'sql') {
        $_SESSION['errFormat'] = 'ERROR! Format file yang diperbolehkan hanya *.SQL';
        header("Location: ./admin.php?page=sett&sub=rest");
        exit();
    }

    // Validate file size (max 50MB)
    if ($uploadedFile['size'] > 50 * 1024 * 1024) {
        throw new RuntimeException("File size too large");
    }

    // Create restore directory
    $restoreDir = "restore/";
    if (!is_dir($restoreDir)) {
        mkdir($restoreDir, 0755, true);
    }

    // Generate safe filename
    $safeFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $uploadedFile['name']);
    $filePath = $restoreDir . $safeFileName;

    // Move uploaded file
    if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
        throw new RuntimeException("Failed to move uploaded file");
    }

    return $filePath;
}

/**
 * Execute SQL file against database
 */
function executeSqlFile($connection, $filePath)
{
    if (!file_exists($filePath)) {
        throw new RuntimeException("SQL file not found");
    }

    $sql = file_get_contents($filePath);
    if ($sql === false) {
        throw new RuntimeException("Failed to read SQL file");
    }

    // Split SQL file into individual queries
    $queries = splitSqlQueries($sql);

    // Execute each query
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            if (!mysqli_query($connection, $query)) {
                throw new RuntimeException("Query execution failed: " . mysqli_error($connection) . " - Query: " . substr($query, 0, 100));
            }
        }
    }
}

/**
 * Split SQL file into individual queries
 */
function splitSqlQueries($sql)
{
    // Remove comments
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    // Split by semicolon, but ignore semicolons within quotes
    $queries = [];
    $currentQuery = '';
    $inString = false;
    $stringChar = '';

    for ($i = 0; $i < strlen($sql); $i++) {
        $char = $sql[$i];

        if (($char === "'" || $char === '"') && ($i === 0 || $sql[$i - 1] !== "\\")) {
            if (!$inString) {
                $inString = true;
                $stringChar = $char;
            } elseif ($char === $stringChar) {
                $inString = false;
            }
        }

        $currentQuery .= $char;

        if ($char === ';' && !$inString) {
            $queries[] = $currentQuery;
            $currentQuery = '';
        }
    }

    // Add the last query if any
    if (!empty(trim($currentQuery))) {
        $queries[] = $currentQuery;
    }

    return $queries;
}
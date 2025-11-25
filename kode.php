<?php
require_once 'include/config.php';
require_once 'include/functions.php';

// Prevent direct access to this Ajax source
if (!isAjaxRequest()) {
    header('HTTP/1.1 403 Forbidden');
    echo 'No direct access source!';
    exit();
}

// Set content type header
header("Content-Type: application/json; charset=UTF-8");

// Handle the autocomplete request
handleAutocompleteRequest();

/**
 * Check if request is AJAX
 */
function isAjaxRequest()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
}

/**
 * Handle autocomplete request
 */
function handleAutocompleteRequest()
{
    // Validate and sanitize input
    $query = validateAndSanitizeInput();
    if (!$query) {
        sendErrorResponse('Invalid query parameter');
        return;
    }

    // Get database connection
    $conn = getDatabaseConnection();
    if (!$conn) {
        sendErrorResponse('Database connection failed');
        return;
    }

    // Search for klasifikasi data
    $results = searchKlasifikasi($conn, $query);

    // Format and send response
    sendAutocompleteResponse($results);
}

/**
 * Validate and sanitize input parameter
 */
function validateAndSanitizeInput()
{
    if (!isset($_GET["query"]) || empty(trim($_GET["query"]))) {
        return null;
    }

    $query = trim($_GET["query"]);

    // Basic input validation - allow alphanumeric, spaces, and common punctuation
    if (!preg_match('/^[a-zA-Z0-9 .,\-_]+$/', $query)) {
        return null;
    }

    // Limit query length to prevent abuse
    if (strlen($query) > 100) {
        $query = substr($query, 0, 100);
    }

    return $query;
}

/**
 * Get database connection
 */
function getDatabaseConnection()
{
    global $host, $username, $password, $database;

    try {
        return conn($host, $username, $password, $database);
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

/**
 * Search klasifikasi data
 */
function searchKlasifikasi($conn, $query)
{
    // Sanitize query for SQL
    $sanitizedQuery = $conn->real_escape_string($query);

    // Prepare SQL query with parameterized approach would be better, 
    // but using real_escape_string for compatibility
    $sql = "SELECT * FROM tbl_klasifikasi 
            WHERE kode LIKE '%$sanitizedQuery%' OR nama LIKE '%$sanitizedQuery%' 
            ORDER BY kode DESC 
            LIMIT 10"; // Limit results for performance

    try {
        $result = $conn->query($sql);
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Database query error: " . $e->getMessage());
    }

    return [];
}

/**
 * Format and send autocomplete response
 */
function sendAutocompleteResponse($results)
{
    $output = ['suggestions' => []];

    foreach ($results as $data) {
        $output['suggestions'][] = [
            'value' => htmlspecialchars($data['kode'] . " " . $data['nama'], ENT_QUOTES, 'UTF-8'),
            'kode' => htmlspecialchars($data['kode'], ENT_QUOTES, 'UTF-8'),
            'nama' => htmlspecialchars($data['nama'], ENT_QUOTES, 'UTF-8'),
            'uraian' => htmlspecialchars($data['uraian'] ?? '', ENT_QUOTES, 'UTF-8')
        ];
    }

    // Add debug info in development
    if (isDevelopment()) {
        $output['debug'] = [
            'result_count' => count($results),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    echo json_encode($output, JSON_UNESCAPED_UNICODE);
}

/**
 * Send error response
 */
function sendErrorResponse($message)
{
    $response = [
        'suggestions' => [],
        'error' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
    ];

    if (isDevelopment()) {
        $response['debug'] = [
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

/**
 * Check if in development environment
 */
function isDevelopment()
{
    return $_SERVER['SERVER_NAME'] === 'localhost' ||
        $_SERVER['SERVER_ADDR'] === '127.0.0.1' ||
        (defined('ENVIRONMENT') && ENVIRONMENT === 'development');
}
?>
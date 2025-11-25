<?php
/**
 * Logout Script
 * 
 * Securely terminates user session and redirects to login page
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Securely destroy the session
secureLogout();

// Redirect to login page
redirectToLogin();

/**
 * Securely destroy user session
 */
function secureLogout()
{
    // Unset all session variables
    $_SESSION = array();

    // If session cookie exists, invalidate it
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Finally, destroy the session
    session_destroy();
}

/**
 * Redirect to login page with security headers
 */
function redirectToLogin()
{
    // Prevent caching of the logout page
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Security headers
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");

    // Redirect to login page
    header("Location: ./");
    exit();
}

// Alternative minimal version for simple use cases:
/*
<?php
session_start();
$_SESSION = array();
session_destroy();
header("Location: ./");
exit();
*/
?>
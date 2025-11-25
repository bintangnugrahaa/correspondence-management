<?php
ob_start();
session_start();

/**
 * Login Page - Aplikasi Pengarsipan Surat
 * 
 * Name        : Aplikasi Manajemen Surat Menyurat
 * Version     : v1.0.0
 * Description : Aplikasi untuk mencatat data surat masuk dan keluar secara digital.
 * Date        : 2019
 * Developer   : Muhammad Bintang Nugraha
 * Phone/WA    : +62 851-5534-4998
 * Email       : muhammadbintangnugraha18@gmail.com
 * Website     : https://bintangnugraha.vercel.app
 */

// Check if user is already logged in
if (isset($_SESSION['admin'])) {
    header("Location: ./admin.php");
    exit();
}

require_once 'include/config.php';
require_once 'include/functions.php';
$config = conn($host, $username, $password, $database);

// Handle login form submission
if (isset($_REQUEST['submit'])) {
    handleLogin($config);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Aplikasi Pengarsipan Surat</title>

    <!-- Meta START -->
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
    <?php renderFavicon($config); ?>
    <!-- Meta END -->

    <!--[if lt IE 9]>
    <script src="../asset/js/html5shiv.min.js"></script>
    <![endif]-->

    <!-- Global style START -->
    <link type="text/css" rel="stylesheet" href="asset/css/materialize.css">
    <style type="text/css">
        body {
            background: #fff;
        }

        .bg::before {
            content: '';
            background-image: url('./asset/img/kampleng.jpg');
            background-repeat: no-repeat;
            background-size: cover;
            background-attachment: fixed;
            position: absolute;
            z-index: -1;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            opacity: 100;
            filter: alpha(opacity=10);
            height: 100%;
            width: 100%;
        }

        @media only screen and (min-width: 993px) {
            .container {
                width: 60% !important;
            }
        }

        .container {
            max-width: 100%;
            margin-top: 2.5rem;
        }

        #logo {
            display: block;
            margin: -20px auto -5px;
        }

        img {
            border-radius: 50%;
            margin: 0 auto;
            width: 100px;
            height: 100px;
        }

        #login {
            margin-top: -2%;
        }

        #smk {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .batas {
            border-bottom: 1px dotted #444;
            margin: 0 auto;
            width: 90%;
        }

        #title {
            margin: 5px 0 35px;
        }

        .btn-large {
            font-size: 1.25rem;
            margin: 0;
        }

        #alert-message {
            border-radius: 3px;
            color: #f44336;
            font-size: 1.15rem;
            margin: 15px 15px -15px;
        }

        .error {
            padding: 10px;
        }

        .upss {
            font-size: 1.2rem;
            margin-left: 20px;
        }

        .pace {
            -webkit-pointer-events: none;
            pointer-events: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            -webkit-transform: translate3d(0, -50px, 0);
            -ms-transform: translate3d(0, -50px, 0);
            transform: translate3d(0, -50px, 0);
            -webkit-transition: -webkit-transform .5s ease-out;
            -ms-transition: -webkit-transform .5s ease-out;
            transition: transform .5s ease-out;
        }

        .pace.pace-active {
            -webkit-transform: translate3d(0, 0, 0);
            -ms-transform: translate3d(0, 0, 0);
            transform: translate3d(0, 0, 0);
        }

        .pace .pace-progress {
            display: block;
            position: fixed;
            z-index: 2000;
            top: 0;
            right: 100%;
            width: 100%;
            height: 3px;
            background: #2196f3;
            pointer-events: none;
        }

        noscript {
            color: #42a5f5;
        }

        .input-field label {
            font-size: 1.2rem;
        }

        .input-field label.active {
            font-size: 1rem;
        }
    </style>
    <!-- Global style END -->
</head>

<body class="blue-grey lighten-3 bg">
    <!-- Container START -->
    <div class="container">
        <!-- Row START -->
        <div class="row">
            <!-- Col START -->
            <div class="col s12 m6 offset-m3">
                <!-- Box START -->
                <div class="card-panel z-depth-2" id="login">
                    <!-- Row Form START -->
                    <div class="row">
                        <?php renderLoginHeader($config); ?>
                        <?php renderLoginContent($config); ?>
                    </div>
                    <!-- Row Form END -->
                </div>
                <!-- Box END-->
            </div>
            <!-- Col END -->
        </div>
        <!-- Row END -->
    </div>
    <!-- Container END -->

    <!-- Javascript START -->
    <?php renderJavascriptAssets(); ?>
    <!-- Javascript END -->

    <noscript>
        <meta http-equiv="refresh" content="0;URL='/enable-javascript.html'" />
    </noscript>
</body>

</html>

<?php
/**
 * Handle login form submission
 */
function handleLogin($config)
{
    // Validate required fields
    if (empty($_REQUEST['username']) || empty($_REQUEST['password'])) {
        displayLoginError();
        return;
    }

    // Sanitize and validate input
    $username = trim($_REQUEST['username']);
    $password = trim($_REQUEST['password']);

    // Authenticate user
    $user = authenticateUser($config, $username, $password);

    if ($user) {
        createUserSession($user);
        header("Location: ./admin.php");
        exit();
    } else {
        $_SESSION['errLog'] = '<center>Username & Password tidak ditemukan!</center>';
        header("Location: ./");
        exit();
    }
}

/**
 * Authenticate user against database
 */
function authenticateUser($config, $username, $password)
{
    $username = mysqli_real_escape_string($config, $username);

    $query = mysqli_query(
        $config,
        "SELECT id_user, username, nama, nip, admin 
         FROM tbl_user 
         WHERE username=BINARY'$username' AND password=MD5('$password')"
    );

    if ($query && mysqli_num_rows($query) > 0) {
        return mysqli_fetch_assoc($query);
    }

    return false;
}

/**
 * Create user session after successful login
 */
function createUserSession($user)
{
    $_SESSION['id_user'] = $user['id_user'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['nip'] = $user['nip'];
    $_SESSION['admin'] = $user['admin'];
}

/**
 * Render favicon from institution data
 */
function renderFavicon($config)
{
    $query = mysqli_query($config, "SELECT logo from tbl_instansi");
    if ($query && $data = mysqli_fetch_array($query)) {
        $logo = htmlspecialchars($data[0], ENT_QUOTES, 'UTF-8');
        echo '<link rel="shortcut icon" href="upload/' . $logo . '">';
    }
}

/**
 * Render login header with logo and title
 */
function renderLoginHeader($config)
{
    $query = mysqli_query($config, "SELECT * FROM tbl_instansi");
    if ($query && $data = mysqli_fetch_array($query)) {
        ?>
        <!-- Logo and title START -->
        <div class="col s12">
            <div class="card-content">
                <br><br><img id="logo" src="upload/<?php echo htmlspecialchars($data['logo']); ?>" alt="Logo Institusi">
                <h4 class="center" id="smk">L O G I N</h4>
                <div class="batas"></div>
            </div>
        </div>
        <!-- Logo and title END -->
        <?php
    }
}

/**
 * Render login content (form or error message)
 */
function renderLoginContent($config)
{
    if (isset($_REQUEST['submit'])) {
        // Form submission is handled in handleLogin()
        // This condition is kept for backward compatibility
    } else {
        renderLoginForm();
    }
}

/**
 * Display login error with back button
 */
function displayLoginError()
{
    ?>
    <div class="upss red-text">
        <i class="material-icons">error_outline</i> <strong>ERROR!</strong> Username dan Password wajib diisi.
        <a class="btn-large waves-effect waves-light blue-grey col s11" href="" style="margin: 20px 0 0 5px;">
            <i class="material-icons md-24">arrow_back</i> Kembali ke login form
        </a>
    </div>
    <?php
}

/**
 * Render login form
 */
function renderLoginForm()
{
    ?>
    <!-- Form START -->
    <form class="col s12" method="POST" action="">
        <div class="row">
            <?php displayLoginMessages(); ?>
        </div>
        <div class="input-field col s12">
            <i class="material-icons prefix md-prefix">account_circle</i>
            <input id="username" type="text" class="validate" name="username" required>
            <label for="username">Username</label>
        </div>
        <div class="input-field col s12">
            <i class="material-icons prefix md-prefix">lock</i>
            <input id="password" type="password" class="validate" name="password" required>
            <label for="password">Password</label>
        </div>
        <div class="input-field col s12">
            <button type="submit" class="btn-large waves-effect waves-light blue-grey col s12" name="submit">
                LOGIN
            </button>
        </div>
    </form>
    <!-- Form END -->
    <?php
}

/**
 * Display login error/success messages
 */
function displayLoginMessages()
{
    if (isset($_SESSION['errLog'])) {
        ?>
        <div id="alert-message" class="error red lighten-5">
            <div class="center"><i class="material-icons">error_outline</i> <strong>LOGIN GAGAL!</strong></div>
            <?php echo $_SESSION['errLog']; ?>
        </div>
        <?php
        unset($_SESSION['errLog']);
    }

    if (isset($_SESSION['err'])) {
        ?>
        <div id="alert-message" class="error red lighten-5">
            <div class="center"><i class="material-icons">error_outline</i> <strong>ERROR!</strong></div>
            <?php echo $_SESSION['err']; ?>
        </div>
        <?php
        unset($_SESSION['err']);
    }
}

/**
 * Render JavaScript assets
 */
function renderJavascriptAssets()
{
    ?>
    <script type="text/javascript" src="asset/js/jquery-2.1.1.min.js"></script>
    <script type="text/javascript" src="asset/js/materialize.min.js"></script>
    <script type="text/javascript" src="asset/js/bootstrap.min.js"></script>
    <script data-pace-options='{ "ajax": false }' src='./asset/js/pace.min.js'></script>

    <!-- Jquery auto hide untuk menampilkan pesan error -->
    <script type="text/javascript">
        $("#alert-message").alert().delay(3000).slideUp('slow');
    </script>
    <?php
}
<?php
// Check session and authorization
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<strong>ERROR!</strong> Anda harus login terlebih dahulu.';
    header("Location: ./");
    exit();
}

if ($_SESSION['admin'] != 1) {
    echo '<script language="javascript">
            window.alert("ERROR! Anda tidak memiliki hak akses untuk membuka halaman ini");
            window.location.href="./logout.php";
          </script>';
    exit();
}

// Handle restore process
if (isset($_POST['restore'])) {
    handleDatabaseRestore();
} else {
    displayRestorePage();
}

/**
 * Handle database restore process
 */
function handleDatabaseRestore()
{
    // Validate password
    if (!validateUserPassword($_POST['password'])) {
        $_SESSION['errEmpty'] = 'ERROR! Password yang Anda masukkan salah';
        header("Location: ./admin.php?page=sett&sub=rest");
        exit();
    }

    // Validate file upload
    $fileValidation = validateUploadedFile();
    if (!$fileValidation['success']) {
        $_SESSION[$fileValidation['error_type']] = $fileValidation['message'];
        header("Location: ./admin.php?page=sett&sub=rest");
        exit();
    }

    // Perform restore
    global $host, $username, $password, $database;
    $restoreResult = restore($host, $username, $password, $database, $_FILES['file']);

    if ($restoreResult) {
        $_SESSION['succRestore'] = 'SUKSES! Database berhasil direstore';
    } else {
        $_SESSION['errUpload'] = 'ERROR! Gagal melakukan restore database';
    }

    header("Location: ./admin.php?page=sett&sub=rest");
    exit();
}

/**
 * Validate user password
 */
function validateUserPassword($inputPassword)
{
    global $config;

    $userId = $_SESSION['id_user'];
    $query = mysqli_query($config, "SELECT password FROM tbl_user WHERE id_user = '$userId'");

    if ($query && mysqli_num_rows($query) > 0) {
        $user = mysqli_fetch_assoc($query);
        // Assuming passwords are stored using password_hash()
        return password_verify($inputPassword, $user['password']);
    }

    return false;
}

/**
 * Validate uploaded file
 */
function validateUploadedFile()
{
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'error_type' => 'errUpload',
            'message' => 'ERROR! Gagal mengupload file'
        ];
    }

    if ($_FILES['file']['size'] == 0) {
        return [
            'success' => false,
            'error_type' => 'errEmpty',
            'message' => 'ERROR! File tidak boleh kosong'
        ];
    }

    $fileName = $_FILES['file']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($fileExtension !== 'sql') {
        return [
            'success' => false,
            'error_type' => 'errFormat',
            'message' => 'ERROR! Format file yang diperbolehkan hanya *.SQL'
        ];
    }

    // Additional file size validation (optional)
    $maxFileSize = 100 * 1024 * 1024; // 100MB
    if ($_FILES['file']['size'] > $maxFileSize) {
        return [
            'success' => false,
            'error_type' => 'errUpload',
            'message' => 'ERROR! File terlalu besar. Maksimal 100MB'
        ];
    }

    return ['success' => true];
}

/**
 * Display restore page
 */
function displayRestorePage()
{
    echo re_renderHeader();
    re_displayAlertMessages();
    echo renderRestoreForm();
}

/**
 * Render page header
 */
function re_renderHeader()
{
    return '<!-- Row Start -->
            <div class="row">
                <!-- Secondary Nav START -->
                <div class="col s12">
                    <div class="z-depth-1">
                        <nav class="secondary-nav">
                            <div class="nav-wrapper blue-grey darken-1">
                                <div class="col m12">
                                    <ul class="left">
                                        <li class="waves-effect waves-light">
                                            <a href="?page=sett&sub=rest" class="judul">
                                                <i class="material-icons">storage</i> Restore Database
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </nav>
                    </div>
                </div>
                <!-- Secondary Nav END -->
            </div>
            <!-- Row END -->';
}

/**
 * Display alert messages
 */
function re_displayAlertMessages()
{
    $alertTypes = [
        'errEmpty' => 'red',
        'errFormat' => 'red',
        'errUpload' => 'red',
        'succRestore' => 'green'
    ];

    foreach ($alertTypes as $sessionKey => $color) {
        if (isset($_SESSION[$sessionKey])) {
            echo '<div id="alert-message" class="row">
                    <div class="col m12">
                        <div class="card ' . $color . ' lighten-5">
                            <div class="card-content notif">
                                <span class="card-title ' . $color . '-text">
                                    <i class="material-icons md-36">' .
                ($color === 'green' ? 'done' : 'clear') . '</i> ' .
                htmlspecialchars($_SESSION[$sessionKey]) . '
                                </span>
                            </div>
                        </div>
                    </div>
                </div>';
            unset($_SESSION[$sessionKey]);
        }
    }
}

/**
 * Render restore form
 */
function renderRestoreForm()
{
    return '<!-- Row form Start -->
            <div class="row">
                <div class="col m12">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title black-text">Restore Database</span>
                            ' . renderWarningMessages() . '
                        </div>
                        <div class="card-action">
                            ' . renderRestoreFormFields() . '
                        </div>
                    </div>
                </div>
            </div>';
}

/**
 * Render warning messages
 */
function renderWarningMessages()
{
    return '<p class="kata">
                Silakan pilih file database lalu klik tombol <strong>"Restore"</strong> 
                untuk melakukan restore database dari hasil backup yang telah dibuat sebelumnya. 
                Jika belum ada file database hasil backup, silakan lakukan backup terlebih dahulu 
                melalui menu 
                <strong>
                    <a class="blue-text" style="text-transform: capitalize; margin-right: 0;" 
                       href="?page=sett&sub=back">"Backup Database"</a>.
                </strong>
            </p>
            <br/>
            <p class="kata">
                <span class="red-text">
                    <i class="material-icons">error_outline</i> 
                    <strong>PERINGATAN!</strong>
                </span>
                <br/>
                Berhati-hatilah ketika merestore database karena 
                <span class="error"><strong>data yang ada akan diganti dengan data yang baru</strong></span>. 
                Pastikan bahwa file database yang akan digunakan untuk merestore adalah 
                <strong>"benar-benar"</strong> file backup database yang telah dibuat sebelumnya 
                sehingga sistem dapat berjalan dengan normal dan tidak mengalami error.
            </p>';
}

/**
 * Render restore form fields
 */
function renderRestoreFormFields()
{
    return '<form method="post" enctype="multipart/form-data">
                <div class="file-field input-field col m6">
                    <div class="btn light-green darken-1">
                        <span>File</span>
                        <input type="file" name="file" accept=".sql" required>
                    </div>
                    <div class="file-path-wrapper">
                        <input class="file-path validate" 
                               placeholder="Upload file backup database sql" 
                               type="text">
                    </div>
                </div>
                <div class="input-field col s4">
                    <i class="material-icons prefix md-prefix">lock</i>
                    <input id="password" type="password" class="validate" 
                           name="password" required>
                    <label for="password">Masukkan password Anda</label>
                </div>
                &nbsp;&nbsp;&nbsp;&nbsp;
                <button type="submit" class="btn-large blue waves-effect waves-light" 
                        name="restore">
                    RESTORE <i class="material-icons">restore</i>
                </button>
            </form>';
}
?>
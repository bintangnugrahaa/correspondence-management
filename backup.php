<?php
// Check session and authorization
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
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

// Handle file download
if (isset($_REQUEST['nama_file'])) {
    handleBackupDownload($_REQUEST['nama_file']);
    exit();
}

// Handle backup process
if (isset($_REQUEST['backup'])) {
    handleDatabaseBackup();
} else {
    displayBackupPage();
}

/**
 * Handle backup file download
 */
function handleBackupDownload($fileName)
{
    $backupDir = "backup/";
    $filePath = $backupDir . basename($fileName); // Prevent directory traversal

    // Validate file extension
    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if ($fileExtension !== 'sql') {
        $_SESSION['err'] = 'ERROR! Format file yang boleh didownload hanya *.SQL';
        header("Location: ./admin.php?page=sett&sub=back");
        exit();
    }

    // Check if file exists
    if (!file_exists($filePath)) {
        $_SESSION['err'] = 'ERROR! File sudah tidak ada';
        header("Location: ./admin.php?page=sett&sub=back");
        exit();
    }

    // Download file
    downloadFile($filePath);
}

/**
 * Download file with proper headers
 */
function downloadFile($filePath)
{
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . basename($filePath));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));

    ob_clean();
    flush();

    if (readfile($filePath) === false) {
        $_SESSION['err'] = 'ERROR! Gagal mendownload file';
        header("Location: ./admin.php?page=sett&sub=back");
        exit();
    }

    exit();
}

/**
 * Handle database backup process
 */
function handleDatabaseBackup()
{
    $backupFileName = date("Y-m-d_His") . '.sql';

    // Perform backup
    if (backupDatabase($backupFileName)) {
        displayBackupSuccess($backupFileName);
    } else {
        $_SESSION['err'] = 'ERROR! Gagal melakukan backup database';
        header("Location: ./admin.php?page=sett&sub=back");
        exit();
    }
}

/**
 * Backup database
 */
function backupDatabase($fileName)
{
    global $host, $username, $password, $database;

    // Ensure backup directory exists
    $backupDir = "backup/";
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    // Perform backup using the existing backup function
    return backup($host, $username, $password, $database, $fileName, "*");
}

/**
 * Display backup success page
 */
function displayBackupSuccess($fileName)
{
    echo up_renderHeader();
    echo '<!-- Row form Start -->
          <div class="row">
            <div class="col m12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title black-text">
                            <div class="confirr green-text">
                                <i class="material-icons md-36">done</i>
                                SUKSES! Database berhasil dibackup
                            </div>
                        </span>
                        <p class="kata" style="margin-top: 10px;">
                            Silakan klik tombol <strong>"Download"</strong> dibawah ini 
                            untuk mendownload file backup database.
                        </p>
                    </div>
                    <div class="card-action">
                        <form method="post" enctype="multipart/form-data">
                            <a href="?page=sett&sub=back&nama_file=' . htmlspecialchars($fileName) . '" 
                               class="btn-large blue waves-effect waves-light white-text">
                                DOWNLOAD <i class="material-icons">file_download</i>
                            </a>
                        </form>
                    </div>
                </div>
            </div>
          </div>';
}

/**
 * Display main backup page
 */
function displayBackupPage()
{
    echo up_renderHeader();
    echo '<!-- Row form Start -->
          <div class="row">
            <div class="col m12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title black-text">Backup Database</span>
                        <p class="kata">
                            Lakukan backup database secara berkala untuk membuat cadangan database 
                            yang bisa direstore kapan saja ketika dibutuhkan. Silakan klik tombol 
                            <strong>"Backup"</strong> untuk memulai proses backup data. Setelah proses 
                            backup selesai, silakan download file backup database tersebut dan simpan 
                            di lokasi yang aman.<span class="red-text"><strong>*</strong></span>
                        </p>
                        <br/>
                        <p>
                            <span class="red-text"><strong>*</strong></span> 
                            Tidak disarankan menyimpan file backup database dalam my documents / Local Disk C.
                        </p>
                    </div>
                    <div class="card-action">
                        <form method="post" enctype="multipart/form-data">
                            <button type="submit" class="btn-large blue waves-effect waves-light" name="backup">
                                BACKUP <i class="material-icons">backup</i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
          </div>';
}

/**
 * Render page header
 */
function up_renderHeader()
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
                                            <a href="?page=sett&sub=back" class="judul">
                                                <i class="material-icons">storage</i> Backup Database
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
?>
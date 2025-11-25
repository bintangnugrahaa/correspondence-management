<?php
// Check session and authorization
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

if ($_SESSION['admin'] != 1 && $_SESSION['admin'] != 2) {
    echo '<script language="javascript">
            window.alert("ERROR! Anda tidak memiliki hak akses untuk membuka halaman ini");
            window.location.href="./logout.php";
          </script>';
    exit();
}

// Handle file upload
if (isset($_POST['submit'])) {
    handleFileUpload();
}

displayUploadPage();

/**
 * Handle CSV file upload and import
 */
function handleFileUpload()
{
    global $config;

    if (!isset($_FILES['file']) || $_FILES['file']['tmp_name'] == "") {
        $_SESSION['errEmpty'] = 'ERROR! Form File tidak boleh kosong';
        header("Location: ./admin.php?page=ref&act=imp");
        exit();
    }

    $file = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Validate file format
    if ($fileExtension !== 'csv') {
        $_SESSION['errFormat'] = 'ERROR! Format file yang diperbolehkan hanya *.CSV';
        header("Location: ./admin.php?page=ref&act=imp");
        exit();
    }

    // Validate upload
    if (!is_uploaded_file($file)) {
        $_SESSION['errUpload'] = 'ERROR! Proses upload data gagal';
        header("Location: ./admin.php?page=ref&act=imp");
        exit();
    }

    // Process CSV file
    if (processCSVFile($file)) {
        $_SESSION['succUpload'] = 'SUKSES! Data berhasil diimport';
        header("Location: ./admin.php?page=ref");
        exit();
    } else {
        $_SESSION['errUpload'] = 'ERROR! Gagal memproses file CSV';
        header("Location: ./admin.php?page=ref&act=imp");
        exit();
    }
}

/**
 * Process CSV file and import data
 */
function processCSVFile($file)
{
    global $config;

    $handle = fopen($file, "r");
    if (!$handle) {
        return false;
    }

    $id_user = $_SESSION['id_user'];
    $successCount = 0;
    $errorCount = 0;

    // Skip header row if needed, or start from first row
    $firstRow = true;

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // Skip empty rows
        if (empty($data[1]) && empty($data[2]) && empty($data[3])) {
            continue;
        }

        // Skip header row if it contains column names
        if ($firstRow) {
            $firstRow = false;
            // Optional: validate header row
            if (strtolower($data[1]) === 'kode' || strtolower($data[2]) === 'nama') {
                continue;
            }
        }

        // Sanitize data
        $kode = mysqli_real_escape_string($config, trim($data[1]));
        $nama = mysqli_real_escape_string($config, trim($data[2]));
        $uraian = mysqli_real_escape_string($config, trim($data[3]));

        // Validate required fields
        if (empty($kode) || empty($nama)) {
            $errorCount++;
            continue;
        }

        // Insert data
        $query = mysqli_query($config, "INSERT INTO tbl_klasifikasi 
                                      (id_klasifikasi, kode, nama, uraian, id_user) 
                                      VALUES (NULL, '$kode', '$nama', '$uraian', '$id_user')");

        if ($query) {
            $successCount++;
        } else {
            $errorCount++;
        }
    }

    fclose($handle);

    // Store import statistics in session for feedback
    if ($successCount > 0) {
        $_SESSION['import_stats'] = [
            'success' => $successCount,
            'errors' => $errorCount
        ];
        return true;
    }

    return false;
}

/**
 * Display the upload page
 */
function displayUploadPage()
{
    echo up_renderHeader();
    displayErrorMessages();
    echo renderUploadForm();
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
                                            <a href="?page=ref&act=imp" class="judul">
                                                <i class="material-icons">bookmark</i> Import Referensi Surat
                                            </a>
                                        </li>
                                        <li class="waves-effect waves-light">
                                            <a href="?page=ref">
                                                <i class="material-icons">arrow_back</i> Kembali
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
 * Display error messages
 */
function displayErrorMessages()
{
    $errorTypes = [
        'errFormat' => 'Format file tidak valid',
        'errUpload' => 'Upload gagal',
        'errEmpty' => 'File tidak boleh kosong'
    ];

    foreach ($errorTypes as $sessionKey => $defaultMessage) {
        if (isset($_SESSION[$sessionKey])) {
            echo '<div id="alert-message" class="row">
                    <div class="col m12">
                        <div class="card red lighten-5">
                            <div class="card-content notif">
                                <span class="card-title red-text">
                                    <i class="material-icons md-36">clear</i> '
                . htmlspecialchars($_SESSION[$sessionKey]) . '
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
 * Render upload form
 */
function renderUploadForm()
{
    return '<!-- Row form Start -->
            <div class="row">
                <div class="col m12">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title black-text">Import Referensi Kode Klasifikasi Surat</span>
                            <p class="kata">
                                Silakan pilih file referensi kode klasifikasi berformat *.csv (file excel) 
                                lalu klik tombol <strong>"Import"</strong> untuk melakukan import file. 
                                Contoh format file csv bisa di download melalui link dibawah ini.
                            </p>
                            <br/>
                            ' . renderDownloadLink() . '
                        </div>
                        <div class="card-action">
                            ' . renderUploadFormFields() . '
                        </div>
                    </div>
                </div>
            </div>';
}

/**
 * Render download link for CSV template
 */
function renderDownloadLink()
{
    // Handle download request
    if (isset($_REQUEST['download'])) {
        downloadTemplateFile();
    }

    return '<p>
                <form method="post" enctype="multipart/form-data">
                    <a href="?page=ref&act=imp&download" name="download" 
                       class="waves-effect waves-light blue-text">
                        <i class="material-icons">file_download</i> 
                        <strong>DOWNLOAD CONTOH FORMAT FILE CSV</strong>
                    </a>
                </form>
            </p>';
}

/**
 * Download template file
 */
function downloadTemplateFile()
{
    $dir = "./asset/";
    $file = $dir . "contoh_format.csv";

    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));

        ob_clean();
        flush();
        readfile($file);
        exit();
    }
}

/**
 * Render upload form fields
 */
function renderUploadFormFields()
{
    return '<form method="post" enctype="multipart/form-data">
                <div class="file-field input-field col m6">
                    <div class="btn light-green darken-1">
                        <span>File</span>
                        <input type="file" name="file" accept=".csv" required>
                    </div>
                    <div class="file-path-wrapper">
                        <input class="file-path validate" 
                               placeholder="Upload file csv referensi kode klasifikasi" 
                               type="text">
                     </div>
                </div>
                <button type="submit" class="btn-large blue waves-effect waves-light" 
                        name="submit">
                    IMPORT <i class="material-icons">file_upload</i>
                </button>
            </form>';
}
?>
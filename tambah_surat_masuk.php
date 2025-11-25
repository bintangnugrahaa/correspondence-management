<?php
// Check session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

if (isset($_REQUEST['submit'])) {
    handleFormSubmission();
} else {
    displayForm();
}

/**
 * Handle form submission
 */
function handleFormSubmission()
{
    // Validate required fields
    if (!validateRequiredFields()) {
        echo '<script language="javascript">window.history.back();</script>';
        return;
    }

    // Sanitize and validate input data
    $data = sanitizeAndValidateInput();
    if (!$data) {
        echo '<script language="javascript">window.history.back();</script>';
        return;
    }

    // Check for duplicate entry
    if (checkDuplicateSurat($data['no_surat'])) {
        $_SESSION['errDup'] = 'Nomor Surat sudah terpakai, gunakan yang lain!';
        echo '<script language="javascript">window.history.back();</script>';
        return;
    }

    // Handle file upload and database insertion
    handleFileUploadAndInsert($data);
}

/**
 * Validate required fields
 */
function validateRequiredFields()
{
    $requiredFields = [
        'no_agenda',
        'no_surat',
        'asal_surat',
        'isi',
        'kode',
        'indeks',
        'tgl_surat',
        'keterangan'
    ];

    foreach ($requiredFields as $field) {
        if (empty($_REQUEST[$field])) {
            $_SESSION['errEmpty'] = 'ERROR! Semua form wajib diisi';
            return false;
        }
    }
    return true;
}

/**
 * Sanitize and validate input data
 */
function sanitizeAndValidateInput()
{
    $validationRules = [
        'no_agenda' => [
            'pattern' => "/^[0-9]*$/",
            'error' => 'Form Nomor Agenda harus diisi angka!'
        ],
        'no_surat' => [
            'pattern' => "/^[a-zA-Z0-9.\/ -]*$/",
            'error' => 'Form No Surat hanya boleh mengandung karakter huruf, angka, spasi, titik(.), minus(-) dan garis miring(/)'
        ],
        'asal_surat' => [
            'pattern' => "/^[a-zA-Z0-9.,() \/ -]*$/",
            'error' => 'Form Asal Surat hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-),kurung() dan garis miring(/)'
        ],
        'isi' => [
            'pattern' => "/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/",
            'error' => 'Form Isi Ringkas hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), kurung(), underscore(_), dan(&) persen(%) dan at(@)'
        ],
        'kode' => [
            'pattern' => "/^[a-zA-Z0-9., ]*$/",
            'error' => 'Form Kode Klasifikasi hanya boleh mengandung karakter huruf, angka, spasi, titik(.) dan koma(,)'
        ],
        'indeks' => [
            'pattern' => "/^[a-zA-Z0-9., -]*$/",
            'error' => 'Form Indeks hanya boleh mengandung karakter huruf, angka, spasi, titik(.) dan koma(,) dan minus (-)'
        ],
        'tgl_surat' => [
            'pattern' => "/^[0-9.-]*$/",
            'error' => 'Form Tanggal Surat hanya boleh mengandung angka dan minus(-)'
        ],
        'keterangan' => [
            'pattern' => "/^[a-zA-Z0-9.,()\/ -]*$/",
            'error' => 'Form Keterangan hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), dan kurung()'
        ]
    ];

    $data = [];

    foreach ($validationRules as $field => $rule) {
        $value = $_REQUEST[$field];

        // Special handling for kode field
        if ($field === 'kode') {
            $value = substr($value, 0, 30);
            $value = trim($value);
        }

        if (!preg_match($rule['pattern'], $value)) {
            $_SESSION[$field] = $rule['error'];
            return false;
        }

        $data[$field] = $value;
    }

    $data['id_user'] = $_SESSION['id_user'];
    return $data;
}

/**
 * Check for duplicate surat
 */
function checkDuplicateSurat($no_surat)
{
    global $config;

    $no_surat = mysqli_real_escape_string($config, $no_surat);
    $cek = mysqli_query($config, "SELECT * FROM tbl_surat_masuk WHERE no_surat='$no_surat'");
    return mysqli_num_rows($cek) > 0;
}

/**
 * Handle file upload and database insertion
 */
function handleFileUploadAndInsert($data)
{
    global $config;

    $allowedExtensions = ['jpg', 'png', 'jpeg', 'doc', 'docx', 'pdf'];
    $maxFileSize = 2500000; // 2.5MB
    $targetDir = "upload/surat_masuk/";

    // Create directory if it doesn't exist
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileName = '';
    $fileError = '';

    // Handle file upload if file is provided
    if (!empty($_FILES['file']['name'])) {
        $uploadResult = handleFileUpload($allowedExtensions, $maxFileSize, $targetDir);

        if ($uploadResult['success']) {
            $fileName = $uploadResult['fileName'];
        } else {
            $fileError = $uploadResult['error'];
        }
    }

    // If there was a file error, redirect back
    if (!empty($fileError)) {
        echo '<script language="javascript">window.history.back();</script>';
        return;
    }

    // Insert into database
    if (insertSuratMasuk($data, $fileName)) {
        $_SESSION['succAdd'] = 'SUKSES! Data berhasil ditambahkan';
        header("Location: ./admin.php?page=tsm");
        exit();
    } else {
        $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
        echo '<script language="javascript">window.history.back();</script>';
    }
}

/**
 * Handle file upload
 */
function handleFileUpload($allowedExtensions, $maxFileSize, $targetDir)
{
    $file = $_FILES['file']['name'];
    $tmpName = $_FILES['file']['tmp_name'];
    $size = $_FILES['file']['size'];

    $fileParts = explode('.', $file);
    $extension = strtolower(end($fileParts));

    // Validate file extension
    if (!in_array($extension, $allowedExtensions)) {
        $_SESSION['errFormat'] = 'Format file yang diperbolehkan hanya *.JPG, *.PNG, *.DOC, *.DOCX atau *.PDF!';
        return ['success' => false, 'error' => 'invalid_format'];
    }

    // Validate file size
    if ($size > $maxFileSize) {
        $_SESSION['errSize'] = 'Ukuran file yang diupload terlalu besar!';
        return ['success' => false, 'error' => 'invalid_size'];
    }

    // Generate unique filename and move file
    $random = rand(1, 10000);
    $newFileName = $random . "-" . $file;

    if (move_uploaded_file($tmpName, $targetDir . $newFileName)) {
        return ['success' => true, 'fileName' => $newFileName];
    } else {
        $_SESSION['errQ'] = 'ERROR! Gagal mengupload file';
        return ['success' => false, 'error' => 'upload_failed'];
    }
}

/**
 * Insert data into surat_masuk table
 */
function insertSuratMasuk($data, $fileName = '')
{
    global $config;

    $escapedData = [];
    foreach ($data as $key => $value) {
        $escapedData[$key] = mysqli_real_escape_string($config, $value);
    }

    $fileValue = $fileName ? "'" . mysqli_real_escape_string($config, $fileName) . "'" : "''";

    $query = "INSERT INTO tbl_surat_masuk(
        no_agenda, no_surat, asal_surat, isi, kode, indeks, tgl_surat,
        tgl_diterima, file, keterangan, id_user
    ) VALUES(
        '{$escapedData['no_agenda']}', 
        '{$escapedData['no_surat']}', 
        '{$escapedData['asal_surat']}', 
        '{$escapedData['isi']}', 
        '{$escapedData['kode']}', 
        '{$escapedData['indeks']}', 
        '{$escapedData['tgl_surat']}',
        NOW(), 
        $fileValue, 
        '{$escapedData['keterangan']}', 
        '{$escapedData['id_user']}'
    )";

    return mysqli_query($config, $query);
}

/**
 * Display the form
 */
function displayForm()
{
    add_displayNavigationHeader();
    displayErrorMessages();
    displaySuratForm();
}

/**
 * Display navigation header
 */
function add_displayNavigationHeader()
{
    ?>
    <!-- Row Start -->
    <div class="row">
        <!-- Secondary Nav START -->
        <div class="col s12">
            <nav class="secondary-nav">
                <div class="nav-wrapper blue-grey darken-1">
                    <ul class="left">
                        <li class="waves-effect waves-light">
                            <a href="?page=tsm&act=add" class="judul">
                                <i class="material-icons">mail</i> Tambah Data Surat Masuk
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
        <!-- Secondary Nav END -->
    </div>
    <!-- Row END -->
    <?php
}

/**
 * Display error messages
 */
function displayErrorMessages()
{
    $errorTypes = [
        'errQ' => 'Query Error',
        'errEmpty' => 'Empty Fields',
        'errSize' => 'File Size Error',
        'errFormat' => 'File Format Error'
    ];

    foreach ($errorTypes as $sessionKey => $type) {
        if (isset($_SESSION[$sessionKey])) {
            add_displayAlertMessage($_SESSION[$sessionKey], 'red');
            unset($_SESSION[$sessionKey]);
        }
    }
}

/**
 * Display alert message
 */
function add_displayAlertMessage($message, $color = 'red')
{
    echo '
    <div id="alert-message" class="row">
        <div class="col m12">
            <div class="card ' . $color . ' lighten-5">
                <div class="card-content notif">
                    <span class="card-title ' . $color . '-text">
                        <i class="material-icons md-36">clear</i> ' . htmlspecialchars($message) . '
                    </span>
                </div>
            </div>
        </div>
    </div>';
}

/**
 * Display surat form
 */
function displaySuratForm()
{
    ?>
    <!-- Row form Start -->
    <div class="row jarak-form">
        <!-- Form START -->
        <form class="col s12" method="POST" action="?page=tsm&act=add" enctype="multipart/form-data">
            <?php displayFormFields(); ?>

            <div class="row">
                <div class="col 6">
                    <button type="submit" name="submit" class="btn-large blue waves-effect waves-light">
                        SIMPAN <i class="material-icons">done</i>
                    </button>
                </div>
                <div class="col 6">
                    <a href="?page=tsm" class="btn-large deep-orange waves-effect waves-light">
                        BATAL <i class="material-icons">clear</i>
                    </a>
                </div>
            </div>
        </form>
        <!-- Form END -->
    </div>
    <!-- Row form END -->
    <?php
}

/**
 * Display form fields
 */
function displayFormFields()
{
    ?>
    <!-- Row in form START -->
    <div class="row">
        <?php
        displayNumberAgendaField();
        displayKodeField();
        displayAsalSuratField();
        displayIndeksField();
        displayNoSuratField();
        displayTanggalSuratField();
        displayIsiField();
        displayKeteranganField();
        displayFileField();
        ?>
    </div>
    <!-- Row in form END -->
    <?php
}

/**
 * Display individual form fields with error handling
 */
function displayFormField($type, $id, $label, $icon, $extraHtml = '')
{
    $fieldErrors = [
        'no_agenda',
        'kode',
        'asal_surat',
        'indeks',
        'no_surat',
        'tgl_surat',
        'isi',
        'keterangan'
    ];

    echo '<div class="input-field col s6">';
    echo '<i class="material-icons prefix md-prefix">' . $icon . '</i>';

    if ($type === 'textarea') {
        echo '<textarea id="' . $id . '" class="materialize-textarea validate" name="' . $id . '" required></textarea>';
    } else {
        $value = '';
        if ($id === 'no_agenda') {
            $value = ' value="' . generateNextNoAgenda() . '"';
        }
        echo '<input id="' . $id . '" type="' . $type . '" class="validate" name="' . $id . '"' . $value . ' required>';
    }

    echo $extraHtml;

    // Display field-specific errors
    if (in_array($id, $fieldErrors) && isset($_SESSION[$id])) {
        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' .
            htmlspecialchars($_SESSION[$id]) . '</div>';
        unset($_SESSION[$id]);
    }

    // Display duplicate error for no_surat field
    if ($id === 'no_surat' && isset($_SESSION['errDup'])) {
        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' .
            htmlspecialchars($_SESSION['errDup']) . '</div>';
        unset($_SESSION['errDup']);
    }

    echo '<label for="' . $id . '">' . $label . '</label>';
    echo '</div>';
}

/**
 * Generate next no_agenda number
 */
function generateNextNoAgenda()
{
    global $config;

    $sql = mysqli_query($config, "SELECT no_agenda FROM tbl_surat_masuk ORDER BY no_agenda DESC LIMIT 1");

    if (mysqli_num_rows($sql) == 0) {
        return '1';
    }

    $row = mysqli_fetch_array($sql);
    return intval($row[0]) + 1;
}

// Individual field display functions
function displayNumberAgendaField()
{
    displayFormField('number', 'no_agenda', '', 'looks_one');
}

function displayKodeField()
{
    displayFormField('text', 'kode', 'Kode Klasifikasi', 'bookmark');
}

function displayAsalSuratField()
{
    displayFormField('text', 'asal_surat', 'Asal Surat', 'place');
}

function displayIndeksField()
{
    displayFormField('text', 'indeks', 'Indeks Berkas', 'storage');
}

function displayNoSuratField()
{
    displayFormField('text', 'no_surat', 'Nomor Surat', 'looks_two');
}

function displayTanggalSuratField()
{
    displayFormField('text', 'tgl_surat', 'Tanggal Surat', 'date_range');
}

function displayIsiField()
{
    displayFormField('textarea', 'isi', 'Isi Ringkas', 'description');
}

function displayKeteranganField()
{
    displayFormField('text', 'keterangan', 'Keterangan', 'featured_play_list');
}

function displayFileField()
{
    ?>
    <div class="input-field col s6">
        <div class="file-field input-field">
            <div class="btn light-green darken-1">
                <span>File</span>
                <input type="file" id="file" name="file">
            </div>
            <div class="file-path-wrapper">
                <input class="file-path validate" type="text" placeholder="Upload file/scan gambar surat masuk">
                <?php
                if (isset($_SESSION['errSize'])) {
                    echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' .
                        htmlspecialchars($_SESSION['errSize']) . '</div>';
                    unset($_SESSION['errSize']);
                }
                if (isset($_SESSION['errFormat'])) {
                    echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' .
                        htmlspecialchars($_SESSION['errFormat']) . '</div>';
                    unset($_SESSION['errFormat']);
                }
                ?>
                <small class="red-text">*Format file yang diperbolehkan *.JPG, *.PNG, *.DOC, *.DOCX, *.PDF dan ukuran
                    maksimal file 2 MB!</small>
            </div>
        </div>
    </div>
    <?php
}
?>
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
    displayEditForm();
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

    // Check user permissions
    if (!checkUserPermissions($data['id_surat'])) {
        redirectWithError('ERROR! Anda tidak memiliki hak akses untuk mengedit data ini', 'tsk');
        return;
    }

    // Handle file upload and database update
    handleFileUploadAndUpdate($data);
}

/**
 * Validate required fields
 */
function validateRequiredFields()
{
    $requiredFields = [
        'no_agenda',
        'no_surat',
        'tujuan',
        'isi',
        'kode',
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
            'error' => 'Form Nomor Agenda harus diisi angka!',
            'session_key' => 'no_agendak'
        ],
        'no_surat' => [
            'pattern' => "/^[a-zA-Z0-9.\/ -]*$/",
            'error' => 'Form No Surat hanya boleh mengandung karakter huruf, angka, spasi, titik(.), minus(-) dan garis miring(/)',
            'session_key' => 'no_suratk'
        ],
        'tujuan' => [
            'pattern' => "/^[a-zA-Z0-9.,() \/ -]*$/",
            'error' => 'Form Tujuan Surat hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), kurung() dan garis miring(/)',
            'session_key' => 'tujuan_surat'
        ],
        'isi' => [
            'pattern' => "/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/",
            'error' => 'Form Isi Ringkas hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), kurung(), underscore(_), dan(&) persen(%) dan at(@)',
            'session_key' => 'isik'
        ],
        'kode' => [
            'pattern' => "/^[a-zA-Z0-9., ]*$/",
            'error' => 'Form Kode Klasifikasi hanya boleh mengandung karakter huruf, angka, spasi, titik(.) dan koma(,)',
            'session_key' => 'kodek'
        ],
        'tgl_surat' => [
            'pattern' => "/^[0-9.-]*$/",
            'error' => 'Form Tanggal Surat hanya boleh mengandung angka dan minus(-)',
            'session_key' => 'tgl_suratk'
        ],
        'keterangan' => [
            'pattern' => "/^[a-zA-Z0-9.,()\/ -]*$/",
            'error' => 'Form Keterangan hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), dan kurung()',
            'session_key' => 'keterangank'
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
            $_SESSION[$rule['session_key']] = $rule['error'];
            return false;
        }

        $data[$field] = $value;
    }

    $data['id_surat'] = $_REQUEST['id_surat'];
    $data['id_user'] = $_SESSION['id_user'];
    return $data;
}

/**
 * Check user permissions for editing
 */
function checkUserPermissions($id_surat)
{
    global $config;

    $id_surat = mysqli_real_escape_string($config, $id_surat);
    $query = mysqli_query($config, "SELECT id_user FROM tbl_surat_keluar WHERE id_surat='$id_surat'");

    if ($query && $row = mysqli_fetch_array($query)) {
        $id_user = $row[0];
        // Allow if user is admin (id_user = 1) or owns the record
        return ($_SESSION['id_user'] == 1 || $_SESSION['id_user'] == $id_user);
    }

    return false;
}

/**
 * Redirect with error message
 */
function redirectWithError($message, $page)
{
    echo '<script language="javascript">
            window.alert("' . addslashes($message) . '");
            window.location.href="./admin.php?page=' . $page . '";
          </script>';
}

/**
 * Handle file upload and database update
 */
function handleFileUploadAndUpdate($data)
{
    global $config;

    $allowedExtensions = ['jpg', 'png', 'jpeg', 'doc', 'docx', 'pdf'];
    $maxFileSize = 2500000; // 2.5MB
    $targetDir = "upload/surat_keluar/";

    // Create directory if it doesn't exist
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileName = null;
    $fileError = '';

    // Handle file upload if file is provided
    if (!empty($_FILES['file']['name'])) {
        $uploadResult = handleFileUpload($allowedExtensions, $maxFileSize, $targetDir);

        if ($uploadResult['success']) {
            $fileName = $uploadResult['fileName'];
            // Delete old file if exists
            deleteOldFile($data['id_surat'], $targetDir);
        } else {
            $fileError = $uploadResult['error'];
        }
    }

    // If there was a file error, redirect back
    if (!empty($fileError)) {
        echo '<script language="javascript">window.history.back();</script>';
        return;
    }

    // Update database
    if (updateSuratKeluar($data, $fileName)) {
        $_SESSION['succEdit'] = 'SUKSES! Data berhasil diupdate';
        header("Location: ./admin.php?page=tsk");
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
 * Delete old file
 */
function deleteOldFile($id_surat, $targetDir)
{
    global $config;

    $id_surat = mysqli_real_escape_string($config, $id_surat);
    $query = mysqli_query($config, "SELECT file FROM tbl_surat_keluar WHERE id_surat='$id_surat'");

    if ($query && $row = mysqli_fetch_array($query)) {
        $oldFile = $row[0];
        if (!empty($oldFile) && file_exists($targetDir . $oldFile)) {
            unlink($targetDir . $oldFile);
        }
    }
}

/**
 * Update surat_keluar table
 */
function updateSuratKeluar($data, $fileName = null)
{
    global $config;

    $escapedData = [];
    foreach ($data as $key => $value) {
        $escapedData[$key] = mysqli_real_escape_string($config, $value);
    }

    // Build update query
    $updateFields = [
        "no_agenda = '{$escapedData['no_agenda']}'",
        "tujuan = '{$escapedData['tujuan']}'",
        "no_surat = '{$escapedData['no_surat']}'",
        "isi = '{$escapedData['isi']}'",
        "kode = '{$escapedData['kode']}'",
        "tgl_surat = '{$escapedData['tgl_surat']}'",
        "keterangan = '{$escapedData['keterangan']}'",
        "id_user = '{$escapedData['id_user']}'"
    ];

    // Add file field if new file is uploaded
    if ($fileName !== null) {
        $escapedFileName = mysqli_real_escape_string($config, $fileName);
        $updateFields[] = "file = '$escapedFileName'";
    }

    $query = "UPDATE tbl_surat_keluar SET " . implode(', ', $updateFields) .
        " WHERE id_surat = '{$escapedData['id_surat']}'";

    return mysqli_query($config, $query);
}

/**
 * Display the edit form
 */
function displayEditForm()
{
    global $config;

    $id_surat = mysqli_real_escape_string($config, $_REQUEST['id_surat']);
    $suratData = getSuratData($id_surat);

    if (!$suratData) {
        echo '<script language="javascript">
                window.alert("ERROR! Data tidak ditemukan");
                window.location.href="./admin.php?page=tsk";
              </script>';
        return;
    }

    // Check user permissions
    if ($_SESSION['id_user'] != $suratData['id_user'] && $_SESSION['id_user'] != 1) {
        echo '<script language="javascript">
                window.alert("ERROR! Anda tidak memiliki hak akses untuk mengedit data ini");
                window.location.href="./admin.php?page=tsk";
              </script>';
        return;
    }

    edit_displayNavigationHeader();
    displayErrorMessages();
    displayEditSuratForm($suratData);
}

/**
 * Get surat data from database
 */
function getSuratData($id_surat)
{
    global $config;

    $query = mysqli_query(
        $config,
        "SELECT id_surat, no_agenda, tujuan, no_surat, isi, kode, 
                tgl_surat, file, keterangan, id_user 
         FROM tbl_surat_keluar 
         WHERE id_surat='$id_surat'"
    );

    if ($query && $data = mysqli_fetch_assoc($query)) {
        return $data;
    }

    return false;
}

/**
 * Display navigation header
 */
function edit_displayNavigationHeader()
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
                                <a href="#" class="judul">
                                    <i class="material-icons">edit</i> Edit Data Surat Keluar
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
            edit_displayAlertMessage($_SESSION[$sessionKey], 'red');
            unset($_SESSION[$sessionKey]);
        }
    }
}

/**
 * Display alert message
 */
function edit_displayAlertMessage($message, $color = 'red')
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
 * Display edit surat form
 */
function displayEditSuratForm($suratData)
{
    ?>
        <!-- Row form Start -->
        <div class="row jarak-form">
            <!-- Form START -->
            <form class="col s12" method="POST" action="?page=tsk&act=edit" enctype="multipart/form-data">
                <input type="hidden" name="id_surat" value="<?php echo htmlspecialchars($suratData['id_surat']); ?>">
                <?php displayFormFields($suratData); ?>
            
                <div class="row">
                    <div class="col 6">
                        <button type="submit" name="submit" class="btn-large blue waves-effect waves-light">
                            SIMPAN <i class="material-icons">done</i>
                        </button>
                    </div>
                    <div class="col 6">
                        <a href="?page=tsk" class="btn-large deep-orange waves-effect waves-light">
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
function displayFormFields($suratData)
{
    ?>
        <!-- Row in form START -->
        <div class="row">
            <?php
            displayNumberAgendaField($suratData['no_agenda']);
            displayKodeField($suratData['kode']);
            displayTujuanField($suratData['tujuan']);
            displayNoSuratField($suratData['no_surat']);
            displayTanggalSuratField($suratData['tgl_surat']);
            displayKeteranganField($suratData['keterangan']);
            displayIsiField($suratData['isi']);
            displayFileField($suratData['file']);
            ?>
        </div>
        <!-- Row in form END -->
        <?php
}

/**
 * Display individual form fields with error handling
 */
function displayFormField($type, $id, $label, $icon, $value, $sessionKey = null)
{
    $sessionKey = $sessionKey ?: $id;

    echo '<div class="input-field col s6">';
    echo '<i class="material-icons prefix md-prefix">' . $icon . '</i>';

    if ($type === 'textarea') {
        echo '<textarea id="' . $id . '" class="materialize-textarea validate" name="' . $id . '" required>' .
            htmlspecialchars($value) . '</textarea>';
    } else {
        echo '<input id="' . $id . '" type="' . $type . '" class="validate" name="' . $id . '" value="' .
            htmlspecialchars($value) . '" required>';
    }

    // Display field-specific errors
    if (isset($_SESSION[$sessionKey])) {
        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' .
            htmlspecialchars($_SESSION[$sessionKey]) . '</div>';
        unset($_SESSION[$sessionKey]);
    }

    echo '<label for="' . $id . '">' . $label . '</label>';
    echo '</div>';
}

// Individual field display functions
function displayNumberAgendaField($value)
{
    displayFormField('number', 'no_agenda', '', 'looks_one', $value, 'no_agendak');
}

function displayKodeField($value)
{
    displayFormField('text', 'kode', '', 'bookmark', $value, 'kodek');
}

function displayTujuanField($value)
{
    displayFormField('text', 'tujuan', '', 'place', $value, 'tujuan_surat');
}

function displayNoSuratField($value)
{
    displayFormField('text', 'no_surat', '', 'looks_two', $value, 'no_suratk');
}

function displayTanggalSuratField($value)
{
    echo '<div class="input-field col s6">';
    echo '<i class="material-icons prefix md-prefix">date_range</i>';
    echo '<input id="tgl_surat" type="text" name="tgl_surat" class="datepicker" value="' .
        htmlspecialchars($value) . '" required>';

    if (isset($_SESSION['tgl_suratk'])) {
        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' .
            htmlspecialchars($_SESSION['tgl_suratk']) . '</div>';
        unset($_SESSION['tgl_suratk']);
    }

    echo '<label for="tgl_surat"></label>';
    echo '</div>';
}

function displayKeteranganField($value)
{
    displayFormField('text', 'keterangan', '', 'featured_play_list', $value, 'keterangank');
}

function displayIsiField($value)
{
    displayFormField('textarea', 'isi', '', 'description', $value, 'isik');
}

function displayFileField($currentFile)
{
    ?>
        <div class="input-field col s6">
            <div class="file-field input-field">
                <div class="btn light-green darken-1">
                    <span>File</span>
                    <input type="file" id="file" name="file">
                </div>
                <div class="file-path-wrapper">
                    <input class="file-path validate" type="text" value="<?php echo htmlspecialchars($currentFile); ?>" 
                           placeholder="Upload file/scan gambar surat keluar">
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
                    <small class="red-text">*Format file yang diperbolehkan *.JPG, *.PNG, *.DOC, *.DOCX, *.PDF dan ukuran maksimal file 2 MB!</small>
                </div>
            </div>
        </div>
        <?php
}
?>
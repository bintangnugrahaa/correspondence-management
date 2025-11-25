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

// Handle sub-pages
if (isset($_REQUEST['sub'])) {
    handleSubPages($_REQUEST['sub']);
    exit();
}

// Handle form submission
if (isset($_REQUEST['submit'])) {
    handleFormSubmission();
} else {
    displaySettingsForm();
}

/**
 * Handle sub-pages routing
 */
function handleSubPages($sub)
{
    $subPages = [
        'back' => 'backup.php',
        'rest' => 'restore.php',
        'usr' => 'user.php'
    ];

    if (isset($subPages[$sub])) {
        include $subPages[$sub];
    }
}

/**
 * Handle form submission for institution settings
 */
function handleFormSubmission()
{
    global $config;

    // Validate required fields
    if (!validateRequiredFields()) {
        $_SESSION['errEmpty'] = 'ERROR! Semua form wajib diisi';
        header("Location: ./admin.php?page=sett");
        exit();
    }

    // Validate all input fields
    $validationResult = validateAllFields();
    if (!$validationResult['success']) {
        foreach ($validationResult['errors'] as $sessionKey => $errorMessage) {
            $_SESSION[$sessionKey] = $errorMessage;
        }
        echo '<script language="javascript">window.history.back();</script>';
        exit();
    }

    // Process the form data
    if (processInstitutionUpdate()) {
        $_SESSION['succEdit'] = 'SUKSES! Data instansi berhasil diupdate';
        header("Location: ./admin.php?page=sett");
        exit();
    } else {
        $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
        echo '<script language="javascript">window.history.back();</script>';
        exit();
    }
}

/**
 * Validate required fields
 */
function validateRequiredFields()
{
    $requiredFields = [
        'institusi',
        'nama',
        'status',
        'alamat',
        'kepsek',
        'nip',
        'website',
        'email'
    ];

    foreach ($requiredFields as $field) {
        if (empty($_REQUEST[$field])) {
            return false;
        }
    }
    return true;
}

/**
 * Validate all form fields
 */
function validateAllFields()
{
    $validationRules = [
        'nama' => [
            'pattern' => "/^[a-zA-Z0-9. -]*$/",
            'error' => 'Form Nama Instansi hanya boleh mengandung karakter huruf, angka, spasi, titik(.) dan minus(-)'
        ],
        'institusi' => [
            'pattern' => "/^[a-zA-Z0-9. -]*$/",
            'error' => 'Form Nama Yayasan hanya boleh mengandung karakter huruf, angka, spasi, titik(.) dan minus(-)'
        ],
        'status' => [
            'pattern' => "/^[a-zA-Z0-9.,:\/<> -\"]*$/",
            'error' => 'Form Status hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), titik dua(:), petik dua(""), garis miring(/) dan minus(-)'
        ],
        'alamat' => [
            'pattern' => "/^[a-zA-Z0-9.,()\/ -]*$/",
            'error' => 'Form Alamat hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), dan kurung()'
        ],
        'kepsek' => [
            'pattern' => "/^[a-zA-Z., ]*$/",
            'error' => 'Form Nama Kepala Sekolah hanya boleh mengandung karakter huruf, spasi, titik(.) dan koma(,)'
        ],
        'nip' => [
            'pattern' => "/^[0-9 -]*$/",
            'error' => 'Form NIP Kepala Sekolah hanya boleh mengandung karakter angka, spasi, dan minus(-)'
        ]
    ];

    $errors = [];

    foreach ($validationRules as $field => $rule) {
        if (!preg_match($rule['pattern'], $_REQUEST[$field])) {
            $errors[$field] = $rule['error'];
        }
    }

    // Validate website URL
    if (!filter_var($_REQUEST['website'], FILTER_VALIDATE_URL)) {
        $errors['website'] = 'Format URL Website tidak valid';
    }

    // Validate email
    if (!filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format Email tidak valid';
    }

    return [
        'success' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Process institution data update
 */
function processInstitutionUpdate()
{
    global $config;

    $id_instansi = "1";
    $id_user = $_SESSION['id_user'];

    // Sanitize input data
    $data = [
        'institusi' => mysqli_real_escape_string($config, $_REQUEST['institusi']),
        'nama' => mysqli_real_escape_string($config, $_REQUEST['nama']),
        'status' => mysqli_real_escape_string($config, $_REQUEST['status']),
        'alamat' => mysqli_real_escape_string($config, $_REQUEST['alamat']),
        'kepsek' => mysqli_real_escape_string($config, $_REQUEST['kepsek']),
        'nip' => mysqli_real_escape_string($config, $_REQUEST['nip']),
        'website' => mysqli_real_escape_string($config, $_REQUEST['website']),
        'email' => mysqli_real_escape_string($config, $_REQUEST['email'])
    ];

    // Handle logo upload if provided
    $logoResult = handleLogoUpload();
    if (!$logoResult['success'] && $logoResult['error']) {
        $_SESSION['errSize'] = $logoResult['error'];
        echo '<script language="javascript">window.history.back();</script>';
        exit();
    }

    // Build update query
    if ($logoResult['success'] && $logoResult['logo_name']) {
        $data['logo'] = $logoResult['logo_name'];
        $query = "UPDATE tbl_instansi SET 
                  institusi='{$data['institusi']}', nama='{$data['nama']}', status='{$data['status']}', 
                  alamat='{$data['alamat']}', kepsek='{$data['kepsek']}', nip='{$data['nip']}', 
                  website='{$data['website']}', email='{$data['email']}', logo='{$data['logo']}', 
                  id_user='$id_user' WHERE id_instansi='$id_instansi'";
    } else {
        $query = "UPDATE tbl_instansi SET 
                  institusi='{$data['institusi']}', nama='{$data['nama']}', status='{$data['status']}', 
                  alamat='{$data['alamat']}', kepsek='{$data['kepsek']}', nip='{$data['nip']}', 
                  website='{$data['website']}', email='{$data['email']}', id_user='$id_user' 
                  WHERE id_instansi='$id_instansi'";
    }

    return mysqli_query($config, $query);
}

/**
 * Handle logo file upload
 */
function handleLogoUpload()
{
    global $config;

    if (empty($_FILES['logo']['name'])) {
        return ['success' => true, 'logo_name' => null];
    }

    $target_dir = "upload/";
    $logo = $_FILES['logo']['name'];
    $fileExtension = strtolower(pathinfo($logo, PATHINFO_EXTENSION));
    $allowedExtensions = ['png', 'jpg'];
    $maxFileSize = 2000000; // 2MB

    // Create directory if it doesn't exist
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // Validate file extension
    if (!in_array($fileExtension, $allowedExtensions)) {
        return [
            'success' => false,
            'error' => 'Format file gambar yang diperbolehkan hanya *.JPG dan *.PNG!',
            'logo_name' => null
        ];
    }

    // Validate file size
    if ($_FILES['logo']['size'] > $maxFileSize) {
        return [
            'success' => false,
            'error' => 'Ukuran file yang diupload terlalu besar!',
            'logo_name' => null
        ];
    }

    // Delete old logo
    $oldLogoQuery = mysqli_query($config, "SELECT logo FROM tbl_instansi");
    if ($oldLogoQuery && mysqli_num_rows($oldLogoQuery) > 0) {
        list($oldLogo) = mysqli_fetch_array($oldLogoQuery);
        if (!empty($oldLogo) && file_exists($target_dir . $oldLogo)) {
            unlink($target_dir . $oldLogo);
        }
    }

    // Upload new logo
    if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_dir . $logo)) {
        return ['success' => true, 'logo_name' => $logo];
    } else {
        return [
            'success' => false,
            'error' => 'Gagal mengupload file logo!',
            'logo_name' => null
        ];
    }
}

/**
 * Display settings form
 */
function displaySettingsForm()
{
    global $config;

    $query = mysqli_query($config, "SELECT * FROM tbl_instansi LIMIT 1");
    if (mysqli_num_rows($query) > 0) {
        $institution = mysqli_fetch_assoc($query);
        renderSettingsPage($institution);
    }
}

/**
 * Render the settings page
 */
function renderSettingsPage($institution)
{
    echo renderHeader();
    displayAlertMessages();
    echo renderSettingsForm($institution);
}

/**
 * Render page header
 */
function renderHeader()
{
    return '<!-- Row Start -->
            <div class="row">
                <!-- Secondary Nav START -->
                <div class="col s12">
                    <nav class="secondary-nav">
                        <div class="nav-wrapper blue-grey darken-1">
                            <ul class="left">
                                <li class="waves-effect waves-light">
                                    <a href="?page=sett" class="judul">
                                        <i class="material-icons">work</i> Manajemen Instansi
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </div>
                <!-- Secondary Nav END -->
            </div>
            <!-- Row END -->';
}

/**
 * Display alert messages
 */
function displayAlertMessages()
{
    $alertTypes = [
        'errEmpty' => 'red',
        'succEdit' => 'green',
        'errQ' => 'red',
        'errSize' => 'red',
        'errFormat' => 'red'
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
 * Render settings form
 */
function renderSettingsForm($institution)
{
    return '<!-- Row form Start -->
            <div class="row jarak-form">
                <!-- Form START -->
                <form class="col s12" method="post" action="?page=sett" enctype="multipart/form-data">
                    ' . renderFormFields($institution) . '
                    ' . renderFormButtons() . '
                </form>
                <!-- Form END -->
            </div>
            <!-- Row form END -->';
}

/**
 * Render form fields
 */
function renderFormFields($institution)
{
    $fields = [
        [
            'name' => 'nama',
            'label' => 'Nama Instansi',
            'icon' => 'school',
            'type' => 'text',
            'value' => htmlspecialchars($institution['nama']),
            'sessionKey' => 'namains'
        ],
        [
            'name' => 'institusi',
            'label' => 'Nama Yayasan',
            'icon' => 'work',
            'type' => 'text',
            'value' => htmlspecialchars($institution['institusi']),
            'sessionKey' => 'institusi'
        ],
        [
            'name' => 'status',
            'label' => 'Status',
            'icon' => 'assistant_photo',
            'type' => 'text',
            'value' => htmlspecialchars($institution['status']),
            'sessionKey' => 'status'
        ],
        [
            'name' => 'kepsek',
            'label' => 'Nama Kepala Sekolah',
            'icon' => 'account_box',
            'type' => 'text',
            'value' => htmlspecialchars($institution['kepsek']),
            'sessionKey' => 'kepsek'
        ],
        [
            'name' => 'alamat',
            'label' => 'Alamat',
            'icon' => 'place',
            'type' => 'text',
            'value' => htmlspecialchars($institution['alamat']),
            'sessionKey' => 'alamat'
        ],
        [
            'name' => 'nip',
            'label' => 'NIP Kepala Sekolah',
            'icon' => 'looks_one',
            'type' => 'text',
            'value' => htmlspecialchars($institution['nip']),
            'sessionKey' => 'nipkepsek'
        ],
        [
            'name' => 'website',
            'label' => 'Website',
            'icon' => 'language',
            'type' => 'url',
            'value' => htmlspecialchars($institution['website']),
            'sessionKey' => 'website'
        ],
        [
            'name' => 'email',
            'label' => 'Email Instansi',
            'icon' => 'mail',
            'type' => 'email',
            'value' => htmlspecialchars($institution['email']),
            'sessionKey' => 'email'
        ]
    ];

    $html = '<!-- Row in form START -->
            <div class="row">
                <input type="hidden" value="1" name="id_instansi">';

    foreach ($fields as $field) {
        $html .= renderInputField($field);
    }

    $html .= renderLogoField($institution);
    $html .= '</div><!-- Row in form END -->';

    return $html;
}

/**
 * Render input field
 */
function renderInputField($field)
{
    $html = '<div class="input-field col s6">
                <i class="material-icons prefix md-prefix">' . $field['icon'] . '</i>
                <input id="' . $field['name'] . '" type="' . $field['type'] . '" 
                       class="validate" name="' . $field['name'] . '" 
                       value="' . $field['value'] . '" required>';

    if (isset($_SESSION[$field['sessionKey']])) {
        $html .= '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'
            . htmlspecialchars($_SESSION[$field['sessionKey']]) . '</div>';
        unset($_SESSION[$field['sessionKey']]);
    }

    $html .= '<label for="' . $field['name'] . '">' . $field['label'] . '</label>
            </div>';

    return $html;
}

/**
 * Render logo field
 */
function renderLogoField($institution)
{
    $html = '<div class="input-field col s6 tooltipped" data-position="top" 
                 data-tooltip="Jika tidak ada logo, biarkan kosong">
                <div class="file-field input-field">
                    <div class="btn light-green darken-1">
                        <span>File</span>
                        <input type="file" id="logo" name="logo">
                    </div>
                    <div class="file-path-wrapper">
                        <input class="file-path validate" type="text" placeholder="Upload Logo instansi">
                    </div>';

    if (isset($_SESSION['errSize'])) {
        $html .= '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'
            . htmlspecialchars($_SESSION['errSize']) . '</div>';
        unset($_SESSION['errSize']);
    }

    $html .= '<small class="red-text">*Format file yang diperbolehkan hanya *.JPG, *.PNG dan ukuran maksimal file
                2 MB. Disarankan gambar berbentuk kotak atau lingkaran!</small>
                </div>
            </div>
            <div class="input-field col s6">
                <img class="logo" src="upload/' . htmlspecialchars($institution['logo']) . '" />
            </div>';

    return $html;
}

/**
 * Render form buttons
 */
function renderFormButtons()
{
    return '<div class="row">
                <div class="col 6">
                    <button type="submit" name="submit" class="btn-large blue waves-effect waves-light">
                        SIMPAN <i class="material-icons">done</i>
                    </button>
                </div>
                <div class="col 6">
                    <a href="./admin.php" class="btn-large deep-orange waves-effect waves-light">
                        BATAL <i class="material-icons">clear</i>
                    </a>
                </div>
            </div>';
}
?>
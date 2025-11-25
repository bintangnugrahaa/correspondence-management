<?php
// Check session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

// Handle form submission or display form
if (isset($_REQUEST['submit'])) {
    handleKlasifikasiUpdate();
} else {
    displayKlasifikasiEditForm();
}

/**
 * Handle klasifikasi update process
 */
function handleKlasifikasiUpdate()
{
    global $config;

    $id_klasifikasi = $_REQUEST['id_klasifikasi'];

    // Validate required fields
    if (!validateRequiredFields()) {
        $_SESSION['errEmpty'] = 'ERROR! Semua form wajib diisi';
        header("Location: ./admin.php?page=ref&act=edit&id_klasifikasi=" . $id_klasifikasi);
        exit();
    }

    // Validate input data
    $validationResult = validateKlasifikasiData();
    if (!$validationResult['success']) {
        foreach ($validationResult['errors'] as $sessionKey => $errorMessage) {
            $_SESSION[$sessionKey] = $errorMessage;
        }
        header("Location: ./admin.php?page=ref&act=edit&id_klasifikasi=" . $id_klasifikasi);
        exit();
    }

    // Update klasifikasi data
    if (updateKlasifikasi($id_klasifikasi)) {
        $_SESSION['succEdit'] = 'SUKSES! Data berhasil diupdate';
        header("Location: ./admin.php?page=ref");
        exit();
    } else {
        $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
        header("Location: ./admin.php?page=ref&act=edit&id_klasifikasi=" . $id_klasifikasi);
        exit();
    }
}

/**
 * Validate required fields
 */
function validateRequiredFields()
{
    $requiredFields = ['kode', 'nama', 'uraian'];

    foreach ($requiredFields as $field) {
        if (empty($_REQUEST[$field])) {
            return false;
        }
    }
    return true;
}

/**
 * Validate klasifikasi data
 */
function validateKlasifikasiData()
{
    $validationRules = [
        'kode' => [
            'pattern' => "/^[a-zA-Z0-9. ]*$/",
            'error' => 'kode',
            'message' => 'Form Kode hanya boleh mengandung karakter huruf, angka, spasi dan titik(.)'
        ],
        'nama' => [
            'pattern' => "/^[a-zA-Z0-9.,\/ -]*$/",
            'error' => 'namaref',
            'message' => 'Form Nama hanya boleh mengandung karakter huruf, spasi, titik(.), koma(,) dan minus(-)'
        ],
        'uraian' => [
            'pattern' => "/^[a-zA-Z0-9.,()\/\r\n -]*$/",
            'error' => 'uraian',
            'message' => 'Form Uraian hanya boleh mengandung huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), dan kurung()'
        ]
    ];

    $errors = [];

    foreach ($validationRules as $field => $rule) {
        if (!preg_match($rule['pattern'], $_REQUEST[$field])) {
            $errors[$rule['error']] = $rule['message'];
        }
    }

    return [
        'success' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Update klasifikasi in database
 */
function updateKlasifikasi($id_klasifikasi)
{
    global $config;

    // Sanitize input data
    $kode = mysqli_real_escape_string($config, $_REQUEST['kode']);
    $nama = mysqli_real_escape_string($config, $_REQUEST['nama']);
    $uraian = mysqli_real_escape_string($config, $_REQUEST['uraian']);
    $id_user = $_SESSION['admin'];

    $query = mysqli_query($config, "UPDATE tbl_klasifikasi 
                                   SET kode='$kode', nama='$nama', uraian='$uraian', id_user='$id_user' 
                                   WHERE id_klasifikasi='$id_klasifikasi'");

    return $query;
}

/**
 * Display klasifikasi edit form
 */
function displayKlasifikasiEditForm()
{
    global $config;

    // Check authorization
    if (!in_array($_SESSION['admin'], [1, 2])) {
        echo '<script language="javascript">
                window.alert("ERROR! Anda tidak memiliki hak akses untuk mengedit data ini");
                window.location.href="./admin.php?page=ref";
              </script>';
        exit();
    }

    $id_klasifikasi = mysqli_real_escape_string($config, $_REQUEST['id_klasifikasi']);
    $query = mysqli_query($config, "SELECT * FROM tbl_klasifikasi WHERE id_klasifikasi='$id_klasifikasi'");

    if (mysqli_num_rows($query) > 0) {
        $klasifikasi = mysqli_fetch_assoc($query);
        renderEditForm($klasifikasi);
    } else {
        // Klasifikasi not found
        $_SESSION['errQ'] = 'ERROR! Data klasifikasi tidak ditemukan';
        header("Location: ./admin.php?page=ref");
        exit();
    }
}

/**
 * Render edit form
 */
function renderEditForm($klasifikasi)
{
    echo edit_renderHeader();
    edit_displayAlertMessages();
    echo renderKlasifikasiForm($klasifikasi);
}

/**
 * Render page header
 */
function edit_renderHeader()
{
    return '<!-- Row Start -->
            <div class="row">
                <!-- Secondary Nav START -->
                <div class="col s12">
                    <nav class="secondary-nav">
                        <div class="nav-wrapper blue-grey darken-1">
                            <ul class="left">
                                <li class="waves-effect waves-light">
                                    <a href="#" class="judul">
                                        <i class="material-icons">edit</i> Edit Klasifikasi Surat
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
function edit_displayAlertMessages()
{
    $alertTypes = [
        'errEmpty' => 'red',
        'errQ' => 'red'
    ];

    foreach ($alertTypes as $sessionKey => $color) {
        if (isset($_SESSION[$sessionKey])) {
            echo '<div id="alert-message" class="row">
                    <div class="col m12">
                        <div class="card ' . $color . ' lighten-5">
                            <div class="card-content notif">
                                <span class="card-title ' . $color . '-text">
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
 * Render klasifikasi form
 */
function renderKlasifikasiForm($klasifikasi)
{
    return '<!-- Row form Start -->
            <div class="row jarak-form">
                <!-- Form START -->
                <form class="col s12" method="post" action="?page=ref&act=edit">
                    <!-- Row in form START -->
                    <div class="row">
                        ' . renderFormFields($klasifikasi) . '
                    </div>
                    <!-- Row in form END -->
                    ' . renderFormButtons() . '
                </form>
                <!-- Form END -->
            </div>
            <!-- Row form END -->';
}

/**
 * Render form fields
 */
function renderFormFields($klasifikasi)
{
    $fields = [
        [
            'name' => 'id_klasifikasi',
            'type' => 'hidden',
            'value' => htmlspecialchars($klasifikasi['id_klasifikasi'])
        ],
        [
            'name' => 'kode',
            'label' => '',
            'icon' => 'font_download',
            'type' => 'text',
            'value' => htmlspecialchars($klasifikasi['kode']),
            'attributes' => 'maxlength="30"',
            'error_key' => 'kode',
            'width' => 's3'
        ],
        [
            'name' => 'nama',
            'label' => '',
            'icon' => 'text_fields',
            'type' => 'text',
            'value' => htmlspecialchars($klasifikasi['nama']),
            'error_key' => 'namaref',
            'width' => 's9'
        ],
        [
            'name' => 'uraian',
            'label' => '',
            'icon' => 'subject',
            'type' => 'textarea',
            'value' => htmlspecialchars($klasifikasi['uraian']),
            'error_key' => 'uraian',
            'width' => 's12'
        ]
    ];

    $html = '';
    foreach ($fields as $field) {
        if ($field['type'] === 'hidden') {
            $html .= '<input type="hidden" name="' . $field['name'] . '" value="' . $field['value'] . '">';
            continue;
        }

        $html .= '<div class="input-field col ' . $field['width'] . '">
                    <i class="material-icons prefix md-prefix">' . $field['icon'] . '</i>';

        if ($field['type'] === 'textarea') {
            $html .= '<textarea id="' . $field['name'] . '" class="materialize-textarea" name="' . $field['name'] . '" required>'
                . $field['value'] . '</textarea>';
        } else {
            $html .= '<input id="' . $field['name'] . '" type="' . $field['type'] . '" class="validate" name="' . $field['name'] . '" 
                       value="' . $field['value'] . '" ' . ($field['attributes'] ?? '') . ' required>';
        }

        // Display validation error if exists
        if (isset($_SESSION[$field['error_key']])) {
            $html .= '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'
                . htmlspecialchars($_SESSION[$field['error_key']]) . '</div>';
            unset($_SESSION[$field['error_key']]);
        }

        $html .= '<label for="' . $field['name'] . '">' . $field['label'] . '</label>
                </div>';
    }

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
                    <a href="?page=ref" class="btn-large deep-orange waves-effect waves-light">
                        BATAL <i class="material-icons">clear</i>
                    </a>
                </div>
            </div>';
}
?>
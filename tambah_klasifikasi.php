<?php
// Check session and authorization
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

if (!in_array($_SESSION['admin'], [1, 2])) {
    echo '<script language="javascript">
            window.alert("ERROR! Anda tidak memiliki hak akses untuk menambahkan data");
            window.location.href="./admin.php?page=ref";
          </script>';
    exit();
}

// Handle form submission or display form
if (isset($_REQUEST['submit'])) {
    handleKlasifikasiCreation();
} else {
    displayKlasifikasiCreationForm();
}

/**
 * Handle klasifikasi creation process
 */
function handleKlasifikasiCreation()
{
    global $config;

    // Validate required fields
    if (!validateRequiredFields()) {
        $_SESSION['errEmpty'] = 'ERROR! Semua form wajib diisi';
        header("Location: ./admin.php?page=ref&act=add");
        exit();
    }

    // Validate input data
    $validationResult = validateKlasifikasiData();
    if (!$validationResult['success']) {
        foreach ($validationResult['errors'] as $sessionKey => $errorMessage) {
            $_SESSION[$sessionKey] = $errorMessage;
        }
        header("Location: ./admin.php?page=ref&act=add");
        exit();
    }

    // Check for duplicate kode
    if (isKodeDuplicate($_REQUEST['kode'])) {
        $_SESSION['duplikasi'] = 'Kode sudah ada, pilih yang lainnya!';
        header("Location: ./admin.php?page=ref&act=add");
        exit();
    }

    // Create new klasifikasi
    if (createKlasifikasi()) {
        $_SESSION['succAdd'] = 'SUKSES! Data berhasil ditambahkan';
        header("Location: ./admin.php?page=ref");
        exit();
    } else {
        $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
        header("Location: ./admin.php?page=ref&act=add");
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
            'message' => 'Form Uraian hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), dan kurung()'
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
 * Check if kode already exists
 */
function isKodeDuplicate($kode)
{
    global $config;

    $kode = mysqli_real_escape_string($config, $kode);
    $cek = mysqli_query($config, "SELECT * FROM tbl_klasifikasi WHERE kode='$kode'");

    return mysqli_num_rows($cek) > 0;
}

/**
 * Create new klasifikasi in database
 */
function createKlasifikasi()
{
    global $config;

    // Sanitize input data
    $kode = mysqli_real_escape_string($config, $_REQUEST['kode']);
    $nama = mysqli_real_escape_string($config, $_REQUEST['nama']);
    $uraian = mysqli_real_escape_string($config, $_REQUEST['uraian']);
    $id_user = $_SESSION['admin'];

    $query = mysqli_query($config, "INSERT INTO tbl_klasifikasi(kode, nama, uraian, id_user) 
                                   VALUES('$kode', '$nama', '$uraian', '$id_user')");

    return $query;
}

/**
 * Display klasifikasi creation form
 */
function displayKlasifikasiCreationForm()
{
    echo add_renderHeader();
    add_displayAlertMessages();
    echo renderKlasifikasiForm();
}

/**
 * Render page header
 */
function add_renderHeader()
{
    return '<!-- Row Start -->
            <div class="row">
                <!-- Secondary Nav START -->
                <div class="col s12">
                    <nav class="secondary-nav">
                        <div class="nav-wrapper blue-grey darken-1">
                            <ul class="left">
                                <li class="waves-effect waves-light">
                                    <a href="?page=ref&act=add" class="judul">
                                        <i class="material-icons">bookmark</i> Tambah Klasifikasi Surat
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
function add_displayAlertMessages()
{
    $alertTypes = [
        'errQ' => 'red',
        'errEmpty' => 'red'
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
function renderKlasifikasiForm()
{
    return '<!-- Row form Start -->
            <div class="row jarak-form">
                <!-- Form START -->
                <form class="col s12" method="post" action="?page=ref&act=add">
                    <!-- Row in form START -->
                    <div class="row">
                        ' . renderFormFields() . '
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
function renderFormFields()
{
    $fields = [
        [
            'name' => 'kode',
            'label' => 'Kode',
            'icon' => 'font_download',
            'type' => 'text',
            'attributes' => 'maxlength="30"',
            'error_keys' => ['kode', 'duplikasi'],
            'width' => 's3'
        ],
        [
            'name' => 'nama',
            'label' => 'Nama',
            'icon' => 'text_fields',
            'type' => 'text',
            'error_keys' => ['namaref'],
            'width' => 's9'
        ],
        [
            'name' => 'uraian',
            'label' => 'Uraian',
            'icon' => 'subject',
            'type' => 'textarea',
            'error_keys' => ['uraian'],
            'width' => 's12'
        ]
    ];

    $html = '';
    foreach ($fields as $field) {
        $html .= '<div class="input-field col ' . $field['width'] . '">
                    <i class="material-icons prefix md-prefix">' . $field['icon'] . '</i>';

        if ($field['type'] === 'textarea') {
            $html .= '<textarea id="' . $field['name'] . '" class="materialize-textarea" name="' . $field['name'] . '" required></textarea>';
        } else {
            $html .= '<input id="' . $field['name'] . '" type="' . $field['type'] . '" class="validate" name="' . $field['name'] . '" 
                       ' . ($field['attributes'] ?? '') . ' required>';
        }

        // Display validation errors
        foreach ($field['error_keys'] as $errorKey) {
            if (isset($_SESSION[$errorKey])) {
                $html .= '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'
                    . htmlspecialchars($_SESSION[$errorKey]) . '</div>';
                unset($_SESSION[$errorKey]);
            }
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
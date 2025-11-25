<?php
// Check session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

// Handle form submission or display form
if (isset($_REQUEST['submit'])) {
    handleUserCreation();
} else {
    displayUserCreationForm();
}

/**
 * Handle user creation process
 */
function handleUserCreation()
{
    // Validate required fields
    if (!add_validateRequiredFields()) {
        $_SESSION['errEmpty'] = 'ERROR! Semua form wajib diisi!';
        header("Location: ./admin.php?page=sett&sub=usr&act=add");
        exit();
    }

    // Validate input data
    $validationResult = validateUserData();
    if (!$validationResult['success']) {
        foreach ($validationResult['errors'] as $sessionKey => $errorMessage) {
            $_SESSION[$sessionKey] = $errorMessage;
        }
        header("Location: ./admin.php?page=sett&sub=usr&act=add");
        exit();
    }

    // Create new user
    if (createNewUser()) {
        $_SESSION['succAdd'] = 'SUKSES! User baru berhasil ditambahkan';
        header("Location: ./admin.php?page=sett&sub=usr");
        exit();
    } else {
        $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
        header("Location: ./admin.php?page=sett&sub=usr&act=add");
        exit();
    }
}

/**
 * Validate required fields
 */
function add_validateRequiredFields()
{
    $requiredFields = ['username', 'password', 'nama', 'nip', 'admin'];

    foreach ($requiredFields as $field) {
        if (empty($_REQUEST[$field])) {
            return false;
        }
    }
    return true;
}

/**
 * Validate user data
 */
function validateUserData()
{
    global $config;

    $validationRules = [
        'username' => [
            'pattern' => "/^[a-zA-Z0-9_]*$/",
            'error' => 'uname',
            'message' => 'Form Username hanya boleh mengandung karakter huruf, angka dan underscore (_)'
        ],
        'nama' => [
            'pattern' => "/^[a-zA-Z., ]*$/",
            'error' => 'namauser',
            'message' => 'Form Nama hanya boleh mengandung karakter huruf, spasi, titik(.) dan koma(,)'
        ],
        'nip' => [
            'pattern' => "/^[0-9. -]*$/",
            'error' => 'nipuser',
            'message' => 'Form NIP hanya boleh mengandung karakter angka, spasi dan minus(-)'
        ],
        'admin' => [
            'pattern' => "/^[2-3]$/",
            'error' => 'tipeuser',
            'message' => 'Form Tipe User hanya boleh mengandung karakter angka 2 atau 3'
        ]
    ];

    $errors = [];

    // Validate field patterns
    foreach ($validationRules as $field => $rule) {
        if (!preg_match($rule['pattern'], $_REQUEST[$field])) {
            $errors[$rule['error']] = $rule['message'];
        }
    }

    // Check if username already exists
    $username = mysqli_real_escape_string($config, $_REQUEST['username']);
    $cek = mysqli_query($config, "SELECT * FROM tbl_user WHERE username='$username'");
    if (mysqli_num_rows($cek) > 0) {
        $errors['errUsername'] = 'Username sudah terpakai, gunakan yang lain!';
    }

    // Validate username length
    if (strlen($_REQUEST['username']) < 5) {
        $errors['errUser5'] = 'Username minimal 5 karakter!';
    }

    // Validate password length
    if (strlen($_REQUEST['password']) < 5) {
        $errors['errPassword'] = 'Password minimal 5 karakter!';
    }

    return [
        'success' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Create new user in database
 */
function createNewUser()
{
    global $config;

    // Sanitize input data
    $username = mysqli_real_escape_string($config, $_REQUEST['username']);
    $password = mysqli_real_escape_string($config, $_REQUEST['password']);
    $nama = mysqli_real_escape_string($config, $_REQUEST['nama']);
    $nip = mysqli_real_escape_string($config, $_REQUEST['nip']);
    $admin = mysqli_real_escape_string($config, $_REQUEST['admin']);

    // Insert new user
    $query = mysqli_query($config, "INSERT INTO tbl_user(username, password, nama, nip, admin) 
                                   VALUES('$username', MD5('$password'), '$nama', '$nip', '$admin')");

    return $query;
}

/**
 * Display user creation form
 */
function displayUserCreationForm()
{
    echo add_renderHeader();
    add_displayAlertMessages();
    echo renderUserForm();
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
                                    <a href="?page=sett&sub=usr&act=add" class="judul">
                                        <i class="material-icons">person_add</i> Tambah User
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
 * Render user creation form
 */
function renderUserForm()
{
    return '<!-- Row form Start -->
            <div class="row jarak-form">
                <!-- Form START -->
                <form class="col s12" method="post" action="?page=sett&sub=usr&act=add">
                    <!-- Row in form START -->
                    <div class="row">
                        ' . add_renderFormFields() . '
                    </div>
                    <!-- Row in form END -->
                    <br />
                    ' . add_renderFormButtons() . '
                </form>
                <!-- Form END -->
            </div>
            <!-- Row form END -->';
}

/**
 * Render form fields
 */
function add_renderFormFields()
{
    $fields = [
        [
            'name' => 'username',
            'label' => 'Username',
            'icon' => 'account_circle',
            'type' => 'text',
            'error_keys' => ['uname', 'errUsername', 'errUser5']
        ],
        [
            'name' => 'nama',
            'label' => 'Nama',
            'icon' => 'text_fields',
            'type' => 'text',
            'error_keys' => ['namauser']
        ],
        [
            'name' => 'password',
            'label' => 'Password',
            'icon' => 'lock',
            'type' => 'password',
            'error_keys' => ['errPassword']
        ],
        [
            'name' => 'nip',
            'label' => 'NIP',
            'icon' => 'looks_one',
            'type' => 'text',
            'error_keys' => ['nipuser']
        ],
        [
            'name' => 'admin',
            'label' => 'Pilih Tipe User',
            'icon' => 'supervisor_account',
            'type' => 'select',
            'options' => [
                '3' => 'User Biasa',
                '2' => 'Administrator'
            ],
            'error_keys' => ['tipeuser']
        ]
    ];

    $html = '';
    foreach ($fields as $field) {
        $html .= '<div class="input-field col s6">';

        if ($field['type'] === 'select') {
            $html .= '<i class="material-icons prefix md-prefix">' . $field['icon'] . '</i>
                      <label>' . $field['label'] . '</label><br />
                      <div class="input-field col s11 right">
                          <select class="browser-default validate" name="' . $field['name'] . '" id="' . $field['name'] . '" required>';

            foreach ($field['options'] as $value => $label) {
                $html .= '<option value="' . $value . '">' . $label . '</option>';
            }

            $html .= '</select>
                      </div>';
        } else {
            $html .= '<i class="material-icons prefix md-prefix">' . $field['icon'] . '</i>
                      <input id="' . $field['name'] . '" type="' . $field['type'] . '" 
                             class="validate" name="' . $field['name'] . '" required>
                      <label for="' . $field['name'] . '">' . $field['label'] . '</label>';
        }

        // Display errors
        foreach ($field['error_keys'] as $errorKey) {
            if (isset($_SESSION[$errorKey])) {
                $html .= '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'
                    . htmlspecialchars($_SESSION[$errorKey]) . '</div>';
                unset($_SESSION[$errorKey]);
            }
        }

        $html .= '</div>';
    }

    return $html;
}

/**
 * Render form buttons
 */
function add_renderFormButtons()
{
    return '<div class="row">
                <div class="col 6">
                    <button type="submit" name="submit" class="btn-large blue waves-effect waves-light">
                        SIMPAN <i class="material-icons">done</i>
                    </button>
                </div>
                <div class="col 6">
                    <a href="?page=sett&sub=usr" class="btn-large deep-orange waves-effect waves-light">
                        BATAL <i class="material-icons">clear</i>
                    </a>
                </div>
            </div>';
}
?>
<?php
// Check session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

// Handle profile update or display
if (isset($_REQUEST['sub'])) {
    handleProfileUpdate();
} else {
    displayProfileView();
}

/**
 * Handle profile update process
 */
function handleProfileUpdate()
{
    if (isset($_REQUEST['submit'])) {
        processProfileUpdate();
    } else {
        displayProfileEditForm();
    }
}

/**
 * Process profile update submission
 */
function processProfileUpdate()
{
    global $config;

    $id_user = $_SESSION['id_user'];

    // Validate required fields
    if (!validateRequiredFields()) {
        $_SESSION['errEmpty'] = 'ERROR! Semua form wajib diisi';
        header("Location: ./admin.php?page=pro&sub=pass");
        exit();
    }

    // Validate input data
    $validationResult = validateProfileData();
    if (!$validationResult['success']) {
        foreach ($validationResult['errors'] as $sessionKey => $errorMessage) {
            $_SESSION[$sessionKey] = $errorMessage;
        }
        header("Location: ./admin.php?page=pro&sub=pass");
        exit();
    }

    // Verify old password and update profile
    if (verifyAndUpdateProfile($id_user)) {
        echo '<script language="javascript">
                window.alert("SUKSES! Profil berhasil diupdate");
                window.location.href="./logout.php";
              </script>';
        exit();
    } else {
        echo '<script language="javascript">
                window.alert("ERROR! Password lama tidak sesuai. Anda mungkin tidak memiliki akses ke halaman ini");
                window.location.href="./logout.php";
              </script>';
        exit();
    }
}

/**
 * Validate required fields
 */
function validateRequiredFields()
{
    $requiredFields = ['username', 'password', 'nama', 'nip'];

    foreach ($requiredFields as $field) {
        if (empty($_REQUEST[$field])) {
            return false;
        }
    }
    return true;
}

/**
 * Validate profile data
 */
function validateProfileData()
{
    $validationRules = [
        'username' => [
            'pattern' => "/^[a-zA-Z0-9_]*$/",
            'error' => 'epuname',
            'message' => 'Form Username hanya boleh mengandung karakter huruf, angka dan underscore (_)'
        ],
        'nama' => [
            'pattern' => "/^[a-zA-Z., ]*$/",
            'error' => 'epnama',
            'message' => 'Form Nama hanya boleh mengandung karakter huruf, spasi, titik(.) dan koma(,)'
        ],
        'nip' => [
            'pattern' => "/^[0-9 -]*$/",
            'error' => 'epnip',
            'message' => 'Form NIP hanya boleh mengandung karakter angka, spasi dan minus(-)'
        ]
    ];

    $errors = [];

    foreach ($validationRules as $field => $rule) {
        if (!preg_match($rule['pattern'], $_REQUEST[$field])) {
            $errors[$rule['error']] = $rule['message'];
        }
    }

    // Validate username length
    if (strlen($_REQUEST['username']) < 5) {
        $errors['errEpUname5'] = 'Username minimal 5 karakter!';
    }

    // Validate password length
    if (strlen($_REQUEST['password']) < 5) {
        $errors['errEpPassword5'] = 'Password minimal 5 karakter!';
    }

    return [
        'success' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Verify old password and update profile
 */
function verifyAndUpdateProfile($id_user)
{
    global $config;

    // Sanitize input data
    $username = mysqli_real_escape_string($config, $_REQUEST['username']);
    $password_lama = mysqli_real_escape_string($config, $_REQUEST['password_lama']);
    $password = mysqli_real_escape_string($config, $_REQUEST['password']);
    $nama = mysqli_real_escape_string($config, $_REQUEST['nama']);
    $nip = mysqli_real_escape_string($config, $_REQUEST['nip']);

    // Verify old password
    $query = mysqli_query($config, "SELECT password FROM tbl_user WHERE id_user='$id_user' AND password=MD5('$password_lama')");
    if (mysqli_num_rows($query) == 0) {
        return false;
    }

    // Update profile
    $updateQuery = mysqli_query($config, "UPDATE tbl_user SET username='$username', password=MD5('$password'), nama='$nama', nip='$nip' WHERE id_user='$id_user'");

    if (!$updateQuery) {
        $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
        header("Location: ./admin.php?page=pro&sub=pass");
        exit();
    }

    return true;
}

/**
 * Display profile edit form
 */
function displayProfileEditForm()
{
    echo renderEditHeader();
    displayAlertMessages();
    echo renderProfileEditForm();
}

/**
 * Render edit page header
 */
function renderEditHeader()
{
    return '<!-- UPDATE PROFIL PAGE START-->
            <!-- Row Start -->
            <div class="row">
                <!-- Secondary Nav START -->
                <div class="col s12">
                    <nav class="secondary-nav">
                        <div class="nav-wrapper blue-grey darken-1">
                            <ul class="left">
                                <li class="waves-effect waves-light">
                                    <a href="?page=pro&sub=pass" class="judul">
                                        <i class="material-icons">mode_edit</i> Edit Profil
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
 * Render profile edit form
 */
function renderProfileEditForm()
{
    return '<!-- Row form Start -->
            <div class="row jarak-form">
                <!-- Form START -->
                <form class="col s12" method="post" action="?page=pro&sub=pass">
                    <!-- Row in form START -->
                    <div class="row">
                        ' . renderFormFields() . '
                    </div>
                    <!-- Row in form END -->
                    <br />
                    ' . renderFormButtons() . '
                </form>
                <!-- Form END -->
            </div>
            <!-- Row form END -->
            <!-- UPDATE PROFIL PAGE END-->';
}

/**
 * Render form fields
 */
function renderFormFields()
{
    $fields = [
        [
            'name' => 'username',
            'label' => 'Username',
            'icon' => 'account_circle',
            'type' => 'text',
            'value' => htmlspecialchars($_SESSION['username']),
            'error_keys' => ['epuname', 'errEpUname5']
        ],
        [
            'name' => 'nama',
            'label' => 'Nama',
            'icon' => 'text_fields',
            'type' => 'text',
            'value' => htmlspecialchars($_SESSION['nama']),
            'error_keys' => ['epnama']
        ],
        [
            'name' => 'password_lama',
            'label' => 'Password Lama',
            'icon' => 'lock_outline',
            'type' => 'password',
            'value' => '',
            'error_keys' => []
        ],
        [
            'name' => 'nip',
            'label' => 'NIP',
            'icon' => 'looks_one',
            'type' => 'text',
            'value' => htmlspecialchars($_SESSION['nip']),
            'error_keys' => ['epnip']
        ],
        [
            'name' => 'password',
            'label' => 'Password Baru',
            'icon' => 'lock',
            'type' => 'password',
            'value' => '',
            'error_keys' => ['errEpPassword5'],
            'note' => '*Setelah menekan tombol "Simpan", Anda akan diminta melakukan Login ulang.'
        ]
    ];

    $html = '';
    foreach ($fields as $field) {
        $html .= '<div class="input-field col s6">
                    <i class="material-icons prefix md-prefix">' . $field['icon'] . '</i>
                    <input id="' . $field['name'] . '" type="' . $field['type'] . '" 
                           class="validate" name="' . $field['name'] . '" 
                           value="' . $field['value'] . '" ' .
            ($field['name'] === 'nip' ? 'autocomplete="off"' : '') . ' required>';

        // Display errors
        foreach ($field['error_keys'] as $errorKey) {
            if (isset($_SESSION[$errorKey])) {
                $html .= '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'
                    . htmlspecialchars($_SESSION[$errorKey]) . '</div>';
                unset($_SESSION[$errorKey]);
            }
        }

        $html .= '<label for="' . $field['name'] . '">' . $field['label'] . '</label>';

        // Add note if exists
        if (isset($field['note'])) {
            $html .= '<small class="red-text">' . $field['note'] . '</small>';
        }

        $html .= '</div>';
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
                    <a href="?page=pro" class="btn-large deep-orange waves-effect waves-light">
                        BATAL <i class="material-icons">clear</i>
                    </a>
                </div>
            </div>';
}

/**
 * Display profile view
 */
function displayProfileView()
{
    echo '<!-- SHOW PROFIL PAGE START-->
          <!-- Row Start -->
          <div class="row">
            <!-- Secondary Nav START -->
            <div class="col s12">
                <nav class="secondary-nav">
                    <div class="nav-wrapper blue-grey darken-1">
                        <ul class="left">
                            <li class="waves-effect waves-light">
                                <a href="#" class="judul">
                                    <i class="material-icons">person</i> Profil User
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
            <!-- Secondary Nav END -->
          </div>
          <!-- Row END -->
          
          <!-- Row form Start -->
          <div class="row jarak-form">
            <!-- Form START -->
            <form class="col s12" method="post" action="save.php">
                <!-- Row in form START -->
                <div class="row">
                    ' . renderProfileViewFields() . '
                </div>
                <!-- Row in form END -->
                <br />
                <div class="row">
                    <div class="col m12">
                        <a href="?page=pro&sub=pass" class="btn-large blue waves-effect waves-light">
                            EDIT PROFIL <i class="material-icons">mode_edit</i>
                        </a>
                    </div>
                </div>
            </form>
            <!-- Form END -->
          </div>
          <!-- Row form END -->
          <!-- SHOW PROFIL PAGE START-->';
}

/**
 * Render profile view fields
 */
function renderProfileViewFields()
{
    $fields = [
        [
            'name' => 'username',
            'label' => 'Username',
            'icon' => 'account_circle',
            'value' => htmlspecialchars($_SESSION['username'])
        ],
        [
            'name' => 'nama',
            'label' => 'Nama',
            'icon' => 'text_fields',
            'value' => htmlspecialchars($_SESSION['nama'])
        ],
        [
            'name' => 'password',
            'label' => 'Password',
            'icon' => 'lock',
            'value' => '*'
        ],
        [
            'name' => 'nip',
            'label' => 'NIP',
            'icon' => 'looks_one',
            'value' => htmlspecialchars($_SESSION['nip'])
        ]
    ];

    $html = '';
    foreach ($fields as $field) {
        $html .= '<div class="input-field col s6">
                    <i class="material-icons prefix md-prefix">' . $field['icon'] . '</i>
                    <input id="' . $field['name'] . '" type="text" value="' . $field['value'] . '" readonly disabled>
                    <label for="' . $field['name'] . '">' . $field['label'] . '</label>
                  </div>';
    }

    return $html;
}
?>
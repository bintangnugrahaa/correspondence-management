<?php
if (!isset($config)) {
    if (isset($GLOBALS['config'])) {
        $config = $GLOBALS['config'];
    } else {
        require_once '../config.php';
    }
}

// Check session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

// Validate and process user type edit
$id_user = isset($_REQUEST['id_user']) ? mysqli_real_escape_string($config, $_REQUEST['id_user']) : null;

// Check authorization and constraints
if (!validateEditAuthorization($id_user)) {
    exit();
}

// Handle form submission
if (isset($_REQUEST['submit'])) {
    handleUserTypeUpdate($id_user);
    exit();
}

// Display edit form
displayUserTypeEditForm($id_user);

/**
 * Validate if user can be edited
 */
function validateEditAuthorization($id_user)
{
    // Prevent editing super admin (id_user = 1)
    if ($id_user == 1) {
        echo '<script language="javascript">
                window.alert("ERROR! Super Admin tidak boleh diedit");
                window.location.href="./admin.php?page=sett&sub=usr";
              </script>';
        return false;
    }

    // Prevent users from editing their own account
    if ($id_user == $_SESSION['id_user']) {
        echo '<script language="javascript">
                window.alert("ERROR! Anda tidak diperbolehkan mengedit tipe akun Anda sendiri. Hubungi super admin untuk mengeditnya");
                window.location.href="./admin.php?page=sett&sub=usr";
              </script>';
        return false;
    }

    return true;
}

/**
 * Handle user type update
 */
function handleUserTypeUpdate($id_user)
{
    global $config;

    // Double-check authorization (in case form is manipulated)
    if ($id_user == $_SESSION['id_user']) {
        echo '<script language="javascript">
                window.alert("ERROR! Anda tidak boleh mengedit akun Anda sendiri. Hubungi super admin untuk mengeditnya");
                window.location.href="./admin.php?page=sett&sub=usr";
              </script>';
        exit();
    }

    $admin = $_REQUEST['admin'];

    // Validate user type input
    if (!validateUserType($admin)) {
        $_SESSION['tipeuser'] = 'Form Tipe User hanya boleh mengandung karakter angka 2 atau 3';
        header("Location: ./admin.php?page=sett&sub=usr&act=edit&id_user=" . $id_user);
        exit();
    }

    // Update user type
    if (updateUserType($id_user, $admin)) {
        $_SESSION['succEdit'] = 'SUKSES! Tipe user berhasil diupdate';
        header("Location: ./admin.php?page=sett&sub=usr");
        exit();
    } else {
        $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
        header("Location: ./admin.php?page=sett&sub=usr&act=edit&id_user=" . $id_user);
        exit();
    }
}

/**
 * Validate user type input
 */
function validateUserType($admin)
{
    return preg_match("/^[2-3]$/", $admin);
}

/**
 * Update user type in database
 */
function updateUserType($id_user, $admin)
{
    global $config;
    return mysqli_query($config, "UPDATE tbl_user SET admin='$admin' WHERE id_user='$id_user'");
}

/**
 * Display user type edit form
 */
function displayUserTypeEditForm($id_user)
{
    global $config;

    displayErrorMessages();

    $query = mysqli_query($config, "SELECT * FROM tbl_user WHERE id_user='$id_user'");

    if (mysqli_num_rows($query) > 0) {
        $user = mysqli_fetch_assoc($query);
        renderEditForm($user);
    } else {
        // User not found
        $_SESSION['errQ'] = 'ERROR! User tidak ditemukan';
        header("Location: ./admin.php?page=sett&sub=usr");
        exit();
    }
}

/**
 * Display error messages
 */
function displayErrorMessages()
{
    if (isset($_SESSION['errQ'])) {
        echo '<div id="alert-message" class="row">
                <div class="col m12">
                    <div class="card red lighten-5">
                        <div class="card-content notif">
                            <span class="card-title red-text">
                                <i class="material-icons md-36">clear</i> '
            . htmlspecialchars($_SESSION['errQ']) . '
                            </span>
                        </div>
                    </div>
                </div>
            </div>';
        unset($_SESSION['errQ']);
    }
}

/**
 * Render edit form
 */
function renderEditForm($user)
{
    echo '<!-- Row Start -->
          <div class="row">
            <!-- Secondary Nav START -->
            <div class="col s12">
                <nav class="secondary-nav">
                    <div class="nav-wrapper blue-grey darken-1">
                        <ul class="left">
                            <li class="waves-effect waves-light tooltipped" data-position="right"
                                data-tooltip="Menu ini hanya untuk mengedit tipe user. Username dan password bisa diganti lewat menu profil">
                                <a href="#" class="judul">
                                    <i class="material-icons">mode_edit</i> Edit Tipe User
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
            <form class="col s12" method="post" action="?page=sett&sub=usr&act=edit">
                <!-- Row in form START -->
                <div class="row">
                    ' . renderUserInfoFields($user) . '
                    ' . renderUserTypeField($user) . '
                </div>
                <!-- Row in form END -->
                <br />
                ' . edit_renderFormButtons() . '
            </form>
            <!-- Form END -->
          </div>
          <!-- Row form END -->';
}

/**
 * Render user information fields (read-only)
 */
function renderUserInfoFields($user)
{
    return '<div class="input-field col s6">
                <input type="hidden" value="' . htmlspecialchars($user['id_user']) . '" name="id_user">
                <i class="material-icons prefix md-prefix">account_circle</i>
                <input id="username" type="text" value="' . htmlspecialchars($user['username']) . '" readonly class="grey-text">
                <label for="username"></label>
            </div>
            <div class="input-field col s6">
                <i class="material-icons prefix md-prefix">text_fields</i>
                <input id="nama" type="text" value="' . htmlspecialchars($user['nama']) . '" readonly class="grey-text">
                <label for="nama"></label>
            </div>';
}

/**
 * Render user type selection field
 */
function renderUserTypeField($user)
{
    $userTypes = [
        2 => 'Administrator',
        3 => 'User Biasa'
    ];

    $currentType = $user['admin'];
    $currentLabel = $userTypes[$currentType] ?? 'User Biasa';

    $html = '<div class="input-field col s6">
                <i class="material-icons prefix md-prefix">supervisor_account</i>
                <label>Pilih tipe user</label><br />
                <div class="input-field col s11 right">
                    <select class="browser-default" name="admin" id="admin" required>
                        <option value="' . $currentType . '">' . $currentLabel . '</option>';

    // Add other available options
    foreach ($userTypes as $value => $label) {
        if ($value != $currentType) {
            $html .= '<option value="' . $value . '">' . $label . '</option>';
        }
    }

    $html .= '</select>
            </div>';

    // Display validation error if exists
    if (isset($_SESSION['tipeuser'])) {
        $html .= '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'
            . htmlspecialchars($_SESSION['tipeuser']) . '</div>';
        unset($_SESSION['tipeuser']);
    }

    $html .= '</div>';

    return $html;
}

/**
 * Render form buttons
 */
function edit_renderFormButtons()
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
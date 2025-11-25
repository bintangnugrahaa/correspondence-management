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

// Validate and process user deletion
$id_user = mysqli_real_escape_string($config, $_REQUEST['id_user']);

// Check authorization and constraints
if (!validateDeletionAuthorization($id_user)) {
    exit();
}

// Handle form submission
if (isset($_REQUEST['submit'])) {
    handleUserDeletion($id_user);
    exit();
}

// Display confirmation page
displayDeletionConfirmation($id_user);

/**
 * Validate if user can be deleted
 */
function validateDeletionAuthorization($id_user)
{
    // Prevent deletion of super admin (id_user = 1)
    if ($id_user == 1) {
        echo '<script language="javascript">
                window.alert("ERROR! Super Admin tidak boleh dihapus");
                window.location.href="./admin.php?page=sett&sub=usr";
              </script>';
        return false;
    }

    // Prevent users from deleting their own account
    if ($id_user == $_SESSION['id_user']) {
        echo '<script language="javascript">
                window.alert("ERROR! Anda tidak diperbolehkan menghapus akun Anda sendiri. Hubungi super admin untuk menghapusnya");
                window.location.href="./admin.php?page=sett&sub=usr";
              </script>';
        return false;
    }

    return true;
}

/**
 * Handle user deletion
 */
function handleUserDeletion($id_user)
{
    global $config;

    $query = mysqli_query($config, "DELETE FROM tbl_user WHERE id_user='$id_user'");

    if ($query) {
        $_SESSION['succDel'] = 'SUKSES! User berhasil dihapus';
        header("Location: ./admin.php?page=sett&sub=usr");
        exit();
    } else {
        $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
        header("Location: ./admin.php?page=sett&sub=usr&act=del&id_user=" . $id_user);
        exit();
    }
}

/**
 * Display deletion confirmation page
 */
function displayDeletionConfirmation($id_user)
{
    global $config;

    displayErrorMessages();

    $query = mysqli_query($config, "SELECT * FROM tbl_user WHERE id_user='$id_user'");

    if (mysqli_num_rows($query) > 0) {
        $user = mysqli_fetch_assoc($query);
        renderConfirmationPage($user);
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
        echo '<div id="alert-message" class="row jarak-card">
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
 * Render confirmation page
 */
function renderConfirmationPage($user)
{
    echo '<!-- Row form Start -->
          <div class="row jarak-card">
            <div class="col m12">
                <div class="card">
                    <div class="card-content">
                        ' . renderUserDetailsTable($user) . '
                    </div>
                    <div class="card-action">
                        ' . del_renderActionButtons($user) . '
                    </div>
                </div>
            </div>
          </div>
          <!-- Row form END -->';
}

/**
 * Render user details table
 */
function renderUserDetailsTable($user)
{
    return '<table>
                <thead class="red lighten-5 red-text">
                    <div class="confir red-text">
                        <i class="material-icons md-36">error_outline</i>
                        Apakah Anda yakin akan menghapus user ini?
                    </div>
                </thead>
                <tbody>
                    ' . renderUserDetailRows($user) . '
                </tbody>
            </table>';
}

/**
 * Render user detail rows
 */
function renderUserDetailRows($user)
{
    $details = [
        'Username' => htmlspecialchars($user['username']),
        'Nama' => htmlspecialchars($user['nama']),
        'NIP' => htmlspecialchars($user['nip']),
        'Tipe User' => getUserTypeDescription($user['admin'])
    ];

    $html = '';
    foreach ($details as $label => $value) {
        $html .= '<tr>
                    <td width="13%">' . $label . '</td>
                    <td width="1%">:</td>
                    <td width="86%">' . $value . '</td>
                  </tr>';
    }

    return $html;
}

/**
 * Get user type description
 */
function getUserTypeDescription($adminLevel)
{
    $userTypes = [
        2 => 'Administrator',
        3 => 'User Biasa'
    ];

    return $userTypes[$adminLevel] ?? 'User Biasa';
}

/**
 * Render action buttons
 */
function del_renderActionButtons($user)
{
    return '<a href="?page=sett&sub=usr&act=del&submit=yes&id_user=' . $user['id_user'] . '" 
               class="btn-large deep-orange waves-effect waves-light white-text">
                HAPUS <i class="material-icons">delete</i>
            </a>
            <a href="?page=sett&sub=usr" 
               class="btn-large blue waves-effect waves-light white-text">
                BATAL <i class="material-icons">clear</i>
            </a>';
}
?>
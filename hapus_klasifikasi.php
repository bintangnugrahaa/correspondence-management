<?php
// Check session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

global $config;

// Validate and process klasifikasi deletion
$id_klasifikasi = mysqli_real_escape_string($config, $_REQUEST['id_klasifikasi']);

// Check authorization and display confirmation or process deletion
if (!validateDeletionAuthorization()) {
    exit();
}

// Handle form submission
if (isset($_REQUEST['submit'])) {
    handleKlasifikasiDeletion($id_klasifikasi);
    exit();
}

// Display confirmation page
displayDeletionConfirmation($id_klasifikasi);

/**
 * Validate if user can delete klasifikasi
 */
function userHasDeletionAccess()
{
    if (!isset($_SESSION['admin'])) {
        return false;
    }

    $role = strtolower(trim((string) $_SESSION['admin']));
    $allowedRoles = ['1', '2', 'super admin', 'administrator'];

    return in_array($role, $allowedRoles, true);
}

/**
 * Validate if user can delete klasifikasi
 */
function validateDeletionAuthorization()
{
    global $config, $id_klasifikasi;

    // Check if klasifikasi exists
    $query = mysqli_query($config, "SELECT * FROM tbl_klasifikasi WHERE id_klasifikasi='$id_klasifikasi'");
    if (mysqli_num_rows($query) == 0) {
        $_SESSION['errQ'] = 'ERROR! Data klasifikasi tidak ditemukan';
        header("Location: ./admin.php?page=ref");
        exit();
    }

    // Check authorization (only admin levels 1 and 2 can delete)
    if (!userHasDeletionAccess()) {
        echo '<script language="javascript">
                window.alert("ERROR! Anda tidak memiliki hak akses untuk menghapus data ini");
                window.location.href="./admin.php?page=ref";
              </script>';
        return false;
    }

    return true;
}

/**
 * Handle klasifikasi deletion
 */
function handleKlasifikasiDeletion($id_klasifikasi)
{
    global $config;

    $query = mysqli_query($config, "DELETE FROM tbl_klasifikasi WHERE id_klasifikasi='$id_klasifikasi'");

    if ($query) {
        $_SESSION['succDel'] = 'SUKSES! Data berhasil dihapus';
        header("Location: ./admin.php?page=ref");
        exit();
    } else {
        $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
        header("Location: ./admin.php?page=ref&act=del&id_klasifikasi=" . $id_klasifikasi);
        exit();
    }
}

/**
 * Display deletion confirmation page
 */
function displayDeletionConfirmation($id_klasifikasi)
{
    global $config;

    displayErrorMessages();

    $query = mysqli_query($config, "SELECT * FROM tbl_klasifikasi WHERE id_klasifikasi='$id_klasifikasi'");

    if (mysqli_num_rows($query) > 0) {
        $klasifikasi = mysqli_fetch_assoc($query);
        renderConfirmationPage($klasifikasi);
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
function renderConfirmationPage($klasifikasi)
{
    echo '<!-- Row form Start -->
          <div class="row jarak-card">
            <div class="col m12">
                <div class="card">
                    <div class="card-content">
                        ' . renderKlasifikasiDetailsTable($klasifikasi) . '
                    </div>
                    <div class="card-action">
                        ' . renderActionButtons($klasifikasi) . '
                    </div>
                </div>
            </div>
          </div>
          <!-- Row form END -->';
}

/**
 * Render klasifikasi details table
 */
function renderKlasifikasiDetailsTable($klasifikasi)
{
    return '<table>
                <thead class="red lighten-5 red-text">
                    <div class="confir red-text">
                        <i class="material-icons md-36">error_outline</i>
                        Apakah Anda yakin akan menghapus data ini?
                    </div>
                </thead>
                <tbody>
                    ' . renderKlasifikasiDetailRows($klasifikasi) . '
                </tbody>
            </table>';
}

/**
 * Render klasifikasi detail rows
 */
function renderKlasifikasiDetailRows($klasifikasi)
{
    $details = [
        'Kode' => htmlspecialchars($klasifikasi['kode']),
        'Nama' => htmlspecialchars($klasifikasi['nama']),
        'Uraian' => htmlspecialchars($klasifikasi['uraian'])
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
 * Render action buttons
 */
function renderActionButtons($klasifikasi)
{
    return '<a href="?page=ref&act=del&submit=yes&id_klasifikasi=' . $klasifikasi['id_klasifikasi'] . '" 
               class="btn-large deep-orange waves-effect waves-light white-text">
                HAPUS <i class="material-icons">delete</i>
            </a>
            <a href="?page=ref" 
               class="btn-large blue waves-effect waves-light white-text">
                BATAL <i class="material-icons">clear</i>
            </a>';
}
?>
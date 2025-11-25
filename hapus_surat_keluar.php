<?php
// Check session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

if (isset($_REQUEST['submit'])) {
    handleDeleteSubmission();
} else {
    displayDeleteConfirmation();
}

/**
 * Handle delete submission
 */
function handleDeleteSubmission()
{
    global $config;

    $id_surat = mysqli_real_escape_string($config, $_REQUEST['id_surat']);

    // Check user permissions and get surat data
    $suratData = getSuratDataWithPermissionCheck($id_surat);
    if (!$suratData) {
        return;
    }

    // Perform deletion
    if (deleteSuratKeluar($id_surat, $suratData['file'])) {
        $_SESSION['succDel'] = 'SUKSES! Data berhasil dihapus';
        header("Location: ./admin.php?page=tsk");
        exit();
    } else {
        $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
        header("Location: ./admin.php?page=tsk&act=del&id_surat=" . $id_surat);
        exit();
    }
}

/**
 * Get surat data with permission check
 */
function getSuratDataWithPermissionCheck($id_surat)
{
    global $config;

    $query = mysqli_query($config, "SELECT * FROM tbl_surat_keluar WHERE id_surat='$id_surat'");

    if (!$query || mysqli_num_rows($query) == 0) {
        echo '<script>alert("Data tidak ditemukan"); window.location.href="./admin.php?page=tsk";</script>';
        return false;
    }

    $suratData = mysqli_fetch_assoc($query);

    // Check user permissions
    if ($_SESSION['id_user'] != $suratData['id_user'] && $_SESSION['id_user'] != 1) {
        echo '<script>
                alert("ERROR! Anda tidak memiliki hak akses untuk menghapus data ini");
                window.location.href="./admin.php?page=tsk";
              </script>';
        return false;
    }

    return $suratData;
}

/**
 * Delete surat keluar
 */
function deleteSuratKeluar($id_surat, $fileName)
{
    global $config;

    // Delete file if exists
    if (!empty($fileName) && file_exists("upload/surat_keluar/" . $fileName)) {
        unlink("upload/surat_keluar/" . $fileName);
    }

    // Delete from surat_keluar table
    return mysqli_query($config, "DELETE FROM tbl_surat_keluar WHERE id_surat='$id_surat'");
}

/**
 * Display delete confirmation
 */
function displayDeleteConfirmation()
{
    global $config;

    $id_surat = mysqli_real_escape_string($config, $_REQUEST['id_surat']);
    $suratData = getSuratDataWithPermissionCheck($id_surat);

    if (!$suratData) {
        return;
    }

    displayErrorMessage();
    displayConfirmationForm($suratData);
}

/**
 * Display error message if exists
 */
function displayErrorMessage()
{
    if (isset($_SESSION['errQ'])) {
        $errQ = $_SESSION['errQ'];
        echo '
        <div id="alert-message" class="row jarak-card">
            <div class="col m12">
                <div class="card red lighten-5">
                    <div class="card-content notif">
                        <span class="card-title red-text">
                            <i class="material-icons md-36">clear</i> ' . htmlspecialchars($errQ) . '
                        </span>
                    </div>
                </div>
            </div>
        </div>';
        unset($_SESSION['errQ']);
    }
}

/**
 * Display confirmation form
 */
function displayConfirmationForm($suratData)
{
    ?>
    <!-- Row form Start -->
    <div class="row jarak-card">
        <div class="col m12">
            <div class="card">
                <div class="card-content">
                    <table>
                        <thead class="red lighten-5 red-text">
                            <tr>
                                <th colspan="3">
                                    <div class="confir red-text">
                                        <i class="material-icons md-36">error_outline</i>
                                        Apakah Anda yakin akan menghapus data ini?
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php displaySuratDetails($suratData); ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-action">
                    <a href="?page=tsk&act=del&submit=yes&id_surat=<?php echo $suratData['id_surat']; ?>"
                        class="btn-large deep-orange waves-effect waves-light white-text">
                        HAPUS <i class="material-icons">delete</i>
                    </a>
                    <a href="?page=tsk" class="btn-large blue waves-effect waves-light white-text">
                        BATAL <i class="material-icons">clear</i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- Row form END -->
    <?php
}

/**
 * Display surat details
 */
function displaySuratDetails($suratData)
{
    $details = [
        'No. Agenda' => htmlspecialchars($suratData['no_agenda']),
        'Kode Klasifikasi' => htmlspecialchars($suratData['kode']),
        'Isi Ringkas' => htmlspecialchars($suratData['isi']),
        'File' => getFileDisplay($suratData),
        'Tujuan' => htmlspecialchars($suratData['tujuan']),
        'No. Surat' => htmlspecialchars($suratData['no_surat']),
        'Tanggal Surat' => indoDate($suratData['tgl_surat']),
        'Keterangan' => htmlspecialchars($suratData['keterangan'])
    ];

    foreach ($details as $label => $value) {
        echo '
        <tr>
            <td width="13%">' . $label . '</td>
            <td width="1%">:</td>
            <td width="86%">' . $value . '</td>
        </tr>';
    }
}

/**
 * Get file display information
 */
function getFileDisplay($suratData)
{
    if (!empty($suratData['file'])) {
        return '<a class="blue-text" href="?page=gsk&act=fsk&id_surat=' . $suratData['id_surat'] . '">' .
            htmlspecialchars($suratData['file']) . '</a>';
    } else {
        return 'Tidak ada file yang diupload';
    }
}
?>
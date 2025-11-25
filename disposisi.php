<?php
// Check session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

if (isset($_REQUEST['sub'])) {
    handleSubAction($_REQUEST['sub']);
} else {
    displayDisposisiPage();
}

/**
 * Handle sub actions
 */
function handleSubAction($sub)
{
    $allowedActions = [
        'add' => 'tambah_disposisi.php',
        'edit' => 'edit_disposisi.php',
        'del' => 'hapus_disposisi.php'
    ];

    if (isset($allowedActions[$sub])) {
        include $allowedActions[$sub];
        exit();
    }
}

/**
 * Display main disposisi page
 */
function displayDisposisiPage()
{
    global $config;

    $id_surat = $_REQUEST['id_surat'];
    $suratData = getSuratData($id_surat);

    if (!$suratData) {
        redirectWithError('Data surat tidak ditemukan', 'tsm');
        return;
    }

    // Check user permissions
    if (!checkUserPermissions($suratData['id_user'])) {
        redirectWithError('ERROR! Anda tidak memiliki hak akses untuk melihat data ini', 'tsm');
        return;
    }

    displayPageContent($suratData);
}

/**
 * Get surat data from database
 */
function getSuratData($id_surat)
{
    global $config;

    $id_surat = mysqli_real_escape_string($config, $id_surat);
    $query = mysqli_query($config, "SELECT * FROM tbl_surat_masuk WHERE id_surat='$id_surat'");

    if ($query && mysqli_num_rows($query) > 0) {
        return mysqli_fetch_assoc($query);
    }

    return false;
}

/**
 * Check user permissions
 */
function checkUserPermissions($recordOwnerId)
{
    return ($_SESSION['id_user'] == 1 || $_SESSION['id_user'] == $recordOwnerId);
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
 * Display page content
 */
function displayPageContent($suratData)
{
    disp_displayNavigationHeader($suratData['id_surat']);
    displayPerihalSurat($suratData['isi']);
    disp_displaySuccessMessages();
    displayDisposisiTable($suratData['id_surat']);
}

/**
 * Display navigation header
 */
function disp_displayNavigationHeader($id_surat)
{
    ?>
    <!-- Row Start -->
    <div class="row">
        <!-- Secondary Nav START -->
        <div class="col s12">
            <div class="z-depth-1">
                <nav class="secondary-nav">
                    <div class="nav-wrapper blue-grey darken-1">
                        <div class="col m12">
                            <ul class="left">
                                <li class="waves-effect waves-light hide-on-small-only">
                                    <a href="#" class="judul">
                                        <i class="material-icons">description</i> Disposisi Surat
                                    </a>
                                </li>
                                <li class="waves-effect waves-light">
                                    <a href="?page=tsm&act=disp&id_surat=<?php echo $id_surat; ?>&sub=add">
                                        <i class="material-icons md-24">add_circle</i> Tambah Disposisi
                                    </a>
                                </li>
                                <li class="waves-effect waves-light hide-on-small-only">
                                    <a href="?page=tsm">
                                        <i class="material-icons">arrow_back</i> Kembali
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
        <!-- Secondary Nav END -->
    </div>
    <!-- Row END -->
    <?php
}

/**
 * Display perihal surat
 */
function displayPerihalSurat($isi)
{
    ?>
    <!-- Perihal START -->
    <div class="col s12">
        <div class="card blue lighten-5">
            <div class="card-content">
                <p>
                <p class="description">Perihal Surat:</p><?php echo htmlspecialchars($isi); ?></p>
            </div>
        </div>
    </div>
    <!-- Perihal END -->
    <?php
}

/**
 * Display success messages
 */
function disp_displaySuccessMessages()
{
    $messages = [
        'succAdd' => 'Data berhasil ditambahkan',
        'succEdit' => 'Data berhasil diubah',
        'succDel' => 'Data berhasil dihapus'
    ];

    foreach ($messages as $sessionKey => $defaultMessage) {
        if (isset($_SESSION[$sessionKey])) {
            disp_displayAlertMessage($_SESSION[$sessionKey], 'green');
            unset($_SESSION[$sessionKey]);
        }
    }
}

/**
 * Display alert message
 */
function disp_displayAlertMessage($message, $color = 'green')
{
    echo '
    <div id="alert-message" class="row">
        <div class="col m12">
            <div class="card ' . $color . ' lighten-5">
                <div class="card-content notif">
                    <span class="card-title ' . $color . '-text">
                        <i class="material-icons md-36">done</i> ' . htmlspecialchars($message) . '
                    </span>
                </div>
            </div>
        </div>
    </div>';
}

/**
 * Display disposisi table
 */
function displayDisposisiTable($id_surat)
{
    global $config;

    $disposisiData = getDisposisiData($id_surat);
    ?>

    <!-- Row form Start -->
    <div class="row jarak-form">
        <div class="col m12" id="colres">
            <table class="bordered" id="tbl">
                <thead class="blue lighten-4" id="head">
                    <tr>
                        <th width="6%">No</th>
                        <th width="22%">Tujuan Disposisi</th>
                        <th width="32%">Isi Disposisi</th>
                        <th width="24%">Sifat<br />Batas Waktu</th>
                        <th width="16%">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php displayDisposisiTableContent($disposisiData, $id_surat); ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Row form END -->
    <?php
}

/**
 * Get disposisi data from database
 */
function getDisposisiData($id_surat)
{
    global $config;

    $id_surat = mysqli_real_escape_string($config, $id_surat);
    $query = mysqli_query(
        $config,
        "SELECT * FROM tbl_disposisi 
         JOIN tbl_surat_masuk ON tbl_disposisi.id_surat = tbl_surat_masuk.id_surat 
         WHERE tbl_disposisi.id_surat='$id_surat'"
    );

    return $query;
}

/**
 * Display disposisi table content
 */
function displayDisposisiTableContent($query, $id_surat)
{
    if (mysqli_num_rows($query) > 0) {
        $no = 0;
        while ($row = mysqli_fetch_array($query)) {
            $no++;
            displayDisposisiRow($row, $no, $id_surat);
        }
    } else {
        displayEmptyTableMessage($id_surat);
    }
}

/**
 * Display single disposisi row
 */
function displayDisposisiRow($row, $no, $id_surat)
{
    ?>
    <tr>
        <td><?php echo $no; ?></td>
        <td><?php echo htmlspecialchars($row['tujuan']); ?></td>
        <td><?php echo htmlspecialchars($row['isi_disposisi']); ?></td>
        <td>
            <?php echo htmlspecialchars($row['sifat']); ?><br />
            <?php echo indoDate($row['batas_waktu']); ?>
        </td>
        <td>
            <a class="btn small blue waves-effect waves-light"
                href="?page=tsm&act=disp&id_surat=<?php echo $id_surat; ?>&sub=edit&id_disposisi=<?php echo $row['id_disposisi']; ?>">
                <i class="material-icons">edit</i> EDIT
            </a>
            <a class="btn small deep-orange waves-effect waves-light"
                href="?page=tsm&act=disp&id_surat=<?php echo $id_surat; ?>&sub=del&id_disposisi=<?php echo $row['id_disposisi']; ?>">
                <i class="material-icons">delete</i> DEL
            </a>
        </td>
    </tr>
    <?php
}

/**
 * Display empty table message
 */
function displayEmptyTableMessage($id_surat)
{
    ?>
    <tr>
        <td colspan="5">
            <center>
                <p class="add">
                    Tidak ada data untuk ditampilkan.
                    <u>
                        <a href="?page=tsm&act=disp&id_surat=<?php echo $id_surat; ?>&sub=add">
                            Tambah data baru
                        </a>
                    </u>
                </p>
            </center>
        </td>
    </tr>
    <?php
}
?>
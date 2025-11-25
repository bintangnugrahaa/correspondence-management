<?php
// Check session and permissions
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

// Check admin permissions (only admin level 1 and 3 allowed)
if ($_SESSION['admin'] != 1 && $_SESSION['admin'] != 3) {
    echo '<script language="javascript">
            window.alert("ERROR! Anda tidak memiliki hak akses untuk membuka halaman ini");
            window.location.href="./logout.php";
          </script>';
    exit();
}

// Handle actions
if (isset($_REQUEST['act'])) {
    handleAction($_REQUEST['act']);
    exit();
}

// Main page logic
displaySuratKeluarPage();

/**
 * Handle different actions
 */
function handleAction($action)
{
    $allowedActions = [
        'add' => 'tambah_surat_keluar.php',
        'edit' => 'edit_surat_keluar.php',
        'del' => 'hapus_surat_keluar.php'
    ];

    if (isset($allowedActions[$action])) {
        include $allowedActions[$action];
        exit();
    }
}

/**
 * Display main surat keluar page
 */
function displaySuratKeluarPage()
{
    global $config;

    $limit = getPageLimit();
    $currentPage = getCurrentPage();
    $offset = ($currentPage - 1) * $limit;

    displayNavigationHeader();
    displaySuccessMessages();

    if (isset($_REQUEST['submit'])) {
        handleSearch($limit);
    } else {
        displaySuratKeluarTable($offset, $limit);
        displayPagination($limit);
    }
}

/**
 * Get page limit from settings
 */
function getPageLimit()
{
    global $config;

    $query = mysqli_query($config, "SELECT surat_keluar FROM tbl_sett LIMIT 1");
    if ($query && $row = mysqli_fetch_array($query)) {
        return (int) $row[0];
    }
    return 10; // Default fallback
}

/**
 * Get current page number
 */
function getCurrentPage()
{
    return isset($_GET['pg']) ? max(1, (int) $_GET['pg']) : 1;
}

/**
 * Display navigation header
 */
function displayNavigationHeader()
{
    ?>
    <!-- Row Start -->
    <div class="row">
        <!-- Secondary Nav START -->
        <div class="col s12">
            <div class="z-depth-1">
                <nav class="secondary-nav">
                    <div class="nav-wrapper blue-grey darken-1">
                        <div class="col m7">
                            <ul class="left">
                                <li class="waves-effect waves-light hide-on-small-only">
                                    <a href="?page=tsk" class="judul">
                                        <i class="material-icons">drafts</i> Surat Keluar
                                    </a>
                                </li>
                                <li class="waves-effect waves-light">
                                    <a href="?page=tsk&act=add">
                                        <i class="material-icons md-24">add_circle</i> Tambah Data
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="col m5 hide-on-med-and-down">
                            <form method="post" action="?page=tsk">
                                <div class="input-field round-in-box">
                                    <input id="search" type="search" name="cari"
                                        placeholder="Ketik dan tekan enter mencari data..." required>
                                    <label for="search"><i class="material-icons md-dark">search</i></label>
                                    <input type="submit" name="submit" class="hidden">
                                </div>
                            </form>
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
 * Display success messages from session
 */
function displaySuccessMessages()
{
    $messages = [
        'succAdd' => 'Data berhasil ditambahkan',
        'succEdit' => 'Data berhasil diubah',
        'succDel' => 'Data berhasil dihapus'
    ];

    foreach ($messages as $sessionKey => $defaultMessage) {
        if (isset($_SESSION[$sessionKey])) {
            displayAlertMessage($_SESSION[$sessionKey], 'green');
            unset($_SESSION[$sessionKey]);
        }
    }
}

/**
 * Display alert message
 */
function displayAlertMessage($message, $color = 'green')
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
 * Handle search functionality
 */
function handleSearch($limit)
{
    global $config;

    $searchTerm = mysqli_real_escape_string($config, $_REQUEST['cari']);
    displaySearchHeader($searchTerm);

    $query = mysqli_query(
        $config,
        "SELECT * FROM tbl_surat_keluar 
         WHERE isi LIKE '%$searchTerm%' 
         ORDER BY id_surat DESC 
         LIMIT 15"
    );

    displaySuratKeluarTableContent($query, true);
}

/**
 * Display search results header
 */
function displaySearchHeader($searchTerm)
{
    echo '
    <div class="col s12" style="margin-top: -18px;">
        <div class="card blue lighten-5">
            <div class="card-content">
                <p class="description">
                    Hasil pencarian untuk kata kunci <strong>"' . htmlspecialchars(stripslashes($searchTerm)) . '"</strong>
                    <span class="right">
                        <a href="?page=tsk"><i class="material-icons md-36" style="color: #333;">clear</i></a>
                    </span>
                </p>
            </div>
        </div>
    </div>';
}

/**
 * Display surat keluar table
 */
function displaySuratKeluarTable($offset = 0, $limit = 10)
{
    global $config;

    $query = mysqli_query(
        $config,
        "SELECT * FROM tbl_surat_keluar 
         ORDER BY id_surat DESC 
         LIMIT $offset, $limit"
    );
    ?>

    <div class="row jarak-form">
        <div class="col m12" id="colres">
            <table class="bordered" id="tbl">
                <thead class="blue lighten-4" id="head">
                    <tr>
                        <th width="10%">No. Agenda<br />Kode</th>
                        <th width="31%">Isi Ringkas<br />File</th>
                        <th width="24%">Tujuan</th>
                        <th width="19%">No. Surat<br />Tgl Surat</th>
                        <th width="16%">
                            Tindakan
                            <span class="right tooltipped" data-position="left"
                                data-tooltip="Atur jumlah data yang ditampilkan">
                                <a class="modal-trigger" href="#modal">
                                    <i class="material-icons" style="color: #333;">settings</i>
                                </a>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php displaySuratKeluarTableContent($query, false); ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php displayPageLimitModal(); ?>
<?php
}

/**
 * Display table content for surat keluar
 */
function displaySuratKeluarTableContent($query, $isSearch = false)
{
    if (mysqli_num_rows($query) > 0) {
        while ($row = mysqli_fetch_array($query)) {
            displaySuratKeluarRow($row);
        }
    } else {
        $message = $isSearch ?
            'Tidak ada data yang ditemukan' :
            'Tidak ada data untuk ditampilkan. <u><a href="?page=tsk&act=add">Tambah data baru</a></u>';
        echo '<tr><td colspan="5"><center><p class="add">' . $message . '</p></center></td></tr>';
    }
}

/**
 * Display single surat keluar row
 */
function displaySuratKeluarRow($row)
{
    ?>
    <tr>
        <td>
            <?php echo htmlspecialchars($row['no_agenda']); ?><br />
            <hr /><?php echo htmlspecialchars($row['kode']); ?>
        </td>
        <td>
            <?php echo htmlspecialchars(substr($row['isi'], 0, 200)); ?><br /><br />
            <strong>File :</strong>
            <?php displayFileInfo($row); ?>
        </td>
        <td><?php echo htmlspecialchars($row['tujuan']); ?></td>
        <td>
            <?php echo htmlspecialchars($row['no_surat']); ?><br />
            <hr /><?php echo indoDate($row['tgl_surat']); ?>
        </td>
        <td>
            <?php displayActionButtons($row); ?>
        </td>
    </tr>
    <?php
}

/**
 * Display file information
 */
function displayFileInfo($row)
{
    if (!empty($row['file'])) {
        echo ' <strong><a href="?page=gsk&act=fsk&id_surat=' . $row['id_surat'] . '">' .
            htmlspecialchars($row['file']) . '</a></strong>';
    } else {
        echo ' <em>Tidak ada file yang diupload</em>';
    }
}

/**
 * Display action buttons based on user permissions
 */
function displayActionButtons($row)
{
    // Regular users can only view if they don't own the record
    if ($_SESSION['id_user'] != $row['id_user'] && $_SESSION['id_user'] != 1) {
        echo '<button class="btn small blue-grey waves-effect waves-light">
                <i class="material-icons">error</i> No Action
              </button>';
    } else {
        // Admin and record owner get full access
        echo '<a class="btn small blue waves-effect waves-light" href="?page=tsk&act=edit&id_surat=' . $row['id_surat'] . '">
                <i class="material-icons">edit</i> EDIT
              </a>
              <a class="btn small deep-orange waves-effect waves-light" href="?page=tsk&act=del&id_surat=' . $row['id_surat'] . '">
                <i class="material-icons">delete</i> DEL
              </a>';
    }
}

/**
 * Display page limit modal
 */
function displayPageLimitModal()
{
    global $config;

    $query = mysqli_query($config, "SELECT id_sett, surat_keluar FROM tbl_sett LIMIT 1");
    if ($query && list($id_sett, $current_limit) = mysqli_fetch_array($query)) {
        ?>
        <div id="modal" class="modal">
            <div class="modal-content white">
                <h5>Jumlah data yang ditampilkan per halaman</h5>
                <div class="row">
                    <form method="post" action="">
                        <div class="input-field col s12">
                            <input type="hidden" value="<?php echo $id_sett; ?>" name="id_sett">
                            <div class="input-field col s1" style="float: left;">
                                <i class="material-icons prefix md-prefix">looks_one</i>
                            </div>
                            <div class="input-field col s11 right" style="margin: -5px 0 20px;">
                                <select class="browser-default validate" name="surat_keluar" required>
                                    <option value="<?php echo $current_limit; ?>"><?php echo $current_limit; ?></option>
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <div class="modal-footer white">
                                <button type="submit" class="modal-action waves-effect waves-green btn-flat" name="simpan">
                                    Simpan
                                </button>
                                <?php handleLimitUpdate(); ?>
                                <a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat">
                                    Batal
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}

/**
 * Handle page limit update
 */
function handleLimitUpdate()
{
    if (isset($_REQUEST['simpan'])) {
        global $config;

        $id_sett = "1";
        $surat_keluar = (int) $_REQUEST['surat_keluar'];
        $id_user = $_SESSION['id_user'];

        $query = mysqli_query(
            $config,
            "UPDATE tbl_sett 
             SET surat_keluar='$surat_keluar', id_user='$id_user' 
             WHERE id_sett='$id_sett'"
        );

        if ($query) {
            header("Location: ./admin.php?page=tsk");
            exit();
        }
    }
}

/**
 * Display pagination
 */
function displayPagination($limit)
{
    global $config;

    $query = mysqli_query($config, "SELECT COUNT(*) as total FROM tbl_surat_keluar");
    $row = mysqli_fetch_assoc($query);
    $totalRecords = $row['total'];
    $totalPages = ceil($totalRecords / $limit);
    $currentPage = getCurrentPage();

    if ($totalRecords <= $limit) {
        return;
    }
    ?>

    <br />
    <!-- Pagination START -->
    <ul class="pagination">
        <?php displayPaginationLinks($currentPage, $totalPages); ?>
    </ul>
    <!-- Pagination END -->
    <?php
}

/**
 * Display pagination links
 */
function displayPaginationLinks($currentPage, $totalPages)
{
    // First and previous buttons
    if ($currentPage > 1) {
        $prev = $currentPage - 1;
        echo '<li><a href="?page=tsk&pg=1"><i class="material-icons md-48">first_page</i></a></li>
              <li><a href="?page=tsk&pg=' . $prev . '"><i class="material-icons md-48">chevron_left</i></a></li>';
    } else {
        echo '<li class="disabled"><a href="#"><i class="material-icons md-48">first_page</i></a></li>
              <li class="disabled"><a href="#"><i class="material-icons md-48">chevron_left</i></a></li>';
    }

    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        if (($i >= $currentPage - 3 && $i <= $currentPage + 3) || $i == 1 || $i == $totalPages) {
            $activeClass = $i == $currentPage ? 'active waves-effect waves-dark' : 'waves-effect waves-dark';
            echo '<li class="' . $activeClass . '"><a href="?page=tsk&pg=' . $i . '">' . $i . '</a></li>';
        }
    }

    // Next and last buttons
    if ($currentPage < $totalPages) {
        $next = $currentPage + 1;
        echo '<li><a href="?page=tsk&pg=' . $next . '"><i class="material-icons md-48">chevron_right</i></a></li>
              <li><a href="?page=tsk&pg=' . $totalPages . '"><i class="material-icons md-48">last_page</i></a></li>';
    } else {
        echo '<li class="disabled"><a href="#"><i class="material-icons md-48">chevron_right</i></a></li>
              <li class="disabled"><a href="#"><i class="material-icons md-48">last_page</i></a></li>';
    }
}
?>
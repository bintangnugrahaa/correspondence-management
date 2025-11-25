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
displaySuratMasukPage();

/**
 * Handle different actions
 */
function handleAction($action)
{
    $allowedActions = [
        'add' => 'tambah_surat_masuk.php',
        'edit' => 'edit_surat_masuk.php',
        'disp' => 'disposisi.php',
        'print' => 'cetak_disposisi.php',
        'del' => 'hapus_surat_masuk.php'
    ];

    if (isset($allowedActions[$action])) {
        include $allowedActions[$action];
    }
}

/**
 * Display main surat masuk page
 */
function displaySuratMasukPage()
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
        displaySuratMasukTable($offset, $limit);
        displayPagination($limit);
    }
}

/**
 * Get page limit from settings
 */
function getPageLimit()
{
    global $config;

    $query = mysqli_query($config, "SELECT surat_masuk FROM tbl_sett LIMIT 1");
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
                                        <a href="?page=tsm" class="judul">
                                            <i class="material-icons">mail</i> Surat Masuk
                                        </a>
                                    </li>
                                    <li class="waves-effect waves-light">
                                        <a href="?page=tsm&act=add">
                                            <i class="material-icons md-24">add_circle</i> Tambah Data
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="col m5 hide-on-med-and-down">
                                <form method="post" action="?page=tsm">
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
            displayAlertMessage($_SESSION[$sessionKey]);
            unset($_SESSION[$sessionKey]);
        }
    }
}

/**
 * Display alert message
 */
function displayAlertMessage($message)
{
    echo '
    <div id="alert-message" class="row">
        <div class="col m12">
            <div class="card green lighten-5">
                <div class="card-content notif">
                    <span class="card-title green-text">
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
        "SELECT * FROM tbl_surat_masuk 
         WHERE isi LIKE '%$searchTerm%' 
         ORDER BY id_surat DESC 
         LIMIT 15"
    );

    displaySuratMasukTableContent($query, true);
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
                        <a href="?page=tsm"><i class="material-icons md-36" style="color: #333;">clear</i></a>
                    </span>
                </p>
            </div>
        </div>
    </div>';
}

/**
 * Display surat masuk table
 */
function displaySuratMasukTable($offset = 0, $limit = 10)
{
    global $config;

    $query = mysqli_query(
        $config,
        "SELECT * FROM tbl_surat_masuk 
         ORDER BY id_surat DESC 
         LIMIT $offset, $limit"
    );
    ?>
    
        <div class="row jarak-form">
            <div class="col m12" id="colres">
                <table class="bordered" id="tbl">
                    <thead class="blue lighten-4" id="head">
                        <tr>
                            <th width="10%">No. Agenda<br/>Kode</th>
                            <th width="30%">Isi Ringkas<br/>File</th>
                            <th width="24%">Asal Surat</th>
                            <th width="18%">No. Surat<br/>Tgl Surat</th>
                            <th width="18%">
                                Tindakan 
                                <span class="right tooltipped" data-position="left" data-tooltip="Atur jumlah data yang ditampilkan">
                                    <a class="modal-trigger" href="#modal">
                                        <i class="material-icons" style="color: #333;">settings</i>
                                    </a>
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php displaySuratMasukTableContent($query, false); ?>
                    </tbody>
                </table>
            </div>
        </div>
    
        <?php displayPageLimitModal(); ?>
        <?php
}

/**
 * Display table content for surat masuk
 */
function displaySuratMasukTableContent($query, $isSearch = false)
{
    if (mysqli_num_rows($query) > 0) {
        while ($row = mysqli_fetch_array($query)) {
            displaySuratMasukRow($row);
        }
    } else {
        $message = $isSearch ?
            'Tidak ada data yang ditemukan' :
            'Tidak ada data untuk ditampilkan. <u><a href="?page=tsm&act=add">Tambah data baru</a></u>';
        echo '<tr><td colspan="5"><center><p class="add">' . $message . '</p></center></td></tr>';
    }
}

/**
 * Display single surat masuk row
 */
function displaySuratMasukRow($row)
{
    ?>
        <tr>
            <td><?php echo htmlspecialchars($row['no_agenda']); ?><br/><hr/><?php echo htmlspecialchars($row['kode']); ?></td>
            <td>
                <?php echo htmlspecialchars(substr($row['isi'], 0, 200)); ?><br/><br/>
                <strong>File :</strong>
                <?php displayFileInfo($row); ?>
            </td>
            <td><?php echo htmlspecialchars($row['asal_surat']); ?></td>
            <td>
                <?php echo htmlspecialchars($row['no_surat']); ?><br/><hr/>
                <?php echo indoDate($row['tgl_surat']); ?>
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
        echo ' <strong><a href="?page=gsm&act=fsm&id_surat=' . $row['id_surat'] . '">' .
            htmlspecialchars($row['file']) . '</a></strong>';
    } else {
        echo '<em>Tidak ada file yang di upload</em>';
    }
}

/**
 * Display action buttons based on user permissions
 */
function displayActionButtons($row)
{
    // Regular users can only print if they don't own the record
    if ($_SESSION['id_user'] != $row['id_user'] && $_SESSION['id_user'] != 1) {
        echo '<a class="btn small yellow darken-3 waves-effect waves-light" href="?page=ctk&id_surat=' . $row['id_surat'] . '" target="_blank">
                <i class="material-icons">print</i> PRINT</a>';
    } else {
        // Admin and record owner get full access
        echo '<a class="btn small blue waves-effect waves-light" href="?page=tsm&act=edit&id_surat=' . $row['id_surat'] . '">
                <i class="material-icons">edit</i> EDIT</a>
              <a class="btn small light-green waves-effect waves-light tooltipped" data-position="left" data-tooltip="Pilih Disp untuk menambahkan Disposisi Surat" href="?page=tsm&act=disp&id_surat=' . $row['id_surat'] . '">
                <i class="material-icons">description</i> DISP</a>
              <a class="btn small yellow darken-3 waves-effect waves-light" href="?page=ctk&id_surat=' . $row['id_surat'] . '" target="_blank">
                <i class="material-icons">print</i> PRINT</a>
              <a class="btn small deep-orange waves-effect waves-light" href="?page=tsm&act=del&id_surat=' . $row['id_surat'] . '">
                <i class="material-icons">delete</i> DEL</a>';
    }
}

/**
 * Display page limit modal
 */
function displayPageLimitModal()
{
    global $config;

    $query = mysqli_query($config, "SELECT id_sett, surat_masuk FROM tbl_sett LIMIT 1");
    list($id_sett, $current_limit) = mysqli_fetch_array($query);
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
                                <select class="browser-default validate" name="surat_masuk" required>
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

/**
 * Handle page limit update
 */
function handleLimitUpdate()
{
    if (isset($_REQUEST['simpan'])) {
        global $config;

        $id_sett = "1";
        $surat_masuk = (int) $_REQUEST['surat_masuk'];
        $id_user = $_SESSION['id_user'];

        $query = mysqli_query(
            $config,
            "UPDATE tbl_sett 
             SET surat_masuk='$surat_masuk', id_user='$id_user' 
             WHERE id_sett='$id_sett'"
        );

        if ($query) {
            header("Location: ./admin.php?page=tsm");
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

    $query = mysqli_query($config, "SELECT COUNT(*) as total FROM tbl_surat_masuk");
    $row = mysqli_fetch_assoc($query);
    $totalRecords = $row['total'];
    $totalPages = ceil($totalRecords / $limit);
    $currentPage = getCurrentPage();

    if ($totalRecords <= $limit) {
        return;
    }
    ?>
    
        <br/>
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
        echo '<li><a href="?page=tsm&pg=1"><i class="material-icons md-48">first_page</i></a></li>
              <li><a href="?page=tsm&pg=' . $prev . '"><i class="material-icons md-48">chevron_left</i></a></li>';
    } else {
        echo '<li class="disabled"><a href="#"><i class="material-icons md-48">first_page</i></a></li>
              <li class="disabled"><a href="#"><i class="material-icons md-48">chevron_left</i></a></li>';
    }

    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        if (($i >= $currentPage - 3 && $i <= $currentPage + 3) || $i == 1 || $i == $totalPages) {
            $activeClass = $i == $currentPage ? 'active waves-effect waves-dark' : 'waves-effect waves-dark';
            echo '<li class="' . $activeClass . '"><a href="?page=tsm&pg=' . $i . '">' . $i . '</a></li>';
        }
    }

    // Next and last buttons
    if ($currentPage < $totalPages) {
        $next = $currentPage + 1;
        echo '<li><a href="?page=tsm&pg=' . $next . '"><i class="material-icons md-48">chevron_right</i></a></li>
              <li><a href="?page=tsm&pg=' . $totalPages . '"><i class="material-icons md-48">last_page</i></a></li>';
    } else {
        echo '<li class="disabled"><a href="#"><i class="material-icons md-48">chevron_right</i></a></li>
              <li class="disabled"><a href="#"><i class="material-icons md-48">last_page</i></a></li>';
    }
}
?>
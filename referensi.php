<?php
// Check session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

// Handle actions
if (isset($_REQUEST['act'])) {
    $actionFiles = [
        'add' => 'tambah_klasifikasi.php',
        'edit' => 'edit_klasifikasi.php',
        'del' => 'hapus_klasifikasi.php',
        'imp' => 'upload_referensi.php'
    ];

    if (isset($actionFiles[$_REQUEST['act']])) {
        include $actionFiles[$_REQUEST['act']];
        exit();
    }
}

// Main page logic
displayKlasifikasiPage();

/**
 * Display the main klasifikasi page
 */
function displayKlasifikasiPage()
{
    global $config;

    $settings = getSettings();
    $referensi = $settings['referensi'];

    // Handle pagination
    $currentPage = max(1, (int) ($_GET['pg'] ?? 1));
    $offset = ($currentPage - 1) * $referensi;

    echo renderHeader();
    displayAlertMessages();
    echo '<div class="row jarak-form">';

    if (isset($_REQUEST['submit']) && !empty($_REQUEST['cari'])) {
        displaySearchResults($_REQUEST['cari']);
    } else {
        displayAllData($referensi, $offset);
        displayPagination($referensi);
    }

    echo '</div>';
}

/**
 * Get settings from database
 */
function getSettings()
{
    global $config;
    $query = mysqli_query($config, "SELECT referensi FROM tbl_sett LIMIT 1");
    return mysqli_fetch_assoc($query) ?? ['referensi' => 10];
}

/**
 * Render page header
 */
function renderHeader()
{
    $isAdmin = in_array($_SESSION['admin'], [1, 2]);

    return '<!-- Row Start -->
            <div class="row">
                <!-- Secondary Nav START -->
                <div class="col s12">
                    <div class="z-depth-1">
                        <nav class="secondary-nav">
                            <div class="nav-wrapper blue-grey darken-1">
                                <div class="col m7">
                                    <ul class="left">
                                        <li class="waves-effect waves-light hide-on-small-only">
                                            <a href="?page=ref" class="judul">
                                                <i class="material-icons">class</i> Klasifikasi Surat
                                            </a>
                                        </li>'
        . ($isAdmin ?
            '<li class="waves-effect waves-light">
                                            <a href="?page=ref&act=add">
                                                <i class="material-icons md-24">add_circle</i> Tambah Data
                                            </a>
                                        </li>
                                        <li class="waves-effect waves-light">
                                            <a href="?page=ref&act=imp">
                                                <i class="material-icons md-24">file_upload</i> Import Data
                                            </a>
                                        </li>' : '') . '
                                    </ul>
                                </div>
                                <div class="col m5 hide-on-med-and-down">
                                    ' . renderSearchForm() . '
                                </div>
                            </div>
                        </nav>
                    </div>
                </div>
                <!-- Secondary Nav END -->
            </div>
            <!-- Row END -->';
}

/**
 * Render search form
 */
function renderSearchForm()
{
    return '<form method="post" action="?page=ref">
                <div class="input-field round-in-box">
                    <input id="search" type="search" name="cari" 
                           placeholder="Ketik dan tekan enter mencari data..." required>
                    <label for="search"><i class="material-icons">search</i></label>
                    <input type="submit" name="submit" class="hidden">
                </div>
            </form>';
}

/**
 * Display alert messages
 */
function displayAlertMessages()
{
    $alerts = [
        'succAdd' => 'Data berhasil ditambahkan!',
        'succEdit' => 'Data berhasil diubah!',
        'succDel' => 'Data berhasil dihapus!',
        'succUpload' => 'Data berhasil diupload!'
    ];

    foreach ($alerts as $sessionKey => $defaultMessage) {
        if (isset($_SESSION[$sessionKey])) {
            echo '<div id="alert-message" class="row">
                    <div class="col m12">
                        <div class="card green lighten-5">
                            <div class="card-content notif">
                                <span class="card-title green-text">
                                    <i class="material-icons md-36">done</i> '
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
 * Display search results
 */
function displaySearchResults($searchTerm)
{
    global $config;

    $safeSearchTerm = mysqli_real_escape_string($config, $searchTerm);

    echo '<div class="col s12" style="margin-top: -18px;">
            <div class="card blue lighten-5">
                <div class="card-content">
                    <p class="description">Hasil pencarian untuk kata kunci 
                       <strong>"' . htmlspecialchars(stripslashes($searchTerm)) . '"</strong>
                       <span class="right">
                           <a href="?page=ref">
                               <i class="material-icons md-36" style="color: #333;">clear</i>
                           </a>
                       </span>
                    </p>
                </div>
            </div>
        </div>';

    displayDataTable("SELECT * FROM tbl_klasifikasi 
                     WHERE uraian LIKE '%$safeSearchTerm%' 
                     ORDER BY id_klasifikasi DESC LIMIT 15", true);
}

/**
 * Display all data with pagination
 */
function displayAllData($limit, $offset)
{
    displayDataTable("SELECT * FROM tbl_klasifikasi 
                     ORDER BY id_klasifikasi DESC 
                     LIMIT $offset, $limit", false);
}

/**
 * Display data table
 */
function displayDataTable($query, $isSearch = false)
{
    global $config;

    $result = mysqli_query($config, $query);
    $hasData = mysqli_num_rows($result) > 0;

    echo '<div class="col m12" id="colres">
            <table class="bordered" id="tbl">
                <thead class="blue lighten-4" id="head">
                    <tr>
                        <th width="10%">Kode</th>
                        <th width="30%">Nama</th>
                        <th width="42%">Uraian</th>
                        <th width="18%">Tindakan '
        . (!$isSearch ? renderSettingsButton() : '') . '
                        </th>
                    </tr>
                </thead>
                <tbody>';

    if ($hasData) {
        while ($row = mysqli_fetch_assoc($result)) {
            displayTableRow($row);
        }
    } else {
        $message = $isSearch ?
            'Tidak ada data yang ditemukan' :
            'Tidak ada data yang ditemukan. <u><a href="?page=ref&act=add">Tambah data baru</a></u>';

        echo '<tr><td colspan="4"><center><p class="add">' . $message . '</p></center></td></tr>';
    }

    echo '</tbody></table><br/><br/></div>';
}

/**
 * Render settings button
 */
function renderSettingsButton()
{
    return '<span class="right tooltipped" data-position="left" 
                  data-tooltip="Atur jumlah data yang ditampilkan">
                <a class="modal-trigger" href="#modal">
                    <i class="material-icons" style="color: #333;">settings</i>
                </a>
            </span>' . renderSettingsModal();
}

/**
 * Render settings modal
 */
function renderSettingsModal()
{
    global $config;

    $settings = getSettings();
    $id_sett = 1; // Assuming fixed ID for settings

    return '<div id="modal" class="modal">
                <div class="modal-content white">
                    <h5>Jumlah data yang ditampilkan per halaman</h5>
                    <div class="row">
                        <form method="post" action="">
                            <div class="input-field col s12">
                                <input type="hidden" value="' . $id_sett . '" name="id_sett">
                                <div class="input-field col s1" style="float: left;">
                                    <i class="material-icons prefix md-prefix">looks_one</i>
                                </div>
                                <div class="input-field col s11 right" style="margin: -5px 0 20px;">
                                    <select class="browser-default validate" name="referensi" required>
                                        ' . generateOptions($settings['referensi']) . '
                                    </select>
                                </div>
                                <div class="modal-footer white">
                                    <button type="submit" class="modal-action waves-effect waves-green btn-flat" 
                                            name="simpan">Simpan</button>
                                    <a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat">
                                        Batal
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>';
}

/**
 * Generate options for select dropdown
 */
function generateOptions($currentValue)
{
    $options = [5, 10, 20, 50, 100];
    $html = '<option value="' . $currentValue . '">' . $currentValue . '</option>';

    foreach ($options as $option) {
        if ($option != $currentValue) {
            $html .= '<option value="' . $option . '">' . $option . '</option>';
        }
    }

    return $html;
}

/**
 * Handle settings update
 */
function handleSettingsUpdate()
{
    if (isset($_REQUEST['simpan'])) {
        global $config;

        $id_sett = "1";
        $referensi = (int) $_REQUEST['referensi'];
        $id_user = $_SESSION['id_user'];

        $query = mysqli_query($config, "UPDATE tbl_sett 
                                       SET referensi='$referensi', id_user='$id_user' 
                                       WHERE id_sett='$id_sett'");
        if ($query) {
            header("Location: ./admin.php?page=ref");
            exit();
        }
    }
}

// Handle settings update if requested
handleSettingsUpdate();

/**
 * Display table row
 */
function displayTableRow($row)
{
    $isAdmin = in_array($_SESSION['admin'], [1, 2]);

    echo '<tr>
            <td>' . htmlspecialchars($row['kode']) . '</td>
            <td>' . htmlspecialchars($row['nama']) . '</td>
            <td>' . htmlspecialchars($row['uraian']) . '</td>
            <td>';

    if (!$isAdmin) {
        echo '<a class="btn small blue-grey waves-effect waves-light">
                <i class="material-icons">error</i> NO ACTION
              </a>';
    } else {
        echo '<a class="btn small blue waves-effect waves-light" 
                 href="?page=ref&act=edit&id_klasifikasi=' . $row['id_klasifikasi'] . '">
                <i class="material-icons">edit</i> EDIT
              </a>
              <a class="btn small deep-orange waves-effect waves-light" 
                 href="?page=ref&act=del&id_klasifikasi=' . $row['id_klasifikasi'] . '">
                <i class="material-icons">delete</i> DEL
              </a>';
    }

    echo '</td></tr>';
}

/**
 * Display pagination
 */
function displayPagination($limit)
{
    global $config;

    $countQuery = mysqli_query($config, "SELECT COUNT(*) as total FROM tbl_klasifikasi");
    $totalData = mysqli_fetch_assoc($countQuery)['total'];
    $totalPages = ceil($totalData / $limit);

    if ($totalData <= $limit) {
        return;
    }

    $currentPage = max(1, (int) ($_GET['pg'] ?? 1));

    echo '<!-- Pagination START -->
          <ul class="pagination">'
        . renderPaginationLinks($currentPage, $totalPages) . '
          </ul>';
}

/**
 * Render pagination links
 */
function renderPaginationLinks($currentPage, $totalPages)
{
    $html = '';

    // First and previous buttons
    if ($currentPage > 1) {
        $html .= '<li><a href="?page=ref&pg=1"><i class="material-icons md-48">first_page</i></a></li>
                  <li><a href="?page=ref&pg=' . ($currentPage - 1) . '"><i class="material-icons md-48">chevron_left</i></a></li>';
    } else {
        $html .= '<li class="disabled"><a href="#"><i class="material-icons md-48">first_page</i></a></li>
                  <li class="disabled"><a href="#"><i class="material-icons md-48">chevron_left</i></a></li>';
    }

    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        if (($i >= $currentPage - 3 && $i <= $currentPage + 3) || $i == 1 || $i == $totalPages) {
            $activeClass = $i == $currentPage ? 'active waves-effect waves-dark' : 'waves-effect waves-dark';
            $html .= '<li class="' . $activeClass . '"><a href="?page=ref&pg=' . $i . '">' . $i . '</a></li>';
        }
    }

    // Next and last buttons
    if ($currentPage < $totalPages) {
        $html .= '<li><a href="?page=ref&pg=' . ($currentPage + 1) . '"><i class="material-icons md-48">chevron_right</i></a></li>
                  <li><a href="?page=ref&pg=' . $totalPages . '"><i class="material-icons md-48">last_page</i></a></li>';
    } else {
        $html .= '<li class="disabled"><a href="#"><i class="material-icons md-48">chevron_right</i></a></li>
                  <li class="disabled"><a href="#"><i class="material-icons md-48">last_page</i></a></li>';
    }

    return $html;
}
?>
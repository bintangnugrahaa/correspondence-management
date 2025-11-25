<?php
// Check session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

if (isset($_REQUEST['act'])) {
    handleAction($_REQUEST['act']);
} else {
    displayGaleriPage();
}

/**
 * Handle actions
 */
function handleAction($action)
{
    if ($action === 'fsk') {
        include "file_sk.php";
        exit();
    }
}

/**
 * Display galeri page
 */
function displayGaleriPage()
{
    displayNavigationHeader();

    if (isset($_REQUEST['submit'])) {
        displayFilteredResults();
    } else {
        displayDefaultResults();
    }
}

/**
 * Display navigation header
 */
function displayNavigationHeader()
{
    echo '
    <!-- Row Start -->
    <div class="row">
        <!-- Secondary Nav START -->
        <div class="col s12">
            <div class="z-depth-1">
                <nav class="secondary-nav">
                    <div class="nav-wrapper blue-grey darken-1">
                        <div class="col m12">
                            <ul class="left">
                                <li class="waves-effect waves-light">
                                    <a href="?page=gsk" class="judul">
                                        <i class="material-icons">image</i> Galeri File Surat Keluar
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
    <!-- Row END -->';
}

/**
 * Display filtered results based on date range
 */
function displayFilteredResults()
{
    global $config;

    $dari_tanggal = $_REQUEST['dari_tanggal'];
    $sampai_tanggal = $_REQUEST['sampai_tanggal'];

    // Validate date range
    if (empty($dari_tanggal) || empty($sampai_tanggal)) {
        header("Location: ./admin.php?page=gsk");
        exit();
    }

    displayDateFilterForm($dari_tanggal, $sampai_tanggal, true);
    displayFilterHeader($dari_tanggal, $sampai_tanggal);
    displaySuratFiles($dari_tanggal, $sampai_tanggal, true);
}

/**
 * Display default results with pagination
 */
function displayDefaultResults()
{
    global $config;

    $limit = 8;
    $currentPage = getCurrentPage();
    $offset = ($currentPage - 1) * $limit;

    displayDateFilterForm();
    displaySuratFiles(null, null, false, $offset, $limit);
    displayPagination($limit);
}

/**
 * Get current page number
 */
function getCurrentPage()
{
    return isset($_GET['pg']) ? max(1, (int) $_GET['pg']) : 1;
}

/**
 * Display date filter form
 */
function displayDateFilterForm($dari_tanggal = '', $sampai_tanggal = '', $isFiltered = false)
{
    $resetButton = $isFiltered ? '
        <button type="reset" onclick="window.history.back()" class="btn-large deep-orange waves-effect waves-light">
            RESET <i class="material-icons">refresh</i>
        </button>' : '';

    echo '
    <!-- Row form Start -->
    <div class="row jarak-form black-text">
        <form class="col s12" method="post" action="">
            <div class="input-field col s3">
                <i class="material-icons prefix md-prefix">date_range</i>
                <input id="dari_tanggal" type="text" name="dari_tanggal" class="datepicker" value="' . htmlspecialchars($dari_tanggal) . '" required>
                <label for="dari_tanggal">Dari Tanggal</label>
            </div>
            <div class="input-field col s3">
                <i class="material-icons prefix md-prefix">date_range</i>
                <input id="sampai_tanggal" type="text" name="sampai_tanggal" class="datepicker" value="' . htmlspecialchars($sampai_tanggal) . '" required>
                <label for="sampai_tanggal">Sampai Tanggal</label>
            </div>
            <div class="col s6">
                <button type="submit" name="submit" class="btn-large blue waves-effect waves-light">
                    FILTER <i class="material-icons">filter_list</i>
                </button>
                ' . $resetButton . '
            </div>
        </form>
    </div>
    <!-- Row form END -->';
}

/**
 * Display filter header
 */
function displayFilterHeader($dari_tanggal, $sampai_tanggal)
{
    echo '
    <div class="row agenda">
        <div class="col s12">
            <p class="warna agenda">
                Galeri file surat keluar antara tanggal 
                <strong>' . indoDate($dari_tanggal) . '</strong> 
                sampai dengan tanggal 
                <strong>' . indoDate($sampai_tanggal) . '</strong>
            </p>
        </div>
    </div>';
}

/**
 * Display surat files
 */
function displaySuratFiles($dari_tanggal = null, $sampai_tanggal = null, $isFiltered = false, $offset = 0, $limit = 8)
{
    global $config;

    $query = getSuratKeluarQuery($dari_tanggal, $sampai_tanggal, $isFiltered, $offset, $limit);

    if (!$query || mysqli_num_rows($query) == 0) {
        displayNoFilesMessage($isFiltered);
        return;
    }

    echo '<div class="row jarak-form">';

    while ($row = mysqli_fetch_assoc($query)) {
        if (!empty($row['file'])) {
            displayFileCard($row);
        }
    }

    echo '</div>';
}

/**
 * Get surat keluar query based on parameters
 */
function getSuratKeluarQuery($dari_tanggal, $sampai_tanggal, $isFiltered, $offset, $limit)
{
    global $config;

    $sql = "SELECT * FROM tbl_surat_keluar WHERE file != ''";

    if ($isFiltered && $dari_tanggal && $sampai_tanggal) {
        $dari_tanggal = mysqli_real_escape_string($config, $dari_tanggal);
        $sampai_tanggal = mysqli_real_escape_string($config, $sampai_tanggal);
        $sql .= " AND tgl_catat BETWEEN '$dari_tanggal' AND '$sampai_tanggal' 
                 ORDER BY id_surat DESC 
                 LIMIT 10";
    } else {
        $sql .= " ORDER BY id_surat DESC 
                 LIMIT $offset, $limit";
    }

    return mysqli_query($config, $sql);
}

/**
 * Display no files message
 */
function displayNoFilesMessage($isFiltered)
{
    $message = $isFiltered ?
        'Tidak ada file lampiran surat keluar yang ditemukan' :
        'Tidak ada data untuk ditampilkan';

    echo '
    <div class="col m12">
        <div class="card blue lighten-5">
            <div class="card-content notif">
                <span class="card-title lampiran">
                    <center>' . $message . '</center>
                </span>
            </div>
        </div>
    </div>';
}

/**
 * Display file card
 */
function displayFileCard($row)
{
    $fileInfo = getFileInfo($row['file']);
    $imageSrc = getImageSource($fileInfo['extension'], $row['file']);
    $buttonText = getButtonText($fileInfo['extension']);

    echo '
    <div class="col m3">
        <img class="galeri materialboxed" 
             data-caption="' . indoDate($row['tgl_catat']) . '" 
             src="' . $imageSrc . '"/>
        <a class="btn light-green darken-1" 
           href="?page=gsk&act=fsk&id_surat=' . $row['id_surat'] . '">
           ' . $buttonText . '
        </a>
    </div>';
}

/**
 * Get file information
 */
function getFileInfo($filename)
{
    $fileParts = explode('.', $filename);
    return [
        'extension' => strtolower(end($fileParts))
    ];
}

/**
 * Get image source based on file type
 */
function getImageSource($extension, $filename)
{
    $imageExtensions = ['jpg', 'png', 'jpeg'];
    $documentExtensions = ['doc', 'docx'];

    if (in_array($extension, $imageExtensions)) {
        return './upload/surat_keluar/' . htmlspecialchars($filename);
    } elseif (in_array($extension, $documentExtensions)) {
        return './asset/img/word.png';
    } else {
        return './asset/img/pdf.png';
    }
}

/**
 * Get button text based on file type
 */
function getButtonText($extension)
{
    $imageExtensions = ['jpg', 'png', 'jpeg'];

    if (in_array($extension, $imageExtensions)) {
        return 'Tampilkan Ukuran Penuh';
    } else {
        return 'Lihat Detail File';
    }
}

/**
 * Display pagination
 */
function displayPagination($limit)
{
    global $config;

    $query = mysqli_query($config, "SELECT COUNT(*) as total FROM tbl_surat_keluar WHERE file != ''");
    $row = mysqli_fetch_assoc($query);
    $totalRecords = $row['total'];
    $totalPages = ceil($totalRecords / $limit);
    $currentPage = getCurrentPage();

    if ($totalRecords <= $limit) {
        return;
    }

    echo '<!-- Pagination START -->
          <ul class="pagination">';

    displayPaginationLinks($currentPage, $totalPages);

    echo '</ul>
          <!-- Pagination END -->';
}

/**
 * Display pagination links
 */
function displayPaginationLinks($currentPage, $totalPages)
{
    // First and previous buttons
    if ($currentPage > 1) {
        $prev = $currentPage - 1;
        echo '<li><a href="?page=gsk&pg=1"><i class="material-icons md-48">first_page</i></a></li>
              <li><a href="?page=gsk&pg=' . $prev . '"><i class="material-icons md-48">chevron_left</i></a></li>';
    } else {
        echo '<li class="disabled"><a href=""><i class="material-icons md-48">first_page</i></a></li>
              <li class="disabled"><a href=""><i class="material-icons md-48">chevron_left</i></a></li>';
    }

    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        if (($i >= $currentPage - 3 && $i <= $currentPage + 3) || $i == 1 || $i == $totalPages) {
            $activeClass = $i == $currentPage ? 'active waves-effect waves-dark' : 'waves-effect waves-dark';
            echo '<li class="' . $activeClass . '"><a href="?page=gsk&pg=' . $i . '">' . $i . '</a></li>';
        }
    }

    // Next and last buttons
    if ($currentPage < $totalPages) {
        $next = $currentPage + 1;
        echo '<li><a href="?page=gsk&pg=' . $next . '"><i class="material-icons md-48">chevron_right</i></a></li>
              <li><a href="?page=gsk&pg=' . $totalPages . '"><i class="material-icons md-48">last_page</i></a></li>';
    } else {
        echo '<li class="disabled"><a href=""><i class="material-icons md-48">chevron_right</i></a></li>
              <li class="disabled"><a href=""><i class="material-icons md-48">last_page</i></a></li>';
    }
}
?>
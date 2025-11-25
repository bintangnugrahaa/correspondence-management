<?php
// Check session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

// Handle actions
if (isset($_REQUEST['act'])) {
    handleUserAction($_REQUEST['act']);
    exit();
}

// Display user management page
displayUserManagementPage();

/**
 * Handle user actions
 */
function handleUserAction($action)
{
    $actionFiles = [
        'add' => 'tambah_user.php',
        'edit' => 'edit_tipe_user.php',
        'del' => 'hapus_user.php'
    ];

    if (isset($actionFiles[$action])) {
        include $actionFiles[$action];
    }
}

/**
 * Display user management page
 */
function displayUserManagementPage()
{
    global $config;

    $limit = 5;
    $currentPage = getCurrentPage();
    $offset = ($currentPage - 1) * $limit;

    echo user_renderHeader();
    user_displayAlertMessages();
    echo renderUserTable($limit, $offset);
    displayPagination($limit);
}

/**
 * Get current page number
 */
function getCurrentPage()
{
    return max(1, (int) ($_GET['pg'] ?? 1));
}

/**
 * Render page header
 */
function user_renderHeader()
{
    return '<!-- Row Start -->
            <div class="row">
                <!-- Secondary Nav START -->
                <div class="col s12">
                    <div class="z-depth-1">
                        <nav class="secondary-nav">
                            <div class="nav-wrapper blue-grey darken-1">
                                <div class="col m12">
                                    <ul class="left">
                                        <li class="waves-effect waves-light hide-on-small-only">
                                            <a href="?page=sett&sub=usr" class="judul">
                                                <i class="material-icons">people</i> Manajemen User
                                            </a>
                                        </li>
                                        <li class="waves-effect waves-light">
                                            <a href="?page=sett&sub=usr&act=add">
                                                <i class="material-icons md-24">person_add</i> Tambah User
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
 * Display alert messages
 */
function user_displayAlertMessages()
{
    $alertTypes = [
        'succAdd' => 'Data berhasil ditambahkan!',
        'succEdit' => 'Data berhasil diubah!',
        'succDel' => 'Data berhasil dihapus!'
    ];

    foreach ($alertTypes as $sessionKey => $defaultMessage) {
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
 * Render user table
 */
function renderUserTable($limit, $offset)
{
    global $config;

    $query = mysqli_query($config, "SELECT * FROM tbl_user LIMIT $offset, $limit");
    $hasData = mysqli_num_rows($query) > 0;

    return '<!-- Row form Start -->
            <div class="row jarak-form">
                <div class="col m12" id="colres">
                    <!-- Table START -->
                    <table class="bordered" id="tbl">
                        <thead class="blue lighten-4" id="head">
                            <tr>
                                <th width="8%">No</th>
                                <th width="23%">Username</th>
                                <th width="30%">Nama<br/>NIP</th>
                                <th width="22%">Level</th>
                                <th width="16%">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            ' . ($hasData ? renderUserRows($query) : renderNoDataRow()) . '
                        </tbody>
                    </table>
                    <!-- Table END -->
                </div>
            </div>
            <!-- Row form END -->';
}

/**
 * Render user rows
 */
function renderUserRows($query)
{
    $html = '';
    $no = 1;

    while ($row = mysqli_fetch_assoc($query)) {
        $html .= '<tr>
                    <td>' . $no++ . '</td>
                    <td>' . htmlspecialchars($row['username']) . '</td>
                    <td>' . htmlspecialchars($row['nama']) . '<br/>' . htmlspecialchars($row['nip']) . '</td>
                    <td>' . getUserLevel($row['admin']) . '</td>
                    <td>' . renderActionButtons($row) . '</td>
                </tr>';
    }

    return $html;
}

/**
 * Get user level description
 */
function getUserLevel($adminLevel)
{
    $levels = [
        1 => 'Super Admin',
        2 => 'Administrator',
        0 => 'User Biasa'
    ];

    return $levels[$adminLevel] ?? 'User Biasa';
}

/**
 * Render action buttons for user
 */
function renderActionButtons($user)
{
    // Current user cannot edit themselves
    if ($_SESSION['username'] == $user['username']) {
        return '<button class="btn small blue-grey waves-effect waves-light">
                    <i class="material-icons">error</i> No Action
                </button>';
    }

    // Cannot edit super admin (id_user = 1)
    if ($user['id_user'] == 1) {
        return '<button class="btn small blue-grey waves-effect waves-light">
                    <i class="material-icons">error</i> No Action
                </button>';
    }

    return '<a class="btn small blue waves-effect waves-light" 
               href="?page=sett&sub=usr&act=edit&id_user=' . $user['id_user'] . '">
                <i class="material-icons">edit</i> EDIT
            </a>
            <a class="btn small deep-orange waves-effect waves-light" 
               href="?page=sett&sub=usr&act=del&id_user=' . $user['id_user'] . '">
                <i class="material-icons">delete</i> DEL
            </a>';
}

/**
 * Render no data row
 */
function renderNoDataRow()
{
    return '<tr>
                <td colspan="5">
                    <center>
                        <p class="add">Tidak ada data untuk ditampilkan</p>
                    </center>
                </td>
            </tr>';
}

/**
 * Display pagination
 */
function displayPagination($limit)
{
    global $config;

    $countQuery = mysqli_query($config, "SELECT COUNT(*) as total FROM tbl_user");
    $totalData = mysqli_fetch_assoc($countQuery)['total'];
    $totalPages = ceil($totalData / $limit);

    if ($totalData <= $limit) {
        return;
    }

    $currentPage = getCurrentPage();

    echo '<!-- Pagination START -->
          <ul class="pagination">'
        . renderPaginationLinks($currentPage, $totalPages) . '
          </ul>
          <!-- Pagination END -->';
}

/**
 * Render pagination links
 */
function renderPaginationLinks($currentPage, $totalPages)
{
    $html = '';

    // First and previous buttons
    if ($currentPage > 1) {
        $prev = $currentPage - 1;
        $html .= '<li>
                    <a href="?page=sett&sub=usr&pg=1">
                        <i class="material-icons md-48">first_page</i>
                    </a>
                  </li>
                  <li>
                    <a href="?page=sett&sub=usr&pg=' . $prev . '">
                        <i class="material-icons md-48">chevron_left</i>
                    </a>
                  </li>';
    } else {
        $html .= '<li class="disabled">
                    <a href="#">
                        <i class="material-icons md-48">first_page</i>
                    </a>
                  </li>
                  <li class="disabled">
                    <a href="#">
                        <i class="material-icons md-48">chevron_left</i>
                    </a>
                  </li>';
    }

    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $activeClass = $i == $currentPage ? 'active waves-effect waves-dark' : 'waves-effect waves-dark';
        $html .= '<li class="' . $activeClass . '">
                    <a href="?page=sett&sub=usr&pg=' . $i . '">' . $i . '</a>
                  </li>';
    }

    // Next and last buttons
    if ($currentPage < $totalPages) {
        $next = $currentPage + 1;
        $html .= '<li>
                    <a href="?page=sett&sub=usr&pg=' . $next . '">
                        <i class="material-icons md-48">chevron_right</i>
                    </a>
                  </li>
                  <li>
                    <a href="?page=sett&sub=usr&pg=' . $totalPages . '">
                        <i class="material-icons md-48">last_page</i>
                    </a>
                  </li>';
    } else {
        $html .= '<li class="disabled">
                    <a href="#">
                        <i class="material-icons md-48">chevron_right</i>
                    </a>
                  </li>
                  <li class="disabled">
                    <a href="#">
                        <i class="material-icons md-48">last_page</i>
                    </a>
                  </li>';
    }

    return $html;
}
?>
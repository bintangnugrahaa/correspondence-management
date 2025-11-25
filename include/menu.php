<?php
// Check session
if (empty($_SESSION['admin'])) {
    header("Location: ../");
    exit();
}
?>

<nav class="blue-grey darken-5">
    <div class="nav-wrapper">
        <a href="./" class="brand-logo center hide-on-large-only">
            <i class="material-icons md-36">school</i> PT
        </a>

        <?php displaySideNavigation(); ?>
        <?php displayTopNavigation(); ?>

        <a href="#" data-activates="slide-out" class="button-collapse" id="menu">
            <i class="material-icons">menu</i>
        </a>
    </div>
</nav>

<?php

/**
 * Display side navigation for mobile
 */
function displaySideNavigation()
{
    ?>
    <ul id="slide-out" class="side-nav" data-simplebar-direction="vertical">
        <?php
        displaySideLogo();
        displayUserMenu();
        displayMainMenuItems();
        displayAdminMenuItems();
        ?>
    </ul>
    <?php
}

/**
 * Display side logo and institution info
 */
function displaySideLogo()
{
    global $config;
    ?>
    <li class="no-padding">
        <div class="logo-side center red darken-5">
            <?php
            $query = mysqli_query($config, "SELECT * FROM tbl_instansi LIMIT 1");
            $data = mysqli_fetch_array($query);

            // Logo
            $logo = !empty($data['logo']) ? './upload/logo.png' : './asset/img/logo.png';
            echo '<img class="logoside" src="' . htmlspecialchars($logo) . '"/>';

            // Institution name
            $institutionName = !empty($data['nama']) ? htmlspecialchars($data['nama']) : 'SMK TI DWIGUNA';
            echo '<h5 class="smk-side">' . $institutionName . '</h5>';

            // Address
            $address = !empty($data['alamat']) ? htmlspecialchars($data['alamat']) : 'Jl. Raya Citayam, Gg. H. Dul No.100 Cipayung, Kota Depok';
            echo '<p class="description-side">' . $address . '</p>';
            ?>
        </div>
    </li>
    <?php
}

/**
 * Display user menu in side navigation
 */
function displayUserMenu()
{
    ?>
    <li class="no-padding blue-grey darken-4">
        <ul class="collapsible collapsible-accordion">
            <li>
                <a class="collapsible-header">
                    <i class="material-icons">account_circle</i>
                    <?php echo htmlspecialchars($_SESSION['nama']); ?>
                </a>
                <div class="collapsible-body">
                    <ul>
                        <li><a href="?page=pro">Profil</a></li>
                        <li><a href="?page=pro&sub=pass">Ubah Password</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </li>
        </ul>
    </li>
    <?php
}

/**
 * Display main menu items in side navigation
 */
function displayMainMenuItems()
{
    ?>
    <li><a href="./"><i class="material-icons middle">dashboard</i> Beranda</a></li>

    <?php displayTransactionMenu(); ?>

    <li class="no-padding">
        <ul class="collapsible collapsible-accordion">
            <li>
                <a class="collapsible-header"><i class="material-icons">assignment</i> Buku Agenda</a>
                <div class="collapsible-body">
                    <ul>
                        <li><a href="?page=asm">Surat Masuk</a></li>
                        <li><a href="?page=ask">Surat Keluar</a></li>
                    </ul>
                </div>
            </li>
        </ul>
    </li>

    <li class="no-padding">
        <ul class="collapsible collapsible-accordion">
            <li>
                <a class="collapsible-header"><i class="material-icons">image</i> Galeri File</a>
                <div class="collapsible-body">
                    <ul>
                        <li><a href="?page=gsm">Surat Masuk</a></li>
                        <li><a href="?page=gsk">Surat Keluar</a></li>
                    </ul>
                </div>
            </li>
        </ul>
    </li>

    <li><a href="?page=ref"><i class="material-icons middle">class</i> Referensi</a></li>
    <?php
}

/**
 * Display transaction menu based on user role
 */
function displayTransactionMenu()
{
    if ($_SESSION['admin'] == 1 || $_SESSION['admin'] == 3) {
        ?>
        <li class="no-padding">
            <ul class="collapsible collapsible-accordion">
                <li>
                    <a class="collapsible-header"><i class="material-icons">repeat</i> Transaksi Surat</a>
                    <div class="collapsible-body">
                        <ul>
                            <li><a href="?page=tsm">Surat Masuk</a></li>
                            <li><a href="?page=tsk">Surat Keluar</a></li>
                        </ul>
                    </div>
                </li>
            </ul>
        </li>
        <?php
    }
}

/**
 * Display admin menu items based on user role
 */
function displayAdminMenuItems()
{
    if ($_SESSION['admin'] == 1) {
        displayAdminMenu([
            ['url' => '?page=sett', 'text' => 'Instansi'],
            ['url' => '?page=sett&sub=usr', 'text' => 'User'],
            ['url' => '?page=sett&sub=back', 'text' => 'Backup Database'],
            ['url' => '?page=sett&sub=rest', 'text' => 'Restore Database']
        ]);
    } elseif ($_SESSION['admin'] == 2) {
        displayAdminMenu([
            ['url' => '?page=sett', 'text' => 'Instansi'],
            ['url' => '?page=sett&sub=usr', 'text' => 'User']
        ]);
    }
}

/**
 * Display admin menu
 */
function displayAdminMenu($menuItems)
{
    ?>
    <li class="no-padding">
        <ul class="collapsible collapsible-accordion">
            <li>
                <a class="collapsible-header"><i class="material-icons">settings</i> Pengaturan</a>
                <div class="collapsible-body">
                    <ul>
                        <?php foreach ($menuItems as $item): ?>
                            <li><a href="<?php echo htmlspecialchars($item['url']); ?>">
                                    <?php echo htmlspecialchars($item['text']); ?>
                                </a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </li>
        </ul>
    </li>
    <?php
}

/**
 * Display top navigation for desktop
 */
function displayTopNavigation()
{
    ?>
    <ul class="center hide-on-med-and-down" id="nv">
        <?php
        displayTopLogo();
        displayTopMenuItems();
        displayUserDropdown();
        ?>
    </ul>
    <?php
}

/**
 * Display top logo
 */
function displayTopLogo()
{
    ?>
    <li>
        <a href="./" class="ams hide-on-med-and-down">
            <i class="material-icons md-36">school</i> PT
        </a>
    </li>
    <li>
        <div class="grs"></div>
    </li>
    <?php
}

/**
 * Display top menu items
 */
function displayTopMenuItems()
{
    ?>
    <li><a href="./"><i class="material-icons"></i>&nbsp; Beranda</a></li>

    <?php displayTopTransactionMenu(); ?>

    <li>
        <a class="dropdown-button" href="#!" data-activates="agenda">
            Buku Agenda <i class="material-icons md-18">arrow_drop_down</i>
        </a>
    </li>
    <ul id='agenda' class='dropdown-content'>
        <li><a href="?page=asm">Surat Masuk</a></li>
        <li><a href="?page=ask">Surat Keluar</a></li>
    </ul>

    <li>
        <a class="dropdown-button" href="#!" data-activates="galeri">
            Galeri File <i class="material-icons md-18">arrow_drop_down</i>
        </a>
    </li>
    <ul id='galeri' class='dropdown-content'>
        <li><a href="?page=gsm">Surat Masuk</a></li>
        <li><a href="?page=gsk">Surat Keluar</a></li>
    </ul>

    <li><a href="?page=ref">Referensi</a></li>

    <?php displayTopAdminMenu(); ?>
<?php
}

/**
 * Display transaction menu in top navigation
 */
function displayTopTransactionMenu()
{
    if ($_SESSION['admin'] == 1 || $_SESSION['admin'] == 3) {
        ?>
        <li>
            <a class="dropdown-button" href="#!" data-activates="transaksi">
                Transaksi Surat <i class="material-icons md-18">arrow_drop_down</i>
            </a>
        </li>
        <ul id='transaksi' class='dropdown-content'>
            <li><a href="?page=tsm">Surat Masuk</a></li>
            <li><a href="?page=tsk">Surat Keluar</a></li>
        </ul>
        <?php
    }
}

/**
 * Display admin menu in top navigation
 */
function displayTopAdminMenu()
{
    if ($_SESSION['admin'] == 1) {
        displayTopAdminDropdown([
            ['url' => '?page=sett', 'text' => 'Instansi'],
            ['url' => '?page=sett&sub=usr', 'text' => 'User'],
            ['type' => 'divider'],
            ['url' => '?page=sett&sub=back', 'text' => 'Backup Database'],
            ['url' => '?page=sett&sub=rest', 'text' => 'Restore Database']
        ]);
    } elseif ($_SESSION['admin'] == 2) {
        displayTopAdminDropdown([
            ['url' => '?page=sett', 'text' => 'Instansi'],
            ['url' => '?page=sett&sub=usr', 'text' => 'User']
        ]);
    }
}

/**
 * Display admin dropdown in top navigation
 */
function displayTopAdminDropdown($menuItems)
{
    ?>
    <li>
        <a class="dropdown-button" href="#!" data-activates="pengaturan">
            Pengaturan <i class="material-icons md-18">arrow_drop_down</i>
        </a>
    </li>
    <ul id='pengaturan' class='dropdown-content'>
        <?php foreach ($menuItems as $item): ?>
            <?php if (isset($item['type']) && $item['type'] === 'divider'): ?>
                <li class="divider"></li>
            <?php else: ?>
                <li>
                    <a href="<?php echo htmlspecialchars($item['url']); ?>">
                        <?php echo htmlspecialchars($item['text']); ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
    <?php
}

/**
 * Display user dropdown in top navigation
 */
function displayUserDropdown()
{
    ?>
    <li class="right" style="margin-right: 10px;">
        <a class="dropdown-button" href="#!" data-activates="logout">
            <i class="material-icons">account_circle</i>
            <?php echo htmlspecialchars($_SESSION['nama']); ?>
            <i class="material-icons md-18">arrow_drop_down</i>
        </a>
    </li>
    <ul id='logout' class='dropdown-content'>
        <li><a href="?page=pro">Profil</a></li>
        <li><a href="?page=pro&sub=pass">Ubah Password</a></li>
        <li class="divider"></li>
        <li>
            <a href="logout.php">
                <i class="material-icons">settings_power</i> Logout
            </a>
        </li>
    </ul>
    <?php
}
?>
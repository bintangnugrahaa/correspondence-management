<?php
ob_start();
session_start();

// Check if user is logged in as admin
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

// Database connection (assuming $config is defined elsewhere)
require_once 'include/config.php';

/**
 * Get count from database table
 */
function getCount($tableName)
{
    global $config;
    $result = mysqli_query($config, "SELECT COUNT(*) as count FROM $tableName");
    $row = mysqli_fetch_assoc($result);
    return $row['count'] ?? 0;
}

/**
 * Render dashboard statistics cards
 */
function renderStatCard($title, $count, $description, $colorClass, $icon)
{
    echo "
    <div class=\"col s12 m4\">
        <div class=\"card $colorClass\">
            <div class=\"card-content\">
                <span class=\"card-title white-text\">
                    <i class=\"material-icons md-36\">$icon</i> $title
                </span>
                <h5 class=\"white-text link\">$count $description</h5>
            </div>
        </div>
    </div>";
}

/**
 * Get user role description
 */
function getUserRole($adminLevel, $nama)
{
    switch ($adminLevel) {
        case 1:
            return "ADMIN UTAMA. Anda memiliki akses penuh terhadap sistem.";
        case 2:
            return "ADMINISTRATOR. Berikut adalah statistik data yang tersimpan dalam sistem.";
        default:
            return "Petugas Disposisi. Berikut adalah statistik data yang tersimpan dalam sistem.";
    }
}

/**
 * Include page based on request
 */
function includePage($page)
{
    $allowedPages = [
        'tsm' => 'transaksi_surat_masuk.php',
        'ctk' => 'cetak_disposisi.php',
        'tsk' => 'transaksi_surat_keluar.php',
        'asm' => 'agenda_surat_masuk.php',
        'ask' => 'agenda_surat_keluar.php',
        'ref' => 'referensi.php',
        'sett' => 'pengaturan.php',
        'pro' => 'profil.php',
        'gsm' => 'galeri_sm.php',
        'gsk' => 'galeri_sk.php'
    ];

    if (isset($allowedPages[$page])) {
        include $allowedPages[$page];
        return true;
    }
    return false;
}
?>

<!DOCTYPE html>
<html lang="id">

<!-- Include Head -->
<?php include('include/head.php'); ?>

<body class="bg">
    <header>
        <!-- Include Navigation -->
        <?php include('include/menu.php'); ?>
    </header>

    <main>
        <div class="container">
            <?php
            // Handle page inclusion based on request
            if (isset($_GET['page']) && includePage($_GET['page'])) {
                // Page has been included via the function
            } else {
                // Show dashboard
                ?>
                <div class="row">
                    <!-- Include Header Instansi -->
                    <?php include('include/header_instansi.php'); ?>

                    <!-- Welcome Message -->
                    <div class="col s12">
                        <div class="card">
                            <div class="card-content">
                                <h4>Selamat Datang <?php echo htmlspecialchars($_SESSION['nama']); ?></h4>
                                <p class="description">
                                    Anda login sebagai
                                    <strong><?php echo getUserRole($_SESSION['admin'], $_SESSION['nama']); ?></strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <?php
                    // Get counts from database
                    $counts = [
                        'Surat Masuk' => ['count' => getCount('tbl_surat_masuk'), 'desc' => 'Surat Masuk', 'color' => 'pink', 'icon' => 'mail'],
                        'Surat Keluar' => ['count' => getCount('tbl_surat_keluar'), 'desc' => 'Surat Keluar', 'color' => 'cyan darken-1', 'icon' => 'drafts'],
                        'Disposisi' => ['count' => getCount('tbl_disposisi'), 'desc' => 'Disposisi', 'color' => 'green darken-5', 'icon' => 'description'],
                        'Klasifikasi Surat' => ['count' => getCount('tbl_klasifikasi'), 'desc' => 'Klasifikasi Surat', 'color' => 'deep-orange', 'icon' => 'class']
                    ];

                    // Render statistic cards
                    foreach ($counts as $title => $data) {
                        renderStatCard(
                            "Jumlah $title",
                            $data['count'],
                            $data['desc'],
                            $data['color'],
                            $data['icon']
                        );
                    }

                    // Show user count only for admin users
                    if ($_SESSION['admin'] == 1 || $_SESSION['admin'] == 2) {
                        $userCount = getCount('tbl_user');
                        renderStatCard(
                            'Jumlah Pengguna',
                            $userCount,
                            'Pengguna',
                            'red accent-2',
                            'people'
                        );
                    }
                    ?>
                </div>
            <?php } ?>
        </div>
    </main>

    <!-- Include Footer -->
    <?php include('include/footer.php'); ?>
</body>

</html>
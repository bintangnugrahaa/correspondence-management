<?php
// Check session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

if (isset($_REQUEST['submit'])) {
    displayAgendaReport();
} else {
    displayDateRangeForm();
}

/**
 * Display agenda report based on date range
 */
function displayAgendaReport()
{
    global $config;

    $dari_tanggal = $_REQUEST['dari_tanggal'];
    $sampai_tanggal = $_REQUEST['sampai_tanggal'];

    // Validate date range
    if (empty($dari_tanggal) || empty($sampai_tanggal)) {
        header("Location: ./admin.php?page=ask");
        exit();
    }

    $suratData = getSuratKeluarByDateRange($dari_tanggal, $sampai_tanggal);
    $instansiData = getInstansiData();

    outputAgendaReport($dari_tanggal, $sampai_tanggal, $suratData, $instansiData);
}

/**
 * Get surat keluar data by date range
 */
function getSuratKeluarByDateRange($dari_tanggal, $sampai_tanggal)
{
    global $config;

    $dari_tanggal = mysqli_real_escape_string($config, $dari_tanggal);
    $sampai_tanggal = mysqli_real_escape_string($config, $sampai_tanggal);

    return mysqli_query(
        $config,
        "SELECT sk.*, u.nama as pengelola 
         FROM tbl_surat_keluar sk 
         LEFT JOIN tbl_user u ON sk.id_user = u.id_user 
         WHERE sk.tgl_catat BETWEEN '$dari_tanggal' AND '$sampai_tanggal' 
         ORDER BY sk.tgl_catat"
    );
}

/**
 * Get instansi data
 */
function getInstansiData()
{
    global $config;

    $query = mysqli_query($config, "SELECT institusi, nama, status, alamat, logo FROM tbl_instansi LIMIT 1");
    return ($query && mysqli_num_rows($query) > 0) ? mysqli_fetch_assoc($query) : [];
}

/**
 * Output agenda report
 */
function outputAgendaReport($dari_tanggal, $sampai_tanggal, $suratData, $instansiData)
{
    echo getPrintStyles();
    echo getNavigationHeader();
    echo getDateRangeForm();
    echo getReportHeader($instansiData, $dari_tanggal, $sampai_tanggal);
    echo getAgendaTable($suratData);
}

/**
 * Get print styles
 */
function getPrintStyles()
{
    return '
    <style type="text/css">
        .visible-print {
            display: none;
        }
        
        @media print {
            body {
                font-size: 12px!important;
                color: #212121;
            }
            .print-header {
                text-align: center;
                margin: -.5rem 0;
                width: 100%;
            }
            nav {
                display: none;
            }
            .hidden-print {
                display: none !important;
            }
            .visible-print {
                display: block !important;
            }
            .logo-print {
                position: absolute;
                width: 80px;
                height: 80px;
                left: 50px;
                margin: 0 0 0 1.2rem;
            }
            .institution-type {
                font-size: 17px!important;
                font-weight: normal;
                margin-top: 45px;
                text-transform: uppercase;
            }
            .institution-name {
                font-size: 20px!important;
                text-transform: uppercase;
                margin-top: 5px;
                font-weight: bold;
            }
            .institution-status {
                font-size: 17px!important;
                font-weight: normal;
                margin-top: -1.5rem;
            }
            .institution-address {
                margin-top: -15px;
                font-size: 13px;
            }
            .separator {
                border-bottom: 2px solid #616161;
                margin: 1rem 0;
            }
            .agenda-table {
                width: 100%;
                border-collapse: collapse;
            }
            .agenda-table th,
            .agenda-table td {
                border: 1px solid #444;
                padding: 8px;
                vertical-align: top;
            }
        }
    </style>';
}

/**
 * Get navigation header
 */
function getNavigationHeader()
{
    return '
    <!-- Row Start -->
    <div class="row hidden-print">
        <!-- Secondary Nav START -->
        <div class="col s12">
            <div class="z-depth-1">
                <nav class="secondary-nav">
                    <div class="nav-wrapper blue-grey darken-1">
                        <div class="col 12">
                            <ul class="left">
                                <li class="waves-effect waves-light">
                                    <a href="?page=ask" class="judul">
                                        <i class="material-icons">print</i> Cetak Agenda Surat Keluar
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
 * Get date range form
 */
function getDateRangeForm()
{
    return '
    <!-- Row form Start -->
    <div class="row jarak-form black-text hidden-print">
        <form class="col s12" method="post" action="">
            <div class="input-field col s3">
                <i class="material-icons prefix md-prefix">date_range</i>
                <input id="dari_tanggal" type="text" name="dari_tanggal" class="datepicker" required>
                <label for="dari_tanggal">Dari Tanggal</label>
            </div>
            <div class="input-field col s3">
                <i class="material-icons prefix md-prefix">date_range</i>
                <input id="sampai_tanggal" type="text" name="sampai_tanggal" class="datepicker" required>
                <label for="sampai_tanggal">Sampai Tanggal</label>
            </div>
            <div class="col s6">
                <button type="submit" name="submit" class="btn-large blue waves-effect waves-light">
                    TAMPILKAN <i class="material-icons">visibility</i>
                </button>
            </div>
        </form>
    </div>
    <!-- Row form END -->';
}

/**
 * Get report header with institution info
 */
function getReportHeader($instansiData, $dari_tanggal, $sampai_tanggal)
{
    $logo = !empty($instansiData['logo']) ? './upload/' . htmlspecialchars($instansiData['logo']) : '';
    $institusi = !empty($instansiData['institusi']) ? htmlspecialchars($instansiData['institusi']) : '';
    $nama = !empty($instansiData['nama']) ? htmlspecialchars($instansiData['nama']) : '';
    $status = !empty($instansiData['status']) ? htmlspecialchars($instansiData['status']) : '';
    $alamat = !empty($instansiData['alamat']) ? htmlspecialchars($instansiData['alamat']) : '';

    return '
    <div class="row agenda">
        <div class="print-header visible-print">
            ' . ($logo ? '<img class="logo-print" src="' . $logo . '"/>' : '') . '
            <h6 class="institution-type">' . $institusi . '</h6>
            <h5 class="institution-name">' . $nama . '</h5><br/>
            <h6 class="institution-status">' . $status . '</h6>
            <span class="institution-address">' . $alamat . '</span>
        </div>
        <div class="separator visible-print"></div>
        <h5 class="visible-print">AGENDA SURAT KELUAR</h5>
        <div class="col s10">
            <p class="agenda-info">
                Agenda Surat Keluar dari tanggal 
                <strong>' . indoDate($dari_tanggal) . '</strong> 
                sampai dengan tanggal 
                <strong>' . indoDate($sampai_tanggal) . '</strong>
            </p>
        </div>
        <div class="col s2 hidden-print">
            <button type="button" onclick="window.print()" class="btn-large deep-orange waves-effect waves-light right">
                CETAK <i class="material-icons">print</i>
            </button>
        </div>
    </div>';
}

/**
 * Get agenda table
 */
function getAgendaTable($suratData)
{
    return '
    <div id="colres" class="cetak">
        <table class="bordered agenda-table" id="tbl">
            <thead class="blue lighten-4">
                ' . getTableHeaders() . '
            </thead>
            <tbody>
                ' . getTableRows($suratData) . '
            </tbody>
        </table>
    </div>
    <div class="jarak2"></div>';
}

/**
 * Get table headers
 */
function getTableHeaders()
{
    $headers = [
        'No Agenda' => '3%',
        'Kode' => '5%',
        'Isi Ringkas' => '21%',
        'Tujuan Surat' => '18%',
        'Nomor Surat' => '15%',
        'Tanggal<br/> Surat' => '10%',
        'Pengelola' => '12%',
        'Keterangan' => '10%'
    ];

    $headerHtml = '<tr>';
    foreach ($headers as $text => $width) {
        $headerHtml .= '<th width="' . $width . '">' . $text . '</th>';
    }
    $headerHtml .= '</tr>';

    return $headerHtml;
}

/**
 * Get table rows
 */
function getTableRows($suratData)
{
    if (!$suratData || mysqli_num_rows($suratData) == 0) {
        return '
        <tr>
            <td colspan="8">
                <center><p class="add">Tidak ada agenda surat</p></center>
            </td>
        </tr>';
    }

    $rows = '';
    while ($row = mysqli_fetch_assoc($suratData)) {
        $rows .= '
        <tr>
            <td>' . htmlspecialchars($row['no_agenda']) . '</td>
            <td>' . htmlspecialchars($row['kode']) . '</td>
            <td>' . htmlspecialchars($row['isi']) . '</td>
            <td>' . htmlspecialchars($row['tujuan']) . '</td>
            <td>' . htmlspecialchars($row['no_surat']) . '</td>
            <td>' . indoDate($row['tgl_surat']) . '</td>
            <td>' . getPengelolaDisplay($row) . '</td>
            <td>' . htmlspecialchars($row['keterangan']) . '</td>
        </tr>';
    }

    return $rows;
}

/**
 * Get pengelola display name
 */
function getPengelolaDisplay($row)
{
    if ($row['id_user'] == 1) {
        return 'Administrator';
    } else {
        return htmlspecialchars($row['pengelola'] ?? '');
    }
}

/**
 * Display date range form (initial state)
 */
function displayDateRangeForm()
{
    echo getPrintStyles();
    echo getNavigationHeader();
    echo getDateRangeForm();
    echo '<div class="jarak"></div>';
}
?>
<?php
// Check session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<strong>ERROR!</strong> Anda harus login terlebih dahulu.';
    header("Location: ./");
    exit();
}

displayPrintPage();

/**
 * Display print page for disposisi
 */
function displayPrintPage()
{
    global $config;

    $id_surat = mysqli_real_escape_string($config, $_REQUEST['id_surat']);
    $suratData = getSuratData($id_surat);

    if (!$suratData) {
        echo '<script>alert("Data surat tidak ditemukan"); window.close();</script>';
        return;
    }

    $instansiData = getInstansiData();
    $disposisiData = getDisposisiData($id_surat);

    outputPrintPage($instansiData, $suratData, $disposisiData);
}

/**
 * Get surat data from database
 */
function getSuratData($id_surat)
{
    global $config;

    $query = mysqli_query($config, "SELECT * FROM tbl_surat_masuk WHERE id_surat='$id_surat'");
    return ($query && mysqli_num_rows($query) > 0) ? mysqli_fetch_assoc($query) : false;
}

/**
 * Get instansi data from database
 */
function getInstansiData()
{
    global $config;

    $query = mysqli_query($config, "SELECT institusi, nama, status, alamat, logo, kepsek, nip FROM tbl_instansi LIMIT 1");
    return ($query && mysqli_num_rows($query) > 0) ? mysqli_fetch_assoc($query) : [];
}

/**
 * Get disposisi data from database
 */
function getDisposisiData($id_surat)
{
    global $config;

    $query = mysqli_query(
        $config,
        "SELECT * FROM tbl_disposisi 
         JOIN tbl_surat_masuk ON tbl_disposisi.id_surat = tbl_surat_masuk.id_surat 
         WHERE tbl_disposisi.id_surat='$id_surat'"
    );

    return ($query && mysqli_num_rows($query) > 0) ? mysqli_fetch_assoc($query) : false;
}

/**
 * Output the print page
 */
function outputPrintPage($instansiData, $suratData, $disposisiData)
{
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Cetak Disposisi</title>
        <meta charset="UTF-8">
        ' . ctk_getPrintStyles() . '
    </head>
    <body onload="window.print()">
        ' . getPrintContent($instansiData, $suratData, $disposisiData) . '
    </body>
    </html>';
}

/**
 * Get print styles
 */
function ctk_getPrintStyles()
{
    return '
    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #212121;
            font-size: 14px;
        }
        
        table {
            background: #fff;
            padding: 5px;
            width: 100%;
            border-collapse: collapse;
        }
        
        tr, td {
            border: 1px solid #444;
            padding: 8px;
            vertical-align: top;
        }
        
        .border-right-none {
            border-right: none !important;
        }
        
        .border-left-none {
            border-left: none !important;
        }
        
        .content-height {
            height: 300px;
        }
        
        .header {
            text-align: center;
            padding: 1.5rem 0;
            margin-bottom: .5rem;
        }
        
        .logo {
            float: left;
            position: relative;
            width: 110px;
            height: 110px;
            margin: 0 0 0 1rem;
        }
        
        .signature {
            width: auto;
            position: relative;
            margin: 25px 0 0 75%;
        }
        
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: -10px;
        }
        
        .text-center {
            text-align: center;
        }
        
        .institution-name {
            font-size: 2.1rem;
            margin-bottom: -1rem;
        }
        
        .institution-address {
            font-size: 16px;
        }
        
        .institution-type {
            text-transform: uppercase;
            margin: 0;
            line-height: 2.2rem;
            font-size: 1.5rem;
        }
        
        .institution-status {
            margin: 0;
            font-size: 1.3rem;
            margin-bottom: .5rem;
        }
        
        .document-title {
            font-size: 20px;
            font-weight: bold;
        }
        
        .separator {
            border-bottom: 2px solid #616161;
            margin: -1.3rem 0 1.5rem;
        }
        
        .spacing {
            height: 50px;
        }
        
        .small-spacing {
            height: 25px;
        }

        @media print {
            body {
                font-size: 12px;
                color: #212121;
            }
            
            nav {
                display: none;
            }
            
            table {
                width: 100%;
                font-size: 12px;
            }
            
            tr, td {
                padding: 8px;
            }
            
            .document-title {
                font-size: 17px;
            }
            
            .content-height {
                height: 200px;
            }
            
            .header {
                margin: -.5rem 0;
            }
            
            .logo {
                width: 80px;
                height: 80px;
                margin: .5rem 0 0 .5rem;
            }
            
            .signature {
                margin: 15px 0 0 75%;
            }
            
            .institution-name {
                font-size: 20px;
                font-weight: bold;
                text-transform: uppercase;
                margin: -10px 0 -20px 0;
            }
            
            .institution-type {
                font-size: 17px;
                font-weight: normal;
            }
            
            .institution-status {
                font-size: 17px;
                font-weight: normal;
                margin-bottom: -.1rem;
            }
            
            .institution-address {
                margin-top: -15px;
                font-size: 13px;
            }
            
            .separator {
                margin: -1rem 0 1rem;
            }
        }
    </style>';
}

/**
 * Get print content
 */
function getPrintContent($instansiData, $suratData, $disposisiData)
{
    return '
    <!-- Container START -->
    <div id="colres">
        ' . getHeaderSection($instansiData) . '
        ' . getDocumentContent($suratData, $disposisiData) . '
        ' . getSignatureSection($instansiData) . '
    </div>
    <!-- Container END -->';
}

/**
 * Get header section with institution info
 */
function getHeaderSection($instansiData)
{
    $logo = !empty($instansiData['logo']) ? './upload/' . htmlspecialchars($instansiData['logo']) : '';
    $institusi = !empty($instansiData['institusi']) ? htmlspecialchars($instansiData['institusi']) : '';
    $nama = !empty($instansiData['nama']) ? htmlspecialchars($instansiData['nama']) : '';
    $status = !empty($instansiData['status']) ? htmlspecialchars($instansiData['status']) : '';
    $alamat = !empty($instansiData['alamat']) ? htmlspecialchars($instansiData['alamat']) : 'Pinggir Rel, Gg . Hj Dul';

    return '
    <div class="header">
        ' . ($logo ? '<img class="logo" src="' . $logo . '"/>' : '') . '
        <h6 class="institution-type">' . $institusi . '</h6>
        <h5 class="institution-type institution-name">' . $nama . '</h5><br/>
        <h6 class="institution-status">' . $status . '</h6>
        <span class="institution-address">' . $alamat . '</span>
    </div>
    <div class="separator"></div>';
}

/**
 * Get document content
 */
function getDocumentContent($suratData, $disposisiData)
{
    return '
    <table class="bordered">
        <tbody>
            <tr>
                <td class="text-center document-title" colspan="3">LEMBAR DISPOSISI</td>
            </tr>
            ' . getSuratDetails($suratData) . '
            ' . getDisposisiContent($disposisiData) . '
        </tbody>
    </table>';
}

/**
 * Get surat details
 */
function getSuratDetails($suratData)
{
    return '
    <tr>
        <td class="border-right-none" width="18%"><strong>Indeks Berkas</strong></td>
        <td class="border-left-none" style="border-right: none;" width="57%">: ' . htmlspecialchars($suratData['indeks']) . '</td>
        <td class="border-left-none" width="25%"><strong>Kode</strong> : ' . htmlspecialchars($suratData['kode']) . '</td>
    </tr>
    <tr>
        <td class="border-right-none"><strong>Tanggal Surat</strong></td>
        <td class="border-left-none" colspan="2">: ' . indoDate($suratData['tgl_surat']) . '</td>
    </tr>
    <tr>
        <td class="border-right-none"><strong>Nomor Surat</strong></td>
        <td class="border-left-none" colspan="2">: ' . htmlspecialchars($suratData['no_surat']) . '</td>
    </tr>
    <tr>
        <td class="border-right-none"><strong>Asal Surat</strong></td>
        <td class="border-left-none" colspan="2">: ' . htmlspecialchars($suratData['asal_surat']) . '</td>
    </tr>
    <tr>
        <td class="border-right-none"><strong>Isi Ringkas</strong></td>
        <td class="border-left-none" colspan="2">: ' . htmlspecialchars($suratData['isi']) . '</td>
    </tr>
    <tr>
        <td class="border-right-none"><strong>Diterima Tanggal</strong></td>
        <td class="border-left-none" style="border-right: none;">: ' . indoDate($suratData['tgl_diterima']) . '</td>
        <td class="border-left-none"><strong>No. Agenda</strong> : ' . htmlspecialchars($suratData['no_agenda']) . '</td>
    </tr>
    <tr>
        <td class="border-right-none"><strong>Tanggal Penyelesaian</strong></td>
        <td class="border-left-none" colspan="2">: </td>
    </tr>';
}

/**
 * Get disposisi content
 */
function getDisposisiContent($disposisiData)
{
    if ($disposisiData) {
        return '
        <tr class="content-height">
            <td colspan="2">
                <strong>Isi Disposisi :</strong><br/>' . htmlspecialchars($disposisiData['isi_disposisi']) . '
                <div class="spacing"></div>
                <strong>Batas Waktu</strong> : ' . indoDate($disposisiData['batas_waktu']) . '<br/>
                <strong>Sifat</strong> : ' . htmlspecialchars($disposisiData['sifat']) . '<br/>
                <strong>Catatan</strong> :<br/> ' . htmlspecialchars($disposisiData['catatan']) . '
                <div class="small-spacing"></div>
            </td>
            <td><strong>Diteruskan Kepada</strong> : <br/>' . htmlspecialchars($disposisiData['tujuan']) . '</td>
        </tr>';
    } else {
        return '
        <tr class="content-height">
            <td colspan="2"><strong>Isi Disposisi :</strong></td>
            <td><strong>Diteruskan Kepada</strong> : </td>
        </tr>';
    }
}

/**
 * Get signature section
 */
function getSignatureSection($instansiData)
{
    $kepsek = !empty($instansiData['kepsek']) ? htmlspecialchars($instansiData['kepsek']) : 'H. Riza Fachri, S.Kom.';
    $nip = !empty($instansiData['nip']) ? 'NIP. ' . htmlspecialchars($instansiData['nip']) : 'NIP. -';

    return '
    <div class="signature">
        <p>Kepala Sekolah</p>
        <div class="spacing"></div>
        <p class="signature-name">' . $kepsek . '</p>
        <p>' . $nip . '</p>
    </div>
    <div class="jarak2"></div>';
}
?>
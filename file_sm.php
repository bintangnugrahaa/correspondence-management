<?php
// Check session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

displayFileDetail();

/**
 * Display file detail page
 */
function displayFileDetail()
{
    global $config;

    $id_surat = mysqli_real_escape_string($config, $_REQUEST['id_surat']);
    $suratData = getSuratData($id_surat);

    if (!$suratData) {
        echo '<script>alert("Data surat tidak ditemukan"); window.history.back();</script>';
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

    $query = mysqli_query($config, "SELECT * FROM tbl_surat_masuk WHERE id_surat='$id_surat'");
    return ($query && mysqli_num_rows($query) > 0) ? mysqli_fetch_assoc($query) : false;
}

/**
 * Display page content
 */
function displayPageContent($suratData)
{
    echo '
    <div class="row jarak-form">
        ' . getSuratDetailsCollapsible($suratData) . '
        ' . getBackButton() . '
        ' . getFileDisplay($suratData) . '
    </div>';
}

/**
 * Get surat details collapsible section
 */
function getSuratDetailsCollapsible($suratData)
{
    return '
    <ul class="collapsible white" data-collapsible="accordion">
        <li>
            <div class="collapsible-header white">
                <i class="material-icons md-prefix md-36">expand_more</i>
                <span class="add">Tampilkan detail data surat masuk</span>
            </div>
            <div class="collapsible-body white">
                <div class="col m12 white">
                    ' . getSuratDetailsTable($suratData) . '
                </div>
            </div>
        </li>
    </ul>';
}

/**
 * Get surat details table
 */
function getSuratDetailsTable($suratData)
{
    $details = [
        'No. Agenda' => htmlspecialchars($suratData['no_agenda']),
        'Kode Klasifikasi' => htmlspecialchars($suratData['kode']),
        'Indeks Berkas' => htmlspecialchars($suratData['indeks']),
        'Isi Ringkas' => htmlspecialchars($suratData['isi']),
        'Asal Surat' => htmlspecialchars($suratData['asal_surat']),
        'No. Surat' => htmlspecialchars($suratData['no_surat']),
        'Tanggal Surat' => indoDate($suratData['tgl_surat']),
        'Keterangan' => htmlspecialchars($suratData['keterangan'])
    ];

    $tableRows = '';
    foreach ($details as $label => $value) {
        $tableRows .= '
        <tr>
            <td width="13%">' . $label . '</td>
            <td width="1%">:</td>
            <td width="86%">' . $value . '</td>
        </tr>';
    }

    return '
    <table>
        <tbody>
            ' . $tableRows . '
        </tbody>
    </table>';
}

/**
 * Get back button
 */
function getBackButton()
{
    return '
    <button onclick="window.history.back()" class="btn-large blue waves-effect waves-light left">
        <i class="material-icons">arrow_back</i> KEMBALI
    </button>';
}

/**
 * Get file display based on file type
 */
function getFileDisplay($suratData)
{
    if (empty($suratData['file'])) {
        return '';
    }

    $fileInfo = sm_getFileInfo($suratData['file']);
    $fileType = getFileType($fileInfo['extension']);

    switch ($fileType) {
        case 'image':
            return getImageDisplay($suratData);
        case 'document':
            return getDocumentDisplay($suratData, 'document', 'word.png');
        case 'pdf':
            return getDocumentDisplay($suratData, 'PDF', 'pdf.png');
        default:
            return getDocumentDisplay($suratData, 'file', 'pdf.png');
    }
}

/**
 * Get file information
 */
function sm_getFileInfo($filename)
{
    $fileParts = explode('.', $filename);
    return [
        'extension' => strtolower(end($fileParts)),
        'filename' => $filename
    ];
}

/**
 * Get file type based on extension
 */
function getFileType($extension)
{
    $imageExtensions = ['jpg', 'png', 'jpeg'];
    $documentExtensions = ['doc', 'docx'];
    $pdfExtensions = ['pdf'];

    if (in_array($extension, $imageExtensions)) {
        return 'image';
    } elseif (in_array($extension, $documentExtensions)) {
        return 'document';
    } elseif (in_array($extension, $pdfExtensions)) {
        return 'pdf';
    } else {
        return 'other';
    }
}

/**
 * Get image display
 */
function getImageDisplay($suratData)
{
    return '
    <img class="gbr" 
         data-caption="' . date('d M Y', strtotime($suratData['tgl_diterima'])) . '" 
         src="./upload/surat_masuk/' . htmlspecialchars($suratData['file']) . '"/>';
}

/**
 * Get document display (for non-image files)
 */
function getDocumentDisplay($suratData, $fileType, $iconFile)
{
    $fileTypeText = strtoupper($fileType);

    return '
    <div class="gbr">
        <div class="row">
            <div class="col s12">
                <div class="col s9 left">
                    <div class="card">
                        <div class="card-content">
                            <p>File lampiran surat masuk ini bertipe <strong>' . $fileTypeText . '</strong>, silakan klik link dibawah ini untuk melihat file lampiran tersebut.</p>
                        </div>
                        <div class="card-action">
                            <strong>Lihat file :</strong> 
                            <a class="blue-text" href="./upload/surat_masuk/' . htmlspecialchars($suratData['file']) . '" target="_blank">
                                ' . htmlspecialchars($suratData['file']) . '
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col s3 right">
                    <img class="file" src="./asset/img/' . $iconFile . '">
                </div>
            </div>
        </div>
    </div>';
}
?>
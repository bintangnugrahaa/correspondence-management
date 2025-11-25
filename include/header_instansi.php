<?php
// Check session
if (empty($_SESSION['admin'])) {
    header("Location: ../");
    exit();
}

// Render institution header
renderInstitutionHeader($config);

/**
 * Render institution header with logo and information
 */
function renderInstitutionHeader($config)
{
    $institutionData = head_getInstitutionData($config);

    echo '
        <div class="col s12" id="header-instansi">
            <div class="card blue-grey white-text">
                <div class="card-content">
                    ' . renderLogo($institutionData) . '
                    ' . renderInstitutionName($institutionData) . '
                    ' . renderInstitutionAddress($institutionData) . '
                </div>
            </div>
        </div>';
}

/**
 * Get institution data from database
 */
function head_getInstitutionData($config)
{
    $defaultData = [
        'logo' => 'asset/img/logo.png',
        'nama' => 'SMK TI DWIGUNA',
        'alamat' => 'Jl. Raya Citayam, Gg. H. Dul No.100 Cipayung, Kota Depok'
    ];

    try {
        $query = mysqli_query($config, "SELECT logo, nama, alamat FROM tbl_instansi LIMIT 1");
        if ($query && $data = mysqli_fetch_assoc($query)) {
            return [
                'logo' => !empty($data['logo']) ? 'upload/' . htmlspecialchars($data['logo']) : $defaultData['logo'],
                'nama' => !empty($data['nama']) ? htmlspecialchars($data['nama']) : $defaultData['nama'],
                'alamat' => !empty($data['alamat']) ? htmlspecialchars($data['alamat']) : $defaultData['alamat']
            ];
        }
    } catch (Exception $e) {
        error_log("Failed to fetch institution data: " . $e->getMessage());
    }

    return $defaultData;
}

/**
 * Render institution logo
 */
function renderLogo($institutionData)
{
    $logoPath = $institutionData['logo'];
    $altText = htmlspecialchars($institutionData['nama']) . ' Logo';

    return '
        <div class="circle left">
            <img class="logo" src="' . $logoPath . '" alt="' . $altText . '" />
        </div>';
}

/**
 * Render institution name
 */
function renderInstitutionName($institutionData)
{
    return '<h5 class="ins">' . $institutionData['nama'] . '</h5>';
}

/**
 * Render institution address
 */
function renderInstitutionAddress($institutionData)
{
    return '<p class="almt">' . $institutionData['alamat'] . '</p>';
}
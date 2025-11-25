<?php
// Check session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: ./");
    exit();
}

if (isset($_REQUEST['submit'])) {
    handleFormSubmission();
} else {
    displayForm();
}

/**
 * Handle form submission
 */
function handleFormSubmission()
{
    // Validate required fields
    if (!validateRequiredFields()) {
        echo '<script language="javascript">window.history.back();</script>';
        return;
    }

    // Sanitize and validate input data
    $data = sanitizeAndValidateInput();
    if (!$data) {
        echo '<script language="javascript">window.history.back();</script>';
        return;
    }

    // Insert data into database
    if (insertDisposisi($data)) {
        $_SESSION['succAdd'] = 'SUKSES! Data berhasil ditambahkan';
        echo '<script language="javascript">
                window.location.href="./admin.php?page=tsm&act=disp&id_surat=' . $data['id_surat'] . '";
              </script>';
    } else {
        $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
        echo '<script language="javascript">window.history.back();</script>';
    }
}

/**
 * Validate required fields
 */
function validateRequiredFields()
{
    $requiredFields = [
        'tujuan',
        'isi_disposisi',
        'sifat',
        'batas_waktu',
        'catatan'
    ];

    foreach ($requiredFields as $field) {
        if (empty($_REQUEST[$field])) {
            $_SESSION['errEmpty'] = 'ERROR! Semua form wajib diisi';
            return false;
        }
    }
    return true;
}

/**
 * Sanitize and validate input data
 */
function sanitizeAndValidateInput()
{
    $validationRules = [
        'tujuan' => [
            'pattern' => "/^[a-zA-Z0-9.,()\/ -]*$/",
            'error' => 'Form Tujuan Disposisi hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,) minus(-). kurung() dan garis miring(/)',
            'session_key' => 'tujuan'
        ],
        'isi_disposisi' => [
            'pattern' => "/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/",
            'error' => 'Form Isi Disposisi hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), dan(&), underscore(_), kurung(), persen(%) dan at(@)',
            'session_key' => 'isi_disposisi'
        ],
        'batas_waktu' => [
            'pattern' => "/^[0-9 -]*$/",
            'error' => 'Form Batas Waktu hanya boleh mengandung karakter huruf dan minus(-)',
            'session_key' => 'batas_waktu'
        ],
        'catatan' => [
            'pattern' => "/^[a-zA-Z0-9.,()%@\/ -]*$/",
            'error' => 'Form catatan hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-) garis miring(/), dan kurung()',
            'session_key' => 'catatan'
        ],
        'sifat' => [
            'pattern' => "/^[a-zA-Z0 ]*$/",
            'error' => 'Form SIFAT hanya boleh mengandung karakter huruf dan spasi',
            'session_key' => 'sifat'
        ]
    ];

    $data = [];

    foreach ($validationRules as $field => $rule) {
        $value = $_REQUEST[$field];

        if (!preg_match($rule['pattern'], $value)) {
            $_SESSION[$rule['session_key']] = $rule['error'];
            return false;
        }

        $data[$field] = $value;
    }

    $data['id_surat'] = $_REQUEST['id_surat'];
    $data['id_user'] = $_SESSION['id_user'];
    return $data;
}

/**
 * Insert disposisi data into database
 */
function insertDisposisi($data)
{
    global $config;

    $escapedData = [];
    foreach ($data as $key => $value) {
        $escapedData[$key] = mysqli_real_escape_string($config, $value);
    }

    $query = "INSERT INTO tbl_disposisi(
        tujuan, isi_disposisi, sifat, batas_waktu, catatan, id_surat, id_user
    ) VALUES(
        '{$escapedData['tujuan']}', 
        '{$escapedData['isi_disposisi']}', 
        '{$escapedData['sifat']}', 
        '{$escapedData['batas_waktu']}', 
        '{$escapedData['catatan']}', 
        '{$escapedData['id_surat']}', 
        '{$escapedData['id_user']}'
    )";

    return mysqli_query($config, $query);
}

/**
 * Display the form
 */
function displayForm()
{
    // Verify surat exists and get ID
    $id_surat = verifyAndGetSuratId();
    if (!$id_surat) {
        return;
    }

    displayNavigationHeader();
    displayErrorMessages();
    displayDisposisiForm($id_surat);
}

/**
 * Verify surat exists and get ID
 */
function verifyAndGetSuratId()
{
    global $config;

    if (!isset($_REQUEST['id_surat'])) {
        echo '<script language="javascript">
                window.alert("ERROR! ID Surat tidak valid");
                window.location.href="./admin.php?page=tsm";
              </script>';
        return false;
    }

    $id_surat = mysqli_real_escape_string($config, $_REQUEST['id_surat']);
    $query = mysqli_query($config, "SELECT id_surat FROM tbl_surat_masuk WHERE id_surat='$id_surat'");

    if (!$query || mysqli_num_rows($query) == 0) {
        echo '<script language="javascript">
                window.alert("ERROR! Data surat tidak ditemukan");
                window.location.href="./admin.php?page=tsm";
              </script>';
        return false;
    }

    return $id_surat;
}

/**
 * Display navigation header
 */
function add_displayNavigationHeader()
{
    ?>
    <!-- Row Start -->
    <div class="row">
        <!-- Secondary Nav START -->
        <div class="col s12">
            <nav class="secondary-nav">
                <div class="nav-wrapper blue-grey darken-1">
                    <ul class="left">
                        <li class="waves-effect waves-light">
                            <a href="#" class="judul">
                                <i class="material-icons">description</i> Tambah Disposisi Surat
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
        <!-- Secondary Nav END -->
    </div>
    <!-- Row END -->
    <?php
}

/**
 * Display error messages
 */
function displayErrorMessages()
{
    $errorTypes = [
        'errQ' => 'Query Error',
        'errEmpty' => 'Empty Fields'
    ];

    foreach ($errorTypes as $sessionKey => $type) {
        if (isset($_SESSION[$sessionKey])) {
            displayAlertMessage($_SESSION[$sessionKey], 'red');
            unset($_SESSION[$sessionKey]);
        }
    }
}

/**
 * Display alert message
 */
function add_displayAlertMessage($message, $color = 'red')
{
    echo '
    <div id="alert-message" class="row">
        <div class="col m12">
            <div class="card ' . $color . ' lighten-5">
                <div class="card-content notif">
                    <span class="card-title ' . $color . '-text">
                        <i class="material-icons md-36">clear</i> ' . htmlspecialchars($message) . '
                    </span>
                </div>
            </div>
        </div>
    </div>';
}

/**
 * Display disposisi form
 */
function displayDisposisiForm($id_surat)
{
    ?>
    <!-- Row form Start -->
    <div class="row jarak-form">
        <!-- Form START -->
        <form class="col s12" method="post" action="">
            <input type="hidden" name="id_surat" value="<?php echo htmlspecialchars($id_surat); ?>">
            <?php displayFormFields(); ?>

            <div class="row">
                <div class="col 6">
                    <button type="submit" name="submit" class="btn-large blue waves-effect waves-light">
                        SIMPAN <i class="material-icons">done</i>
                    </button>
                </div>
                <div class="col 6">
                    <button type="button" onclick="window.history.back();"
                        class="btn-large deep-orange waves-effect waves-light">
                        BATAL <i class="material-icons">clear</i>
                    </button>
                </div>
            </div>
        </form>
        <!-- Form END -->
    </div>
    <!-- Row form END -->
    <?php
}

/**
 * Display form fields
 */
function displayFormFields()
{
    ?>
    <!-- Row in form START -->
    <div class="row">
        <?php
        displayTujuanField();
        displayBatasWaktuField();
        displayIsiDisposisiField();
        displayCatatanField();
        displaySifatField();
        ?>
    </div>
    <!-- Row in form END -->
    <?php
}

/**
 * Display individual form fields with error handling
 */
function displayFormField($type, $id, $label, $icon, $required = true, $extraHtml = '')
{
    echo '<div class="input-field col s6">';
    echo '<i class="material-icons prefix md-prefix">' . $icon . '</i>';

    if ($type === 'textarea') {
        echo '<textarea id="' . $id . '" class="materialize-textarea validate" name="' . $id . '"' .
            ($required ? ' required' : '') . '></textarea>';
    } else {
        echo '<input id="' . $id . '" type="' . $type . '" class="validate" name="' . $id . '"' .
            ($required ? ' required' : '') . '>';
    }

    echo $extraHtml;

    // Display field-specific errors
    if (isset($_SESSION[$id])) {
        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' .
            htmlspecialchars($_SESSION[$id]) . '</div>';
        unset($_SESSION[$id]);
    }

    echo '<label for="' . $id . '">' . $label . '</label>';
    echo '</div>';
}

// Individual field display functions
function displayTujuanField()
{
    displayFormField('text', 'tujuan', 'Tujuan Disposisi', 'place');
}

function displayBatasWaktuField()
{
    echo '<div class="input-field col s6">';
    echo '<i class="material-icons prefix md-prefix">alarm</i>';
    echo '<input id="batas_waktu" type="text" name="batas_waktu" class="datepicker" required>';

    if (isset($_SESSION['batas_waktu'])) {
        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' .
            htmlspecialchars($_SESSION['batas_waktu']) . '</div>';
        unset($_SESSION['batas_waktu']);
    }

    echo '<label for="batas_waktu">Batas Waktu</label>';
    echo '</div>';
}

function displayIsiDisposisiField()
{
    displayFormField('textarea', 'isi_disposisi', 'Isi Disposisi', 'description');
}

function displayCatatanField()
{
    displayFormField('text', 'catatan', 'Catatan', 'featured_play_list');
}

function displaySifatField()
{
    ?>
    <div class="input-field col s6">
        <i class="material-icons prefix md-prefix">low_priority</i>
        <label>Pilih Sifat Disposisi</label>
        <br />
        <div class="input-field col s11 right">
            <select class="browser-default validate" name="sifat" id="sifat" required>
                <option value="Biasa">Biasa</option>
                <option value="Penting">Penting</option>
                <option value="Segera">Segera</option>
                <option value="Rahasia">Rahasia</option>
            </select>
        </div>
        <?php
        if (isset($_SESSION['sifat'])) {
            echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' .
                htmlspecialchars($_SESSION['sifat']) . '</div>';
            unset($_SESSION['sifat']);
        }
        ?>
    </div>
    <?php
}
?>
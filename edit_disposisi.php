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
    displayEditForm();
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

    // Update data in database
    if (updateDisposisi($data)) {
        $_SESSION['succEdit'] = 'SUKSES! Data berhasil diupdate';
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

    $data['id_disposisi'] = $_REQUEST['id_disposisi'];
    $data['id_surat'] = $_REQUEST['id_surat'];
    $data['id_user'] = $_SESSION['id_user'];
    return $data;
}

/**
 * Update disposisi data in database
 */
function updateDisposisi($data)
{
    global $config;

    $escapedData = [];
    foreach ($data as $key => $value) {
        $escapedData[$key] = mysqli_real_escape_string($config, $value);
    }

    $query = "UPDATE tbl_disposisi SET 
        tujuan = '{$escapedData['tujuan']}', 
        isi_disposisi = '{$escapedData['isi_disposisi']}', 
        sifat = '{$escapedData['sifat']}', 
        batas_waktu = '{$escapedData['batas_waktu']}', 
        catatan = '{$escapedData['catatan']}', 
        id_surat = '{$escapedData['id_surat']}', 
        id_user = '{$escapedData['id_user']}' 
        WHERE id_disposisi = '{$escapedData['id_disposisi']}'";

    return mysqli_query($config, $query);
}

/**
 * Display the edit form
 */
function displayEditForm()
{
    global $config;

    $id_disposisi = mysqli_real_escape_string($config, $_REQUEST['id_disposisi']);
    $disposisiData = edit_getDisposisiData($id_disposisi);

    if (!$disposisiData) {
        echo '<script language="javascript">
                window.alert("ERROR! Data disposisi tidak ditemukan");
                window.location.href="./admin.php?page=tsm";
              </script>';
        return;
    }

    edit_displayNavigationHeader();
    displayErrorMessages();
    displayEditDisposisiForm($disposisiData);
}

/**
 * Get disposisi data from database
 */
function edit_getDisposisiData($id_disposisi)
{
    global $config;

    $query = mysqli_query($config, "SELECT * FROM tbl_disposisi WHERE id_disposisi='$id_disposisi'");

    if ($query && mysqli_num_rows($query) > 0) {
        return mysqli_fetch_assoc($query);
    }

    return false;
}

/**
 * Display navigation header
 */
function edit_displayNavigationHeader()
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
                                <i class="material-icons">edit</i> Edit Disposisi Surat
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
            edit_displayAlertMessage($_SESSION[$sessionKey], 'red');
            unset($_SESSION[$sessionKey]);
        }
    }
}

/**
 * Display alert message
 */
function edit_displayAlertMessage($message, $color = 'red')
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
 * Display edit disposisi form
 */
function displayEditDisposisiForm($disposisiData)
{
    ?>
    <!-- Row form Start -->
    <div class="row jarak-form">
        <!-- Form START -->
        <form class="col s12" method="post" action="">
            <input type="hidden" name="id_disposisi"
                value="<?php echo htmlspecialchars($disposisiData['id_disposisi']); ?>">
            <input type="hidden" name="id_surat" value="<?php echo htmlspecialchars($disposisiData['id_surat']); ?>">
            <?php displayFormFields($disposisiData); ?>

            <div class="row">
                <div class="col 6">
                    <button type="submit" name="submit" class="btn-large blue waves-effect waves-light">
                        SIMPAN <i class="material-icons">done</i>
                    </button>
                </div>
                <div class="col 6">
                    <a href="?page=tsm&act=disp&id_surat=<?php echo htmlspecialchars($disposisiData['id_surat']); ?>"
                        class="btn-large deep-orange waves-effect waves-light">
                        BATAL <i class="material-icons">clear</i>
                    </a>
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
function displayFormFields($disposisiData)
{
    ?>
    <!-- Row in form START -->
    <div class="row">
        <?php
        displayTujuanField($disposisiData['tujuan']);
        displayBatasWaktuField($disposisiData['batas_waktu']);
        displayIsiDisposisiField($disposisiData['isi_disposisi']);
        displayCatatanField($disposisiData['catatan']);
        displaySifatField($disposisiData['sifat']);
        ?>
    </div>
    <!-- Row in form END -->
    <?php
}

/**
 * Display individual form fields with error handling
 */
function displayFormField($type, $id, $label, $icon, $value, $required = true, $extraHtml = '')
{
    echo '<div class="input-field col s6">';
    echo '<i class="material-icons prefix md-prefix">' . $icon . '</i>';

    if ($type === 'textarea') {
        echo '<textarea id="' . $id . '" class="materialize-textarea validate" name="' . $id . '"' .
            ($required ? ' required' : '') . '>' . htmlspecialchars($value) . '</textarea>';
    } else {
        echo '<input id="' . $id . '" type="' . $type . '" class="validate" name="' . $id . '" value="' .
            htmlspecialchars($value) . '"' . ($required ? ' required' : '') . '>';
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
function displayTujuanField($value)
{
    displayFormField('text', 'tujuan', '', 'account_box', $value);
}

function displayBatasWaktuField($value)
{
    echo '<div class="input-field col s6">';
    echo '<i class="material-icons prefix md-prefix">alarm</i>';
    echo '<input id="batas_waktu" type="text" name="batas_waktu" class="datepicker" value="' .
        htmlspecialchars($value) . '" required>';

    if (isset($_SESSION['batas_waktu'])) {
        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' .
            htmlspecialchars($_SESSION['batas_waktu']) . '</div>';
        unset($_SESSION['batas_waktu']);
    }

    echo '<label for="batas_waktu"></label>';
    echo '</div>';
}

function displayIsiDisposisiField($value)
{
    displayFormField('textarea', 'isi_disposisi', '', 'description', $value);
}

function displayCatatanField($value)
{
    displayFormField('text', 'catatan', '', 'featured_play_list', $value);
}

function displaySifatField($currentValue)
{
    $sifatOptions = ['Biasa', 'Penting', 'Segera', 'Rahasia'];
    ?>
    <div class="input-field col s6">
        <i class="material-icons prefix md-prefix">low_priority</i>
        <label>Pilih Sifat Disposisi</label>
        <br />
        <div class="input-field col s11 right">
            <select class="browser-default validate" name="sifat" id="sifat" required>
                <option value="<?php echo htmlspecialchars($currentValue); ?>">
                    <?php echo htmlspecialchars($currentValue); ?>
                </option>
                <?php
                foreach ($sifatOptions as $option) {
                    if ($option !== $currentValue) {
                        echo '<option value="' . htmlspecialchars($option) . '">' .
                            htmlspecialchars($option) . '</option>';
                    }
                }
                ?>
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
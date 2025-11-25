<?php
// Check session
if (empty($_SESSION['admin'])) {
    header("Location: ../");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Management System</title>
</head>

<body class="bg pace-done">

    <noscript>
        <meta http-equiv="refresh" content="0;URL='./enable-javascript.html'" />
    </noscript>

    <!-- Footer START -->
    <?php renderFooter(); ?>
    <!-- Footer END -->

    <!-- Javascript START -->
    <?php renderJavascriptAssets(); ?>
    <!-- Javascript END -->

</body>

</html>

<?php

/**
 * Render footer with copyright information
 */
function renderFooter()
{
    // Uncomment to show copyright footer
    /*
    echo '<footer class="page-footer">
            <div class="container">
                <div class="row">
                    <br/>
                </div>
            </div>
            <div class="footer-copyright blue-grey darken-1 white-text">
                <div class="container" id="footer">'
                    . getCopyrightText() .
                '</div>
            </div>
          </footer>';
    */
}

/**
 * Get copyright text from database
 */
function getCopyrightText()
{
    global $config;

    try {
        $query = mysqli_query($config, "SELECT nama FROM tbl_instansi LIMIT 1");
        if ($query && $data = mysqli_fetch_assoc($query)) {
            return '<span class="white-text copyright-date">&copy; ' . date("Y") . ' ' .
                htmlspecialchars($data['nama'], ENT_QUOTES, 'UTF-8') . '</span>';
        }
    } catch (Exception $e) {
        error_log("Copyright query error: " . $e->getMessage());
    }

    // Fallback copyright text
    return '<span class="white-text copyright-date">&copy; ' . date("Y") . ' Document Management System</span>';
}

/**
 * Render all JavaScript assets and initialization
 */
function renderJavascriptAssets()
{
    $jsConfig = getJavascriptConfig();

    renderJavascriptFiles($jsConfig['files']);
    renderJavascriptInitialization($jsConfig['options']);
}

/**
 * Get JavaScript configuration
 */
function getJavascriptConfig()
{
    return [
        'files' => [
            'asset/js/jquery-2.1.1.min.js',
            'asset/js/materialize.min.js',
            'asset/js/bootstrap.min.js',
            'asset/js/jquery.autocomplete.min.js',
            'asset/js/pace.min.js'
        ],
        'options' => [
            'pace' => ['ajax' => false],
            'sidenav' => [
                'menuWidth' => 240,
                'edge' => 'left',
                'closeOnClick' => true
            ],
            'datepicker' => [
                'selectMonths' => true,
                'selectYears' => 10,
                'format' => 'yyyy-mm-dd'
            ],
            'tooltip' => ['delay' => 10],
            'autocomplete' => [
                'serviceUrl' => 'kode.php',
                'dataType' => 'JSON'
            ],
            'alert' => [
                'delay' => 5000,
                'effect' => 'fadeOut',
                'speed' => 'slow'
            ]
        ]
    ];
}

/**
 * Render JavaScript file tags
 */
function renderJavascriptFiles($files)
{
    foreach ($files as $file) {
        $safeFile = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
        echo '<script type="text/javascript" src="' . $safeFile . '"></script>' . "\n";
    }
}

/**
 * Render JavaScript initialization code
 */
function renderJavascriptInitialization($options)
{
    ?>
    <script data-pace-options='<?php echo json_encode($options['pace']); ?>'></script>
    <script type="text/javascript">
        $(document).ready(function () {
            initializeApplication();
        });

        /**
         * Initialize all application components
         */
        function initializeApplication() {
            try {
                initializeDropdowns();
                initializeSidenav();
                initializeDatepickers();
                initializeTextareas();
                initializeSelects();
                initializeTooltips();
                initializeAutocomplete();
                initializeAlerts();
                initializeModals();
            } catch (error) {
                console.error('Application initialization error:', error);
            }
        }

        /**
         * Initialize dropdown components
         */
        function initializeDropdowns() {
            $(".dropdown-button").dropdown({
                hover: false
            });
        }

        /**
         * Initialize mobile sidenav
         */
        function initializeSidenav() {
            $('.button-collapse').sideNav(<?php echo json_encode($options['sidenav']); ?>);
        }

        /**
         * Initialize datepicker components
         */
        function initializeDatepickers() {
            $('#tgl_surat, #batas_waktu, #dari_tanggal, #sampai_tanggal').pickadate(<?php echo json_encode($options['datepicker']); ?>);
        }

        /**
         * Initialize textarea auto-resize
         */
        function initializeTextareas() {
            $('#isi_ringkas').val('').trigger('autoresize');
        }

        /**
         * Initialize select dropdowns
         */
        function initializeSelects() {
            $('select').material_select();
        }

        /**
         * Initialize tooltips
         */
        function initializeTooltips() {
            $('.tooltipped').tooltip(<?php echo json_encode($options['tooltip']); ?>);
        }

        /**
         * Initialize autocomplete
         */
        function initializeAutocomplete() {
            $("#kode").autocomplete({
                serviceUrl: "<?php echo $options['autocomplete']['serviceUrl']; ?>",
                dataType: "<?php echo $options['autocomplete']['dataType']; ?>",
                onSelect: function (suggestion) {
                    $("#kode").val(suggestion.kode);
                },
                onInvalidateSelection: function () {
                    console.warn('Invalid selection made in autocomplete');
                }
            });
        }

        /**
         * Initialize alert messages
         */
        function initializeAlerts() {
            $("#alert-message").alert().delay(<?php echo $options['alert']['delay']; ?>).<?php echo $options['alert']['effect']; ?>('<?php echo $options['alert']['speed']; ?>');
        }

        /**
         * Initialize modal dialogs
         */
        function initializeModals() {
            $('.modal-trigger').leanModal();
        }

        /**
         * Global error handler for JavaScript
         */
        window.addEventListener('error', function (e) {
            console.error('Global JavaScript error:', e.error);
        });
    </script>
    <?php
}
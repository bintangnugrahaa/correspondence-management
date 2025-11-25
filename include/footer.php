<?php
// Check session
if (empty($_SESSION['admin'])) {
    header("Location: ../");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<!-- Body START -->

<body class="bg pace-done">

    <noscript>
        <meta http-equiv="refresh" content="0;URL='./enable-javascript.html'" />
    </noscript>

    <!-- Footer START -->
    <!-- 
    Uncomment this section to show copyright footer
    <footer class="page-footer">
        <div class="container">
            <div class="row">
                <br/>
            </div>
        </div>
        <div class="footer-copyright blue-grey darken-1 white-text">
            <div class="container" id="footer">
                <?php renderCopyright(); ?>
            </div>
        </div>
    </footer>
    -->
    <!-- Footer END -->

    <!-- Javascript START -->
    <?php includeJavascript(); ?>
    <!-- Javascript END -->

</body>

</html>

<?php

/**
 * Render copyright information from database
 */
function renderCopyright()
{
    global $config;

    $query = mysqli_query($config, "SELECT nama FROM tbl_instansi LIMIT 1");
    if ($query && $data = mysqli_fetch_array($query)) {
        echo '<span class="white-text copyright-date">&copy; ' . date("Y") . ' ' .
            htmlspecialchars($data['nama']) . '</span>';
    }
}

/**
 * Include all javascript files and initialization
 */
function includeJavascript()
{
    $jsFiles = [
        'asset/js/jquery-2.1.1.min.js',
        'asset/js/materialize.min.js',
        'asset/js/bootstrap.min.js',
        'asset/js/jquery.autocomplete.min.js',
        'asset/js/pace.min.js'
    ];

    foreach ($jsFiles as $file) {
        echo '<script type="text/javascript" src="' . htmlspecialchars($file) . '"></script>' . "\n";
    }
    ?>

    <script data-pace-options='{ "ajax": false }'></script>
    <script type="text/javascript">
        $(document).ready(function () {
            initializeComponents();
        });

        /**
         * Initialize all JavaScript components
         */
        function initializeComponents() {
            // Dropdown initialization
            $(".dropdown-button").dropdown({ hover: false });

            // Mobile sidenav initialization
            $('.button-collapse').sideNav({
                menuWidth: 240,
                edge: 'left',
                closeOnClick: true
            });

            // Datepicker initialization
            $('#tgl_surat, #batas_waktu, #dari_tanggal, #sampai_tanggal').pickadate({
                selectMonths: true,
                selectYears: 10,
                format: "yyyy-mm-dd"
            });

            // Textarea auto-resize
            $('#isi_ringkas').val('').trigger('autoresize');

            // Select dropdown and tooltip initialization
            $('select').material_select();
            $('.tooltipped').tooltip({ delay: 10 });

            // Autocomplete initialization
            $("#kode").autocomplete({
                serviceUrl: "kode.php",
                dataType: "JSON",
                onSelect: function (suggestion) {
                    $("#kode").val(suggestion.kode);
                }
            });

            // Alert message auto-hide
            $("#alert-message").alert().delay(5000).fadeOut('slow');

            // Modal initialization
            $('.modal-trigger').leanModal();
        }
    </script>
    <?php
}
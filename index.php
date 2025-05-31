<?php
// load the config file
require_once('./config.php');

// set the page title
define("TITLE", ( SITE_NAME." - URL Shortener" ));

// Helper function to sanitize input
function sanitize($data) {
    return htmlspecialchars(trim($data));
}

// Database connection
$conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Process URL redirection
if (isset($_GET['code']) && strlen($_GET['code']) > 0) {
    // Set basic header data and redirect the user to the URL
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Cache-Control: no-cache");
    header("Pragma: no-cache");

    $code = sanitize($_GET['code']);

    // Validate the 'code' against the database
    $query = mysqli_query($conn, "SELECT id, url, hit_count FROM short_links WHERE code='$code'");
    if (mysqli_num_rows($query) == 1) {
        // Retrieve the data from the database
        $data = mysqli_fetch_assoc($query);

        $newHitCount = $data['hit_count'] + 1;

        // Update the hit counter in the database
        mysqli_query($conn, "UPDATE short_links SET hit_count='$newHitCount' WHERE id='".( $data['id'] )."'");

        /* ADD ANY EXTRA STUFF HERE, IF DESIRED (e.g., logging, analytics) */

        // Actually redirect the user to their endpoint
        header("Location: " . $data['url'], true, 301); // Use 301 for permanent redirect
        exit(); // Ensure script stops after redirection
    } else {
        $error = '<div class="alert alert-danger mt-3" role="alert">Hmm... unable to find that URL.</div>';
    }
}

// Function to generate a unique short code
function generateShortCode($length = 6) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    $max = strlen($characters) - 1;
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, $max)];
    }
    return $code;
}

// Handle form submission for shortening URLs
if (isset($_POST['url']) && strlen($_POST['url']) > 0) {
    $longUrl = filter_var($_POST['url'], FILTER_VALIDATE_URL);
    if ($longUrl) {
        $longUrl = mysqli_real_escape_string($conn, $longUrl);

        // Check if the URL already exists
        $checkQuery = mysqli_query($conn, "SELECT code FROM short_links WHERE url='$longUrl'");
        if (mysqli_num_rows($checkQuery) > 0) {
            $existingData = mysqli_fetch_assoc($checkQuery);
            $shortenedUrl = SITE_ADDR . '/' . $existingData['code'];
            $message = '<div class="alert alert-info mt-3" role="alert">This URL has already been shortened: <a href="' . $shortenedUrl . '" target="_blank">' . $shortenedUrl . '</a></div>';
        } else {
            // Generate a unique short code
            $shortCode = generateShortCode();
            while (mysqli_num_rows(mysqli_query($conn, "SELECT code FROM short_links WHERE code='$shortCode'")) > 0) {
                $shortCode = generateShortCode(); // Regenerate if code exists
            }

            // Insert the new short link into the database
            $insertQuery = mysqli_query($conn, "INSERT INTO short_links (url, code, created_at) VALUES ('$longUrl', '$shortCode', NOW())");
            if ($insertQuery) {
                $shortenedUrl = SITE_ADDR . '/' . $shortCode;
                $message = '<div class="alert alert-success mt-3" role="alert">Your shortened URL: <a href="' . $shortenedUrl . '" target="_blank">' . $shortenedUrl . '</a> <button id="copyButton" class="btn btn-sm btn-outline-secondary ml-2" data-clipboard-text="' . $shortenedUrl . '">Copy</button></div>';
            } else {
                $message = '<div class="alert alert-danger mt-3" role="alert">Error shortening URL. Please try again.</div>';
            }
        }
    } else {
        $message = '<div class="alert alert-warning mt-3" role="alert">Please enter a valid URL.</div>';
    }
}

mysqli_close($conn);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Create a short URL, quick and free with fii.sh URL shortener. Written in PHP, fiish custom URL shortener is open source. Download the PHP source code on GitHub.">
    <meta name="author" content="nickfrosty, Nick Frostbutter">
    <title><?php echo defined("TITLE") ? TITLE : SITE_NAME; ?></title>

    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo SITE_LOGO; ?>">
    <link rel="icon" type="image/png" href="<?php echo SITE_LOGO; ?>">

    <link rel="stylesheet" href="<?php echo SITE_ADDR; ?>/assets/vendor/bootstrap/css/bootstrap.min.css">

    <style>
        <?php
            // load the 'main.css' stylesheet inline
            $mainCssPath = ABSPATH . '/assets/css/main.css';
            if (file_exists($mainCssPath)) {
                echo file_get_contents($mainCssPath);
            } else {
                // Fallback styles if main.css is not found
                echo "body { font-family: sans-serif; margin: 20px; background-color: #f8f9fa; }";
                echo ".logo { margin-right: 10px; vertical-align: middle; }";
                echo ".container { max-width: 800px; margin: 0 auto; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }";
                echo "h1, h2, h3 { color: #343a40; text-align: center; margin-bottom: 20px; }";
                echo ".card { border: none; border-radius: 8px; }";
                echo ".bg-orange-light { background-color: #ffe0b2; }";
                echo ".bg-orange-dark { background-color: #ffb347; color: white; }";
                echo ".form-control { margin-bottom: 15px; }";
                echo ".btn { border-radius: 5px; }";
                echo "#message { text-align: center; }";
                echo ".question { text-align: center; color: #6c757d; cursor: pointer; }";
                echo ".justify { text-align: justify; }";
                echo ".text-sm { font-size: 0.8rem; }";
            }
        ?>
    </style>
    <script>
        var SITE_ADDR = '<?php echo SITE_ADDR; ?>';

        window.onload = function() {
            $("#url").focus();

            $("#shortenForm").submit(function(event) {
                event.preventDefault(); // Prevent default form submission
                $("#copy").hide();
                $("#message").html(''); // Clear previous messages

                var url = $("#url").val();
                if ($.trim(url) !== '') {
                    $.post("./index.php", { // Submit back to the same index.php to handle form
                        url: url
                    }, function(data) {
                        $("#form-container").html(data); // Replace the form with the result
                        if ($("#copyButton").length) {
                            new ClipboardJS('#copyButton');
                            $("#copyButton").click(function(){
                                $("#message").html('<div class="alert alert-success mt-3" role="alert">Copied to clipboard!</div>');
                            });
                        }
                        $("#url").focus();
                    });
                } else {
                    $("#message").html('<div class="alert alert-warning mt-3" role="alert">Please enter a URL to shorten.</div>');
                }
                $("#url").focus();
            });

            $("#url").on('input', function(){
                $("#copy").hide();
                $("#message").html('let\'s do this thing'); // Reset default message
            });

            $(".question").click(function() {
                var $this = $(this);
                $this.fadeOut(function() {
                    $this.text("a fishhook!").fadeIn();
                });
            });
        };
    </script>
    <?php if (defined('GOOGLE_ANALYTICS') && GOOGLE_ANALYTICS !== '') { ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo GOOGLE_ANALYTICS; ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo GOOGLE_ANALYTICS; ?>');
        </script>
    <?php } ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
</head>
<body>
    <article class="container">
        <div class="text-center">
            <h1>
                <a href="<?php echo SITE_ADDR; ?>"><img class="logo" alt="fii.sh URL shortener" src="<?php echo SITE_LOGO; ?>" width="100" height="100"></img>
                    fii.sh - URL Shortener</a>
            </h1>
            <h2 class="h4">A free, open source, and custom URL shortener</h2>
        </div>

        <hr class="my-4" />

        <div class="row justify-content-center my-4">
            <div class="col-md-8 col-sm-10 col-xs-12 card shadow-sm p-5 bg-orange-light">
                <div id="form-container">
                    <form method="post" id="shortenForm">
                        <div class="input-group">
                            <input type="url" id="url" name="url" class="form-control" value="" placeholder="Enter a URL to shorten" tabindex="1" required />
                        </div>
                        <button type="submit" name="short" class="btn btn-lg btn-block w-100 bg-orange-dark mt-3" tabindex="2">Make Short!</button>
                    </form>
                    <div id="message" class="mt-3 text-center"><?php echo isset($error) ? $error : (isset($message) ? $message : "Let's do this thing!"); ?></div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 text-center">
                <p class="text-sm question ask">Psst... what do you call a fish with no eyes?</p>
            </div>
        </div>

        <hr class="my-5" />

        <div class="row">
            <div class="col-md-10 offset-md-1">
                <h3 class="pb-3">Hi, welcome to <a href="https://fii.sh">fii.sh</a></h3>
                <p class="justify">My hope is that fii.sh will one day become a more complete open source URL shortener. But for now, it has basic functionality and can create short URLs. So it does what it must. Still being open source of course!</p>
                <p class="justify">If you need to, you can always find me on <a href="https://twitter.com/nickfrosty">Twitter</a>. Otherwise, have a wonderful day! :)</p>
                <p class="text-right mx-4">&mdash; Nick (<a href="https://twitter.com/nickfrosty">@nickfrosty</a>, <a href="https://github.com/nickfrosty">GitHub</a>)</p>
            </div>
        </div>

        <hr class="my-4" />
        <div class="row mb-2 text-sm">
            <div class="col-md-6 col-xs-12">
                &copy; <?php echo date("Y"); ?> <a href="https://frostbutter.com" target="_blank">nick frostbutter</a>
            </div>
            <div class="col-md-6 col-xs-12 text-md-right">
                <a href="https://10h.dev/php/make-a-custom-url-shortener-in-php/">view tutorial</a> &bull;
                <a href="https://github.com/nickfrosty/url-shortener">source code</a>
            </div>
        </div>

    </article>

    <script src="<?php echo SITE_ADDR; ?>/assets/vendor/jquery/jquery-3.5.1.min.js"></script>
    <script src="<?php echo SITE_ADDR; ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
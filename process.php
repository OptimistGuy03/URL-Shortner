<?php
require_once('./config.php');

// Helper function to sanitize input
function sanitize($data) {
    return htmlspecialchars(trim($data));
}

// Helper function to validate URL format
function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
}

// Helper function to generate a unique short code
function generate_code($length = 6) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    $max = strlen($characters) - 1;
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, $max)];
    }
    return $code;
}

// Set the appropriate Content-Type header for JSON responses
header('Content-Type: application/json');

$response = array('status' => 'error', 'message' => '');

// Verify the URL data has been posted to this script
if (isset($_POST['url']) && !empty($_POST['url'])) {
    $url = sanitize($_POST['url']);

    // Verify that a URL was provided and that it is a valid URL
    if (validate_url($url)) {
        // Create a connection to the database
        $conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

        if (!$conn) {
            $response['message'] = 'Database connection error: ' . mysqli_connect_error();
            echo json_encode($response);
            exit();
        }

        $code = generate_code();

        // Ensure the 'code' is not already taken in the database. If so, generate another
        $query = mysqli_query($conn, "SELECT code FROM short_links WHERE code='{$code}'");
        while (mysqli_num_rows($query) > 0) {
            $code = generate_code();
            $query = mysqli_query($conn, "SELECT code FROM short_links WHERE code='{$code}'");
        }

        // Create all the variables to save in the database
        $id = null; // Let the database handle auto-increment
        $timestamp = time();
        $count = 0;

        // Use prepared statements to prevent SQL injection
        $stmt = mysqli_prepare($conn, "INSERT INTO short_links (id, code, url, hit_count, created_at) VALUES (?, ?, ?, ?, FROM_UNIXTIME(?))");
        mysqli_stmt_bind_param($stmt, "sssis", $id, $code, $url, $count, $timestamp);

        // Add the new code into the database
        if (mysqli_stmt_execute($stmt)) {
            // Verify that the new record was created
            $query = mysqli_query($conn, "SELECT code FROM short_links WHERE created_at=FROM_UNIXTIME('$timestamp') AND code='$code'");
            if (mysqli_num_rows($query) > 0) {
                /* SUCCESS POINT */
                $response['status'] = 'success';
                $response['shortenedUrl'] = SITE_ADDR . '/' . $code;
            } else {
                $response['message'] = 'Unable to shorten your URL due to a database error.';
            }
        } else {
            $response['message'] = 'Error inserting data: ' . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt);
        mysqli_close($conn);
    } else {
        $response['message'] = 'Please enter a valid URL.';
    }
} else {
    $response['message'] = 'Hmm... no URL was found.';
}

echo json_encode($response);
?>
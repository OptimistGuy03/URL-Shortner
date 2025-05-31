<?php

// Provide SQL injection protection by sanitizing any variables
function sanitize($var)
{
    $var = trim($var);
    $var = filter_var($var, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    return $var;
}

// Function for creating the random string for the unique URL
function generate_code()
{
    // Create the charset for the codes and jumble it all up
    $charset = str_shuffle(CHARSET);
    $code = substr($charset, 0, URL_LENGTH);

    // Verify the code is not taken
    while (count_urls($code) > 0) {
        $charset = str_shuffle(CHARSET); // Re-shuffle for a new code
        $code = substr($charset, 0, URL_LENGTH);
    }

    // Return a randomized code of the desired length
    return $code;
}

// Function to count the total number of short URLs saved on the site
function count_urls($code = '')
{
    // Build the extra query string to search for a code in the database
    $extra_query = $code != '' ? " WHERE code = ?" : "";

    // Connect to the database
    $conn = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

    if (!$conn) {
        // Handle database connection error appropriately (log, display message, etc.)
        return 0;
    }

    // Count how many total shortened URLs have been made in the database and return it
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM short_links" . $extra_query);

    if ($code != '') {
        mysqli_stmt_bind_param($stmt, "s", $code);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    return (int) $count;
}

// Function to perform all the validation needed for the URLs provided
function validate_url($url)
{
    // Make sure the user isn't trying to shorten one of our URLs
    if (strpos($url, SITE_ADDR) !== 0) {
        return filter_var($url, FILTER_VALIDATE_URL);
    } else {
        return false;
    }
}
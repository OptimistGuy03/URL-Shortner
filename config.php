<?php

/**
 * Configuration file for the fii.sh URL shortener application.
 */

// Enable or disable debugging mode.  When true, PHP error logging is enabled.
define("DEBUG", false);

// Define global application settings.
define("SITE_NAME", "fii.sh");             // The name of the website.
define("SITE_LOGO", "./assets/img/logo.png"); // The path to the site's logo image.

// Google Analytics tracking code (G4 format only).  Leave empty to disable.
define("GOOGLE_ANALYTICS", "");

// The base URL where the application is hosted.  Include the protocol (http/https).
define("SITE_ADDR", "http://localhost/fii.sh");

// Database connection settings.
define("DB_SERVER", "localhost"); // The hostname or IP address of the database server.
define("DB_USER", "root");        // The database username.
define("DB_PASS", "");          // The database password.
define("DB_NAME", "fiish");       // The name of the database.

/*
 * URL shortener specific settings.
 */
define("CHARSET", "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"); // Characters allowed in short URLs.
define("URL_LENGTH", 7);       // The length of the generated short URL code.
define("URL_BASE", SITE_ADDR); // The base URL used for shortened links.  Should match SITE_ADDR.

// Error logging configuration based on the DEBUG setting.
ini_set("log_errors", DEBUG); // Sets the "log_errors" directive in php.ini

// Define the absolute path to the application's root directory.
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/'); // Ensures consistent path handling.
}

// Load the required custom functions.  Assumes functions.php is in the same directory.
require_once(ABSPATH . 'functions.php');
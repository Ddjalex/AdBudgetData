<?php
// Router script for PHP built-in server

// Get the requested file
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

// If it's a real file and not .php, serve it directly
if (is_file($file) && !str_ends_with($file, '.php')) {
    return false;
}

// Otherwise, let PHP process it normally
return false;
?>

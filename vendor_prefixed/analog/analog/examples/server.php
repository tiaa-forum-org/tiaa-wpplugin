<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

/**
 * This works with test_post.php to verify sent data.
 * Run me via:
 *
 *     php -S localhost:8080 server.php
 *
 * Note: Requires PHP 5.4+
 */
file_put_contents ('php://stdout', 'Logged: ' . join (' - ', $_POST) . "\n");

?>
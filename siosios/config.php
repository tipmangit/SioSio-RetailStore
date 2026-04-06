<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// <-- ADD THIS LINE to set the correct PHP timezone
date_default_timezone_set('Asia/Manila');

$con = mysqli_connect("localhost","root","","siosio_store1") or die("Couldn't connect");

// <-- ADD THIS LINE to set the correct database connection timezone
mysqli_query($con, "SET time_zone = '+08:00'");

?>
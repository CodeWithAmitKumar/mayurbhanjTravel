<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "mbjtravel";

$conn = mysqli_connect($host, $user, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
<?php
$host = "localhost";                 // Nama host
$username = "root";                      // Username database
$password = "";                          // Password database
$database = "correspondence-management"; // Nama database

// BUAT KONEKSI
$config = mysqli_connect($host, $username, $password, $database);

// CEK KONEKSI
if (!$config) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

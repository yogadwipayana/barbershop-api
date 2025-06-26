<?php
    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "barbershop";
    
    $db = new mysqli($host, $username, $password, $database);

    if ($db->connect_error) {
        die("Connection failed: " . $db->connect_error);
    }
?>
<?php
    $host = "db.yogadwipayana.com";
    $username = "barber_user";
    $password = "";
    $database = "barbershop";
    
    $db = new mysqli($host, $username, $password, $database);

    if ($db->connect_error) {
        die("Connection failed: " . $db->connect_error);
    }
?>
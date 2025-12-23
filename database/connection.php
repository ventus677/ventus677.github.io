<?php
    $servername = 'localhost';
    $Email = 'root';
    $Password = '';




    // connecting to the database.
    try{
        $conn = new PDO("mysql:host=$servername;dbname= inventory",$Email, $Password);
        // set the PDO error mode to exception.
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo 'Connected Successfully.';

    } catch(PDOException $e){ // Corrected catch block
        $error_message = $e->getMessage();

    }


?>
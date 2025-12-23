<?php
session_start();
session_unset(); // I-unset lahat ng session variables
session_destroy(); // I-destroy ang session

header('Location: ../E-commerce/customer_auth.php'); // I-redirect pabalik sa customer login page
exit;
?>

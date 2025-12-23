<?php
    //Start the session.
    session_start();
    $users = include('database/show_users.php');
    $user = $_SESSION['user'];
     if(!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit; // Important: Exit after redirect
  
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Keepkit</title>
    <link rel="stylesheet" href="home.css"/>
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
                        .user-name {
    background-color: #343a40;
    padding: 15px 10px;
    border-bottom: 1px solid #444;
    margin-bottom: 20px;
    text-align: left;
    display: flex;
    align-items: center;
    cursor: pointer;
    border-radius: 5px;
    transition: background-color 0.2s ease;
}

.user-name:hover {
    background-color: #495057;
}

.user-name a.profile-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: #f8f9fa;
    font-size: 1.15rem;
    font-weight: 600;
    width: 100%;
}

.user-name img.profile-pic-icon {
    height: 35px;
    width: 35px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 12px;
    border: 2px solid #ffc107;
    box-shadow: 0 0 5px rgba(0,0,0,0.3);
}

.user-name span.first-name,
.user-name span.last-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
    
    </style>
   
</head>

<body>
    <header>
        <a href="index.php" id="navbar__logo">
            <img src="images/KeepkitSubmark.png" alt="KeepkitSubmark" height="50px">
            <h3>&nbsp;&nbsp;Keepkit</h3>
        </a>
        <div class="right-element">
            <a href="database/logout.php" ><img src= "images/iconLogout.png"></a>
        </div>
    </header>

    <div class="page" id="page">
        <?php include('sidebar.php'); ?>

        <main class="main">
            <section id="homePage" class="active">
                <h1>Suppliers</h1>
                <p>View your suppliers information or add new suppliers!</p>
                <div class="cards">
                    <a class="card" id="cardViewSupp" data-page="view_suppliers.php">
                        <img src="images/iconTables.png" alt="Home Icon" class="icon">
                        <span>View Suppliers</span>
                    </a>
                    <a class="card" id="cardAddSupp" data-page="add_suppliers.php">
                        <img src="images/iconOrders.png" alt="Products Icon" class="icon">
                        <span>Add Suppliers</span>
                    </a>
                </div>
            </section>
        </main>
    </div>

    <script src="script.js"></script>

</body>
</html>
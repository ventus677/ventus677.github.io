<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keepkit</title>
    <link rel="stylesheet" href="landingpage.css"/>
    <link rel="icon" type="image/png" href="images/KeepkitFavicon.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
</head>
<style>
.section {
  text-align: center;
  padding: 40px 20px;
}

.logo {
  width: 80px;
  height: auto;
}

h1 {
  margin-top: 10px;
  font-size: 50px;
}

.subtitle {
  margin-top: 10px;
  font-size: 1rem;
}

/* Feature Section Styling */
.feature-section {
    padding: 40px 20px;
    text-align: center;
}

.feature {
    display: flex;
    align-items: center;
    justify-content: center; /* Center content within the feature */
    padding: 30px 0; /* Adjust padding as needed */
    border-bottom: 1px solid #eee; /* Separator between features */
    max-width: 900px; /* Limit width of features */
    margin: 0 auto; /* Center features on the page */
}

.feature:last-child {
    border-bottom: none; /* No border for the last feature */
}

.feature-content {
    display: flex;
    align-items: center;
    width: 100%;
}

.feature-text {
    flex: 1; /* Allows text to take available space */
    padding-right: 50px; /* Space between text and image */
    text-align: left; /* Align text to the left */
}

.feature-text h2 {
    color: #333;
    margin-bottom: 10px;
    font-size: 2em; /* Adjust font size */
    text-align: center;
}

.feature-text p {
    line-height: 1.6;
    color: #666;
    font-size: 1.1em; /* Adjust font size */
}

.feature-image-container {
    width: 500px; /* Fixed width for the image container */
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #ddd; /* Border for the placeholder */
    overflow: hidden; /* Hide overflowing parts of the image */
}

.feature-image-container img {
    width: 100%; /* Image fills container */
    height: 100%; /* Image fills container */
    object-fit: cover; /* Ensures image covers the area, can be 'contain' if you prefer */
}


.section-heading {
  font-weight: bold;
  letter-spacing: 4px;
  margin-bottom: 20px;
}

/* WHO WE ARE Section Styling */
.who-content {
    padding: 60px 20px;
    display: flex;
    justify-content: center; /* Ito ang nagsisiguro na ang content-wrapper ay nakasentro */
    align-items: center;
    min-height: 80vh;
}

.content-wrapper {
    display: flex;
    align-items: center;
    max-width: 1200px; /* Panatilihin ang max-width para hindi masyadong lumaki */
    width: 100%;
    justify-content: center; /* Ito ang magse-center ng text at image block sa loob ng wrapper */
    gap: 50px; /* Space between text and image */
}

.text-content {
    flex: 1; /* Binibigyan ang text ng flex-grow */
    text-align: left;
    max-width: 500px; /* Maaari mong limitahan ang lapad ng text para mas basahin */
}

.text-content h2 {
    letter-spacing: 2px;
    font-size: 2.5em;
    color: #333;
    text-transform: uppercase;
    margin-bottom: 20px;
}

.text-content p {
    font-size: 1.1em;
    margin-bottom: 15px;
    line-height: 1.6;
    color: #666;
    text-align: center;
}

.teamPhoto {
    position: relative;
    width: 400px;
    flex-shrink: 0;
    
}

.teamPhoto img {
    object-fit: cover;
}


/* Responsive adjustments */
@media (max-width: 992px) {
    
    /* Features Section - Ginaya ang Who We Are responsiveness */
    .feature-content {
        flex-direction: column; /* Stack items vertically */
        text-align: center; /* Center text when stacked */
        gap: 20px; /* Add space between text and image */
    }

    .feature-text {
        padding-right: 0; /* Remove right padding when stacked */
        max-width: 100%; /* Allow text to take full width */
    }

    .feature-text h2 {
        font-size: 1.8em; /* Adjust font size for smaller screens */
    }

    .feature-text p {
        font-size: 1em; /* Adjust font size for smaller screens */
    }

    .feature-image-container {
        width: 80%; /* Make image container responsive */
        max-width: 400px; /* Limit max width */
    }

    /* WHO WE ARE Section */
    .content-wrapper {
        flex-direction: column;
        text-align: center;
        gap: 30px;
        justify-content: center; /* Panatilihing nakasentro kahit naka-column */
    }

    .text-content {
        padding-right: 0;
        max-width: 100%; /* Tanggalin ang max-width sa smaller screens */
    }

    .text-content h2 {
        font-size: 2em;
    }

    .text-content p {
        font-size: 0.95em;
    }

    .teamPhoto {
        width: 80%;
        max-width: 400px;
        margin-top: 20px;
    }
}

@media (max-width: 600px) {
    /* Main Section */
    .header__tagline h1 {
        font-size: 2em;
    }

    .header__tagline h3 {
        font-size: 0.9em;
    }

    .header__tagline a.button {
        padding: 10px 20px;
        font-size: 0.9em;
    }

    .features__item img {
        width: 90%;
    }

    /* Features Section */
    .feature-text h2 {
        font-size: 1.5em;
    }

    .feature-text p {
        font-size: 0.9em; /* Further reduce font size for very small screens */
    }

    .feature-image-container {
        height: 180px; /* Further reduce height for very small screens */
    }

    /* WHO WE ARE Section */
    .who-content {
        padding: 40px 15px;
    }

    .text-content h2 {
        text-align: center;
        font-size: 1.8em;
    }

    .text-content p {
        font-size: 0.85em;
    }

    .teamPhoto {
        width: 90%;
    }
}

</style>

<body>

<!-- NAVIGATION BAR -->

    <nav class="navbar">
        <div class="navbar__container">
            <a href="index.php" id="navbar__logo">
                <img src="images/KeepkitSubmark.png" alt="KeepkitSubmark" height="50px">
                &nbsp;&nbsp;<h3>Keepkit</h3>
            </a>
            <div class="navbar__toggle" id="mobile-menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
            <ul class="navbar__menu">
                <li class="navbar__item">
                    <a href="#KeepkitCenter" class="navbar__links">Features</a>
                </li>
                <li class="navbar__item">
                    <a href="#team" class="navbar__links">About</a>
                </li>
                <li class="navbar__btn">
                    <a href="login.php" class="button">Sign In</a>
                </li>
            </ul>
        </div>
    </nav>

<!-- HEADER -->

    <div class="main">
        <div class="main__container">
            <div class="header__tagline">
                <h1>Keep your kit <br> with <i style="font-family:serif;">Keepkit!</i></h1>
                <br>
                <h3>An inventory management system for cosmetic products.</h3>
                <br>
                <a href="signUp.php">Get Started</a>
            </div>

                <div class="features">
                <img id="InvertedTriangle" src="images/InvertedTriangle.png" alt="Background">
                    <ul class="features__list">
                        <li class="features__item">
                            <img src="images/Feature1.png" alt="Feature 1">
                        </li>
                        <li class="features__item">
                            <img src="images/Feature2.png" alt="Feature 3">
                        </li>
                        <li class="features__item">
                            <img src="images/Feature3.png" alt="Feature 4">
                        </li>
                    </ul>
                </div>
                <img id="KeepkitCenter" src="images/KeepkitLogo.png" alt="Keepkit Logo">

        </div>
    </div>
    

<!-- WHAT WE OFFER -->
    <section class="feature-section" id="features-section">
    <h1>Features</h1>
    <br><br>

    <div class="feature">
      <div class="feature-content">
        <div class="feature-text">
          <h2>Inventory Analytics</h2>
          <p>Understand your stock at a glance. With visual summaries and quantity updates, you can have smarter restocking decisions and reduce excess inventory.</p>
        </div>
        <div class="feature-image-container">
            <img src="images/Feature1.png" alt="Feature Image 1" />
        </div>
      </div>
    </div><br><br><br><br>

    <div class="feature">
      <div class="feature-content">
        <div class="feature-text">
          <h2>Order and Sales Tracker</h2>
          <p>Keep a clear record of every transaction, easily log incoming supplies and outgoing products: whether from orders or sales. Stay organized with timestamped records and track your inventory flow without relying on spreadsheets or physical log.</p>
        </div>
        <div class="feature-image-container">
            <img src="images/Feature2.png" alt="Feature Image 2" />
        </div>
      </div>
    </div><br><br><br><br>

    <div class="feature">
      <div class="feature-content">
        <div class="feature-text">
          <h2>Quick Search and Sort</h2>
          <p>Find exactly what you need, fast. No more scrolling through long list, quickly locate any product using keyword search and sort your tables by name, category, or quantity.</p>
        </div>
        <div class="feature-image-container">
            <img src="images/Feature3.png" alt="Feature Image 3" />
        </div>
      </div>
    </div><br><br><br><br>
  </section>


<section class="content" id = "What We Offer">
      <img id="TeamKeepkitCenter" src="images/TeamIcon.png" alt="Keepkit Team">
        <h1 class="about-title">About Us</h1>
        <p class="about-subtitle">Team Keepkit</p>
        <br>


        <div class="what-content">
            <h2 class="section-heading">WHAT WE OFFER</h2>
            <p class="section-text">
                Easy product information viewing, stock level management, sales and order tracking, and inventory summaries and analytics.
            </p>
        </div>

<div class="who-content" id="who">
          <div class="content-wrapper">
            <div class="text-content">
                <h2>WHO WE ARE</h2>
                <p>We are the Group 3 from BS Computer Science 2A, <br>and we developed an Inventory Management System for<br> small to medium-sized cosmetics businesses.
                </p>
                <p> Our goal is to simplify inventory tracking by providing an organized, easy to use platform<br> that helps users monitor their products and transactions more effieciently.
                </p>
            </div>
            <div class="teamPhoto">
                <img src="images/TEAM.png" alt="Team Photo">
            </div>
          </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="text-center text-sm text-gray-500 py-6">
      2025 KeepKit | Created for educational use.
    </footer>

    <script src="script.js"></script>
    <script src="menu.js"></script>

</body>
</html>
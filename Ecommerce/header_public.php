<?php
// Tiyakin na ang session ay base sa 'user' variable na galing sa `users` table
$is_logged_in = isset($_SESSION['user']) && isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id']);

if ($is_logged_in) {
    $session_data = $_SESSION['user'];
    $profile_link_url = 'customer_profile.php'; 
    $customer_first_name = $session_data['first_name'] ?? 'User';
    $customer_profile_pic = $session_data['profile_picture'] ?? 'iconUser.png';
} else {
    $customer_first_name = 'Guest';
    $customer_profile_pic = 'iconUser.png';
    $profile_link_url = '';
}

$profile_pic_path = '../images/iconUser.png'; 
if ($is_logged_in && !empty($customer_profile_pic) && file_exists('../uploads/profiles/' . $customer_profile_pic)) {
    $profile_pic_path = '../uploads/profiles/' . htmlspecialchars($customer_profile_pic);
}

$total_items_in_cart_for_header = $_SESSION['total_items_in_cart'] ?? 0;
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $page_title ?? 'Keepkit Shop'; ?></title> <link rel="icon" type="image/png" href="../images/KeepkitFavicon.png"/>
        <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="header_public.css"/>
        <link rel="stylesheet" href="../landingpage.css"/>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <style>
            body {
                font-family: 'Inter', sans-serif;
                margin: 0;
                background-color: #f0f2f5; /* Light grey background */
                padding-top: 70px; /* Space for fixed header */
                color: #333;
            }

            header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #fafafa;
                filter: drop-shadow(0px 1px 5px #151515);
                position: fixed;
                width: 100%;
                top: 0;
                left: 0;
                z-index: 100;
                height: 60px;
                box-sizing: border-box; /* Isama ang padding sa height */
            }

            /* ANG LAHAT NG NASA LOOB NG HEADER AY DAPAT DIN MANATILI SA ORIGINAL COLORS NITO */
            
            #navbar__logo {
                color: #151515;
                font-weight: 800;  
                display: flex;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                padding: 25px;
            }
            #navbar__logo img {
                margin-right: 10px;
                height: 50px; /* Ayusin ang height kung kinakailangan */
            }

            .search-container {
                display: flex;
                height: 40px;
                flex-grow: 1; /* Payagan ang search bar na sakupin ang available na espasyo */
                max-width: 600px; /* Max width para sa search bar */
                margin: 0 20px;
                background-color: white; /* MANANATILING WHITE */
                border: 0.5px solid #aaa;
                border-radius: 50px;
                overflow: hidden;
                position: relative; /* Idinagdag para sa absolute positioning ng mga suggestion */
            }
            .search-container input[type="search"] {
                border: none;
                padding: 8px 15px;
                font-size: 1rem;
                flex-grow: 1;
                outline: none;
                color: #333; /* Default text color */
            }
            .search-container button {
                background-color: #f0f0f0;
                border: none;
                padding: 8px 15px;
                cursor: pointer;
                color: #555;
                font-size: 1rem;
            }
            .search-container button:hover {
                background-color: #e0e0e0;
            }

            .right-element {
                display: flex;
                align-items: center;
                padding: 25px;
                gap: 15px;
                color: #151515;
            }

            .header-btn {
                background-color: #151515;
                color: #fafafa;
                border: 1px solid #151515;
                padding: 8px 15px;
                border-radius: 5px;
                text-decoration: none;
            }
            .header-btn:hover {
                background: linear-gradient(to left, #a93131, #151515);
            }
            .header-btn.primary {
                background-color: #151515;
                color: #fafafa;
            }
            .header-btn.primary:hover {
                background: linear-gradient(to left, #a93131, #151515);
            }

            /* User dropdown styles */
            .user-info-dropdown {
                position: relative;
                display: inline-block;
                cursor: pointer;
            }
            .profile-link-header {
                display: flex;
                align-items: center;
                text-decoration: none;
                color: white;
            }
            .profile-pic-icon-header {
                width: 35px;
                height: 35px;
                border-radius: 50%;
                object-fit: cover;
                margin-right: 8px;
                border: 2px solid white;
            }
            .header-user-name {
                font-weight: bold;
            }
            .dropdown-content {
                display: none;
                position: absolute;
                background-color: #f9f9f9;
                min-width: 160px;
                box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                z-index: 1;
                right: 0; /* I-align ang dropdown sa kanan */
                top: 40px; /* Posisyon sa ibaba ng impormasyon ng user */
                border-radius: 5px;
                overflow: hidden;
            }
            .dropdown-content a {
                color: black;
                padding: 12px 16px;
                text-decoration: none;
                display: block;
                text-align: left;
            }
            .dropdown-content a:hover {
                background-color: #ddd;
            }
            .user-info-dropdown:hover .dropdown-content {
                display: block;
            }

            /* Cart Icon */
            .cart-icon-container {
                position: relative;
                margin-left: 15px;
                display: flex;
                align-items: center;
            }
            .cart-icon-container i {
                font-size: 1.8em;
                color: #284752;
            }
            .cart-count-badge {
                background-color: #f44336; /* Pula para sa badge */
                color: white;
                border-radius: 50%;
                padding: 2px 7px;
                font-size: 0.7em;
                position: absolute;
                top: -8px;
                right: -15px;
                min-width: 15px;
                text-align: center;
                line-height: 15px;
            }

            /* Responsive adjustments for header */
            @media (max-width: 768px) {

                #navbar__logo h3{
                    display: none;
                }

                .header-user-name{
                    display: none;
                }
            }

            /* NEW CSS for search suggestions dropdown - Minimal and Neutral */
            .search-suggestions {
                position: absolute; /* Posisyon na relative sa .search-container */
                top: 100%; /* Ilagay sa ibaba ng search input */
                left: 0;
                right: 0;
                background-color: white;
                border: 1px solid #e0e0e0; /* Light gray border */
                border-top: none; /* Walang top border dahil nasa ibaba ng input */
                border-radius: 0 0 5px 5px; /* Mga bilog na sulok sa ibaba lang */
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                max-height: 250px; /* Limitahan ang taas at paganahin ang pag-scroll */
                overflow-y: auto;
                z-index: 1000; /* Siguraduhin na ito ay nasa itaas ng ibang nilalaman */
                display: none; /* Nakatago bilang default */
            }

            .search-suggestion-item {
                padding: 10px 15px;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 10px;
                border-bottom: 1px solid #f0f0f0; /* Napakagaan na border sa pagitan ng mga item */
                color: #333; /* Madilim na text para sa readability */
            }

            .search-suggestion-item:last-child {
                border-bottom: none; /* Walang border para sa huling item */
            }

            .search-suggestion-item:hover {
                background-color: #f8f8f8; /* Napakagaan na hover effect */
            }

            .search-suggestion-item img {
                width: 40px;
                height: 40px;
                object-fit: cover;
                border-radius: 4px; /* Bahagyang bilog na mga sulok ng imahe */
                flex-shrink: 0; /* Pigilan ang imahe na lumiit */
            }

            .search-suggestion-item span {
                font-size: 0.95rem;
                flex-grow: 1;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis; /* Magdagdag ng ellipsis para sa mahabang pangalan ng produkto */
            }

            /* Dark Mode Styles for elements *outside* the header, and dropdown only */
            
            
            /* Dark mode search suggestions */
            body.dark-mode .search-suggestions,
            body.dark-mode header
            {
                background-color: #1e1e1e;
                border: 1px solid #333;
            }

            body.dark-mode header h3,
            body.dark-mode .navbar__links,
            body.dark-mode .header-user-name
            {
                color: #e0e0e0;
                background-color: #1e1e1e;
            }

            body.dark-mode .search-suggestion-item 
            {
                color: #e0e0e0;
                border-bottom: 1px solid #333;
            }

            body.dark-mode .search-suggestion-item:hover {
                background-color: #333;
            }
        </style>
    </head>
    <body>
        <header>
            <a href="user_products.php" id="navbar__logo"> <img src="../images/KeepkitSubmark.png" alt="KeepkitSubmark" height="50px">
                <h3>Keepkit</h3>
            </a>
            
            <div class="right-element">
                <ul class="navbar__menu">
                    <li class="navbar__item">
                        <a href="../home.php" class="navbar__links">Inventory</a>
                    </li>
                    <li class="navbar__item" >
                        <a href="user_products.php" class="navbar__links">Home</a>
                    </li>
                    <li class="navbar__item">
                        <a href="customer_about.php" class="navbar__links">About</a>
                    </li>
                    <li class="navbar__item">
                        <a href="customer_contact.php" class="navbar__links">Contact</a>
                    </li>
                </ul>
                
                <a href="user_cart.php" class="cart-icon-container">
                    <img src="../images/iconCart.png" alt="Cart Icon" height="35px">
                    <span id="cartCountBadge" class="cart-count-badge"><?=htmlspecialchars($total_items_in_cart_for_header)?></span>
                </a>

                <?php if ($is_logged_in): ?>
                    <div class="user-info-dropdown">
                        <a href="<?= htmlspecialchars($profile_link_url) ?>" class="profile-link-header" style="color: #151515;"> 
                            <img src="<?= $profile_pic_path ?>" alt="User Profile" class="icon profile-pic-icon-header">
                            <span class="header-user-name"><?= htmlspecialchars($customer_first_name) ?></span>
                        </a>                  
                    </div>

                <?php else: ?>
                    <a href="../login.php?action=login" class="header-btn">Sign In</a>
                    <a href="../signUp.php?action=register" class="header-btn primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </header>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Dark Mode Toggle Functionality ---
            const darkModeToggle = document.getElementById('darkModeToggle');

            // Function to set the theme based on preference
            function setTheme(isDark) {
                const body = document.body;
                if (isDark) {
                    body.classList.add('dark-mode');
                    // Optional: Update toggle text if desired
                    if (darkModeToggle) {
                        darkModeToggle.textContent = 'Light Mode';
                    }
                } else {
                    body.classList.remove('dark-mode');
                    // Optional: Update toggle text if desired
                    if (darkModeToggle) {
                        darkModeToggle.textContent = 'Dark Mode';
                    }
                }
                // Save preference to local storage
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            }

            // Apply saved theme on page load
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                setTheme(true);
            } else if (savedTheme === 'light') {
                setTheme(false);
            } else {
                // Default to light mode if no preference is saved
                setTheme(false);
            }

            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isDark = document.body.classList.contains('dark-mode');
                    setTheme(!isDark); // Toggle the theme
                    
                    // Hide dropdown after click
                    const dropdownContent = this.closest('.dropdown-content');
                    if (dropdownContent) {
                        dropdownContent.style.display = 'none';
                    }
                });
            }


            // --- User Dropdown Toggle ---
        

            // --- Search Functionality with Autocomplete ---
            const publicSearchInput = document.getElementById('publicSearchInput');
            const publicSearchButton = document.getElementById('publicSearchButton');
            const searchSuggestions = document.getElementById('searchSuggestions');
            let debounceTimeout;

            function performSearch() {
                const searchTerm = publicSearchInput.value.trim();
                if (searchTerm) {
                    window.location.href = `customer_all_products.php?search=${encodeURIComponent(searchTerm)}`;
                } else {
                    window.location.href = `customer_all_products.php`;
                }
            }

            async function fetchSuggestions() {
                const searchTerm = publicSearchInput.value.trim();
                if (searchTerm.length < 2) { // Kumuha lamang kung may hindi bababa sa 2 character na na-type
                    searchSuggestions.innerHTML = ''; // I-clear ang mga suggestion
                    searchSuggestions.style.display = 'none';
                    return;
                }

                try {
                    const response = await fetch(`../database/fetch_suggestions.php?term=${encodeURIComponent(searchTerm)}`);
                    const data = await response.json();

                    searchSuggestions.innerHTML = ''; // I-clear ang mga nakaraang suggestion

                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(product => {
                            const suggestionItem = document.createElement('div');
                            suggestionItem.classList.add('search-suggestion-item');
                            suggestionItem.dataset.productId = product.id;
                            suggestionItem.dataset.productName = product.product_name;

                            // Imahe ng Produkto
                            const img = document.createElement('img');
                            // Gumamit ng fallback image kung ang product.img ay walang laman o hindi natagpuan ang file
                            img.src = product.img ? `../uploads/products/${product.img}` : 'https://placehold.co/40x40/cccccc/333333?text=No+Image';
                            img.alt = product.product_name;
                            suggestionItem.appendChild(img);

                            // Pangalan ng Produkto
                            const textSpan = document.createElement('span');
                            textSpan.textContent = product.product_name;
                            suggestionItem.appendChild(textSpan);

                            suggestionItem.addEventListener('click', () => {
                                window.location.href = `customer_product_detail.php?id=${product.id}`;
                                searchSuggestions.style.display = 'none'; // Itago ang mga suggestion pagkatapos ng click
                            });
                            searchSuggestions.appendChild(suggestionItem);
                        });
                        searchSuggestions.style.display = 'block'; // Ipakita ang mga suggestion
                    } else {
                        searchSuggestions.style.display = 'none'; // Itago kung walang suggestion o error
                    }
                } catch (error) {
                    console.error('Error fetching suggestions:', error);
                    searchSuggestions.style.display = 'none';
                }
            }
            
            // Event listeners para sa search input
            publicSearchInput.addEventListener('input', () => {
                clearTimeout(debounceTimeout);
                debounceTimeout = setTimeout(fetchSuggestions, 300); // 300ms debounce
            });

            // Search button click handler
            publicSearchButton.addEventListener('click', performSearch);
            
            // Enter key press handler on the input field
            publicSearchInput.addEventListener('keypress', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault(); // Pigilan ang default form submission
                    performSearch();
                }
            });

            // Isara ang suggestions kung mag-click sa labas
            document.addEventListener('click', (event) => {
                if (!event.target.closest('.search-container')) {
                    searchSuggestions.style.display = 'none';
                }
            });


            // Global function to update cart count badge (used by add_to_cart.php)
            window.updateCartCountBadge = function(count) {
                const cartCountBadge = document.getElementById('cartCountBadge');
                if (cartCountBadge) {
                    cartCountBadge.textContent = count;
                }
            };
            
            // Global function to update header profile picture (used by customer_profile.php and users_profile.php)
            window.updateHeaderProfilePic = function(newPicPath) {
                const profilePicIconHeader = document.querySelector('.profile-pic-icon-header');
                if (profilePicIconHeader) {
                    profilePicIconHeader.src = newPicPath;
                }
            };


            // Kunin ang aktwal na cart count sa pag-load ng pahina
            // Sinasiguro ng AJAX call na ito na tumpak ang cart count kahit na i-refresh ang pahina o direktang bisitahin.
            fetch('../database/get_cart_count.php')
                .then(response => {
                    if (!response.ok) {
                        // Mahalaga na mag-throw ng error dito upang mahawakan ng catch block ang network/HTTP errors
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    // I-update ang badge gamit ang count na natanggap mula sa server
                    if (data.success) { // Check for success flag
                        window.updateCartCountBadge(data.count);
                    } else {
                        console.error('Error fetching cart count:', data.message);
                        window.updateCartCountBadge(0);
                    }
                })
                .catch(error => {
                    console.error('Error fetching cart count:', error);
                    window.updateCartCountBadge(0);
                });
        });
        </script>
    </body>
    </html>
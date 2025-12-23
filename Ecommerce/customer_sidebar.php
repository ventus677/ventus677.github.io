<?php
// Tiyakin na ang session ay nagsimula sa tumatawag na pahina (e.g., customer_profile.php)

// Tukuyin kung sino ang naka-login.
$is_customer_logged_in = isset($_SESSION['customer']) && isset($_SESSION['customer']['id']);
$is_user_logged_in = isset($_SESSION['user']) && isset($_SESSION['user']['id']);

$is_logged_in = $is_customer_logged_in || $is_user_logged_in;

// Gumamit ng tamang session data at tukuyin ang role
if ($is_customer_logged_in) {
    $session_data = $_SESSION['customer'];
    $role = 'Customer';
} elseif ($is_user_logged_in) {
    $session_data = $_SESSION['user'];
    // Kunin ang role mula sa session, default sa 'User' kung wala
    $role = ucfirst($session_data['role'] ?? 'User');
} else {
    $session_data = [];
    $role = 'Guest';
}

$first_name = $session_data['first_name'] ?? 'Guest';
$profile_pic_filename = $session_data['profile_picture'] ?? 'iconUser.png'; 

// Logic para sa Profile Picture Path
$default_profile_pic_path = '../images/iconUser.png'; 
$profile_pic_path = $default_profile_pic_path;

if ($is_logged_in && !empty($profile_pic_filename) && file_exists('../uploads/profiles/' . $profile_pic_filename)) {
    $profile_pic_path = '../uploads/profiles/' . htmlspecialchars($profile_pic_filename);
}

// Kunin ang current script filename para malaman kung aling link ang active.
$current_page = basename($_SERVER['PHP_SELF']); 

// Function para i-check kung active ang link
function is_active($page_name, $current) {
    if ($current === $page_name) {
        return 'active';
    }
    if ($page_name === 'customer_profile.php' && $current === 'customer_profile.php') {
        return 'active';
    }
    return '';
}
?>

<aside class="profile-sidebar sticky-full-height">
    
    <div class="sidebar-header">
        <div class="profile-summary">
            <img 
                src="<?= htmlspecialchars($profile_pic_path) ?>" 
                alt="<?= htmlspecialchars($first_name) ?>'s Profile" 
                class="profile-pic-sidebar"
                onerror="this.onerror=null;this.src='<?= htmlspecialchars($default_profile_pic_path) ?>';"
            >
            <h3><?= htmlspecialchars($first_name) ?></h3>
            <span class="username-label">Role: <?= htmlspecialchars($role) ?></span> 
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="customer_profile.php" class="<?= is_active('customer_profile.php', $current_page) ?>">
                    <i class="fas fa-id-card"></i> My Profile
                </a>
            </li>
            
            <?php if ($is_logged_in): ?>
            <li>
                <a href="customers_active_orders.php" class="<?= is_active('customers_active_orders.php', $current_page) ?>">
                    <i class="fas fa-truck"></i> Order Status
                </a>
            </li>
            <li>
                <a href="customers_order_history.php" class="<?= is_active('customers_order_history.php', $current_page) ?>">
                    <i class="fas fa-history"></i> Order History
                </a>
            </li>
            
            <?php endif; ?>

        </ul>
    </nav>
    
    <div class="sidebar-footer">
        
        <div class="theme-switch-wrapper">
            <span class="theme-label">Theme Mode</span>
            <label class="theme-switch" for="sidebarCheckbox">
                <input type="checkbox" id="sidebarCheckbox" title="Toggle Dark/Light Mode" />
                <div class="slider round"></div>
            </label>
        </div>

        <a href="../database/user_logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

</aside>

<style>
    /* * NEW: CSS Variables (Maaaring ilipat sa global CSS file)
     */
    :root {
        --sidebar-width: 250px;
        --sidebar-bg-color: #f4f4f4;
        --text-color: #333;
        --primary-color: #ffa53c;
        --hover-color: #e9e9e9;
        --border-color: #ddd;
        --danger-color: #a93131;
    }

    /* Dark Mode variables for the sidebar, complements the header's dark-mode styles */
    body.dark-mode {
        /* I-define ang mga variable na ito sa body.dark-mode class */
        --sidebar-bg-color: #151515;
        --text-color: #fafafa;
        --hover-color: #b86433;
        --border-color: #3f3f3fff;
    }
    
    /* 1. KEY STICKY STYLES */
    .profile-sidebar.sticky-full-height {
        position: fixed;
        top: 60px;
        left: 0; 
        height: 100vh; /* Full vertical height */
        width: var(--sidebar-width); 
        z-index: 99; /* Ilagay sa ilalim ng fixed header (z-index 100) */
        background-color: var(--sidebar-bg-color);
        padding: 20px;
        box-shadow: 2px 0 5px rgba(0,0,0,0.15);
        display: flex;
        flex-direction: column;
        transition: background-color 0.3s;
    }

    /* Padding adjustment to clear the fixed header */
    .profile-sidebar {
        padding-top: 80px; /* I-adjust para bumaba sa ilalim ng header (60px height + 20px padding) */
    }
    
    /* General Sidebar Styling */
    .sidebar-header {
        text-align: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-color);
    }
    .profile-summary h3 {
        margin: 5px 0 0;
        font-size: 1.2em;
    }
    /* Binago: ginawa itong mas nababasa sa dark/light mode */
    .profile-summary .username-label {
        font-size: 0.85em;
        color: var(--text-color); 
        opacity: 0.7;
        margin-top: 2px;
    }
    .profile-pic-sidebar {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 10px;
        border: 3px solid var(--primary-color);
    }

    /* Navigation */
    .sidebar-nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
        flex-grow: 1; 
    }
    .sidebar-nav a {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        text-decoration: none;
        color: var(--text-color);
        border-radius: 5px;
        transition: background-color 0.2s, color 0.2s;
        font-weight: 500;
    }
    .sidebar-nav a i {
        margin-right: 10px;
        width: 20px; /* I-align ang text */
    }
    .sidebar-nav a:hover {
        background-color: var(--hover-color);
    }
    .sidebar-nav a.active {
        background-color: var(--primary-color);
        color: white;
        font-weight: bold;
    }

    /* Footer (Logout and Switch) */
    .sidebar-footer {
        padding-top: 20px;
        border-top: 1px solid var(--border-color);
    }
    .logout-btn {
        display: block;
        width: 100%;
        text-align: center;
        margin-top: 15px;
        padding: 10px;
        background-color: var(--danger-color);
        color: white;
        text-decoration: none;
        border-radius: 5px;
        transition: background-color 0.2s;
    }
    .logout-btn:hover {
        background-color: var(--danger-color);
        opacity: 0.9;
    }

    /* --- Theme Switch Styling --- */
    .theme-switch-wrapper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
    }
    .theme-label { 
        font-size: 0.9em;
        color: var(--text-color); 
    }
    .theme-switch { position: relative; display: inline-block; width: 45px; height: 25px; }
    .theme-switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
        background-color: #ccc; transition: .4s; border-radius: 25px;
    }
    .slider:before {
        position: absolute; content: ""; height: 17px; width: 17px; left: 4px; bottom: 4px;
        background-color: white; transition: .4s; border-radius: 50%;
    }
    /* I-adjust ang kulay ng slider para sa light/dark mode */
    .slider { background-color: #ccc; }
    body.dark-mode .slider { background-color: #555; }
    input:checked + .slider { background-color: var(--primary-color); }
    input:checked + .slider:before { transform: translateX(20px); }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('sidebarCheckbox');
        
        function setTheme(isDark) {
            const body = document.body;
            if (isDark) {
                body.classList.add('dark-mode');
            } else {
                body.classList.remove('dark-mode');
            }
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            
            if (checkbox) {
                checkbox.checked = isDark;
            }
        }
        
        const savedTheme = localStorage.getItem('theme');
        const isDark = (savedTheme === 'dark');
        setTheme(isDark);

        if (checkbox) {
            checkbox.addEventListener('change', function() {
                setTheme(this.checked);
            });
        }
    });
</script>
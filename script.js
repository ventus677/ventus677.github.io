// ****USER-AUTHENTICATION**** //

function toggleForms(type) {
    const signupForm = document.getElementById("signupForm");
    const signinForm = document.getElementById("signinForm");

    // lets user switch inbetween forms according to their need
    if (type === 'signup') {
        signupForm.classList.remove("hidden");
        signinForm.classList.add("hidden");
    } else if (type === 'signin') {
        signupForm.classList.add("hidden");
        signinForm.classList.remove("hidden");
    }
}

window.addEventListener("DOMContentLoaded", () => {
    const signupFormElement = document.getElementById("signupFormElement");
    const signinFormElement = document.getElementById("signinFormElement");

    const params = new URLSearchParams(window.location.search);
    const formType = params.get("form");

    /* For landing page buttons. Get Started button --> Sign-up form.
     * Sign-in button --> Sign-in form.
     * Sign-up button --> Sign-up form.    */
    if (formType === "signup" || formType === "signin") {
        toggleForms(formType);
    }

    // A message fades in and fades out when user signed up successfully
    // This showMessage is for the index/login page. user_profile.php has its own.
    const msg = document.getElementById('msg');
    if (msg) { // Check if msg element exists
        function showMessage(text) {
            msg.textContent = text;
            msg.style.opacity = '1';
            setTimeout(() => {
                msg.style.opacity = '0';
            }, 2500);
        }
    }


    /* Sign up button redirects user to sign in form
    signupFormElement.addEventListener("submit", function(e) {
        e.preventDefault();
        showMessage('Sign-up successful! Please sign in.');
        toggleForms('signin');
    });*/

    /* // Sign in form redirects user to homepage
    signinFormElement.addEventListener("submit", function(e) {
        e.preventDefault();
        window.location.href = "home.php";
    });*/
});



// ****HOMEPAGE BUTTONS**** //

// SIDEBAR AND CARD NAVIGATION
document.addEventListener("DOMContentLoaded", () => {
    // Select all <a> tags in sidebar with data-page, all cards with data-page,
    // AND the new profile link in the sidebar header
    const clickableElements = document.querySelectorAll(
        ".sidebar-menu a[data-page], .card[data-page], .sidebar-menu .profile-link"
    );

    clickableElements.forEach(element => {
        element.addEventListener("click", (e) => {
            e.preventDefault(); // prevent default anchor behavior

            // For sidebar links and cards, get data-page. For profile-link, use its href.
            const targetPage = element.getAttribute("data-page") || element.getAttribute("href");

            if (targetPage) {
                window.location.href = targetPage;
            }
        });
    });

    // Highlight active sidebar link based on the current page URL
    const currentPage = window.location.pathname.split('/').pop(); // e.g., "home.php"
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    sidebarLinks.forEach(link => {
        if (link.dataset.page === currentPage) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
});


// ***Search Bar*** //
document.addEventListener("DOMContentLoaded", () => { // Encapsulate search bar logic
    const searchInput = document.getElementById('searchInput');
    const searchResultsContainer = document.getElementById('searchResults');

    if (searchInput && searchResultsContainer) { // Only add listeners if elements exist
        searchInput.addEventListener('input', async function() {
            const query = this.value.trim();

            // Clear previous results and hide the container if the query is empty
            if (query === '') {
                searchResultsContainer.innerHTML = '';
                searchResultsContainer.style.display = 'none'; // Explicitly hide
                return;
            }

            try {
                const response = await fetch('search_handler.php?q=' + encodeURIComponent(query));
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.text(); // Get the HTML content from PHP

                searchResultsContainer.innerHTML = data; // Insert the HTML

                // * CRUCIAL: Show the results container after content is loaded *
                searchResultsContainer.style.display = 'block';

            } catch (error) {
                console.error('Error fetching search results:', error);
                searchResultsContainer.innerHTML = '<p class="error">Error fetching search results.</p>';
                searchResultsContainer.style.display = 'block'; // Show error message
            }
        });

        // Optional: Hide results when clicking outside the search area
        document.addEventListener('click', (event) => {
            if (!searchInput.contains(event.target) && !searchResultsContainer.contains(event.target)) {
                searchResultsContainer.style.display = 'none';
            }
        });

        // Optional: Show results again if search input is focused and has text
        searchInput.addEventListener('focus', () => {
            if (searchInput.value.trim().length > 0 && searchResultsContainer.innerHTML !== '') {
                // Ensure there's actual content, not just error/no results message
                if (!searchResultsContainer.querySelector('.no-results') && !searchResultsContainer.querySelector('.error')) {
                    searchResultsContainer.style.display = 'block';
                }
            }
        });
    }
});
document.addEventListener('DOMContentLoaded', () => {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const toggleIcon = sidebarToggle.querySelector('i');

    // Function to apply the sidebar state
    const applySidebarState = () => {
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            toggleIcon.classList.remove('fa-chevron-left');
            toggleIcon.classList.add('fa-chevron-right');
        } else {
            sidebar.classList.remove('collapsed');
            toggleIcon.classList.remove('fa-chevron-right');
            toggleIcon.classList.add('fa-chevron-left');
        }
    };

    // Apply the saved state on page load
    applySidebarState();

    sidebarToggle.addEventListener('click', () => {
        // Toggle the collapsed state
        const isNowCollapsed = !sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isNowCollapsed); // Store the state

        applySidebarState(); // Update the UI immediately
    });
});



document.addEventListener("DOMContentLoaded", function () {
    const darkModeToggle = document.getElementById('darkModeToggle');

    // Check if dark mode is enabled in localStorage
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
        if (darkModeToggle) {
            darkModeToggle.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
        }
    }

    // Event listener for toggling dark mode
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function () {
            document.body.classList.toggle('dark-mode');

            // Store the dark mode state in localStorage
            const isDarkMode = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDarkMode);

            // Change button text/icon
            darkModeToggle.innerHTML = isDarkMode
                ? '<i class="fas fa-sun"></i> Light Mode'
                : '<i class="fas fa-moon"></i> Dark Mode';
        });
    }
});



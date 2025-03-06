document.addEventListener('DOMContentLoaded', () => {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.getElementById('mainContent');
    const toggleIcon = sidebarToggle.querySelector('i');  // Select the icon inside the toggle button

    // Set the initial state of the sidebar to be collapsed
    if (!sidebar.classList.contains('collapsed')) {
        sidebar.classList.add('collapsed');
        toggleIcon.classList.remove('fa-chevron-left');
        toggleIcon.classList.add('fa-chevron-right');
    }

    sidebarToggle.addEventListener('click', () => {
        // Toggle the collapsed class on both sidebar and main content
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('collapsed');

        // Change the icon based on whether the sidebar is collapsed or not
        if (sidebar.classList.contains('collapsed')) {
            toggleIcon.classList.remove('fa-chevron-left');
            toggleIcon.classList.add('fa-chevron-right');  // Change to right chevron when collapsed
        } else {
            toggleIcon.classList.remove('fa-chevron-right');
            toggleIcon.classList.add('fa-chevron-left');  // Change to left chevron when expanded
        }
    });
});



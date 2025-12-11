$(document).ready(function() {
    
    // 1. SIDEBAR TOGGLE LOGIC (Existing)
    $('#sidebar-toggle').on('click', function(e) {
        e.preventDefault();
        $('body').toggleClass('sb-sidenav-toggled');
    });

    // 2. ACTIVE LINK HIGHLIGHTER (New)
    var currentUrl = window.location.href; // Get the full URL of the current page

    $('.list-group-item').each(function() {
        // 'this.href' is the absolute URL of the sidebar link
        if (this.href === currentUrl) {
            $(this).addClass('active'); // Add the highlight class
        }
    });

});
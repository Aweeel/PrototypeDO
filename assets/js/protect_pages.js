//----------------LAGAY SA DULO NG INSIDE PAGES/MODULES------------------
// Include this script with the PHP ASSETS_URL helper.

// Force reload if back button tries to show cached page
window.addEventListener('pageshow', function (event) {
    if (event.persisted || (performance.getEntriesByType("navigation")[0]?.type === "back_forward")) {
        window.location.reload();
    }
});

// Prevent navigating back to login page
history.pushState(null, null, location.href);
window.onpopstate = function () {
    history.go(1);
};


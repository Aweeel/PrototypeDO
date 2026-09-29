// Logout when the browser closes
window.addEventListener("beforeunload", function () {
    navigator.sendBeacon(window.appUrl('/modules/login/logout.php'));
});

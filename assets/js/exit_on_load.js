// Logout when the browser closes
window.addEventListener("beforeunload", function () {
    navigator.sendBeacon("/PrototypeDO/modules/login/logout.php");
});

var sessionTimeout = 1800000; // 30 minutes in milliseconds
var warningTime = 1500000; // 25 minutes to show warning before logout
var logoutURL = "./login.php?session_expired=1&fail=session expired. please log in again.";

var timeout, warningTimeout;

function resetTimer() {
    clearTimeout(timeout);
    clearTimeout(warningTimeout);

    warningTimeout = setTimeout(function () {
        alert("Your session is about to expire due to inactivity.");
    }, warningTime);

    timeout = setTimeout(function () {
        window.location.href = logoutURL;
    }, sessionTimeout);
}

document.addEventListener("mousemove", resetTimer);
document.addEventListener("keydown", resetTimer);
document.addEventListener("click", resetTimer);

resetTimer();

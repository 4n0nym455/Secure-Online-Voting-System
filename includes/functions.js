


    function updateDateTime() {
        const now = new Date();

        // Format Date
        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        const date = now.toLocaleDateString('en-US', options);

        // Format Time
        const time = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });

        
        document.getElementById('current-date').textContent = date;
        document.getElementById('current-time').textContent = time;
    }

    // Update Date and Time Every Second
    setInterval(updateDateTime, 500);
    updateDateTime();


document.addEventListener("DOMContentLoaded", function () {
    const rows = document.querySelectorAll("tr[data-end-time]");

    rows.forEach((row) => {
        const endTime = new Date(row.getAttribute("data-end-time"));
        const countdownSpan = row.querySelector(".countdown");

        if (countdownSpan) {
            // Update countdown
            const timer = setInterval(() => {
                const now = new Date();
                const timeLeft = endTime - now;

                if (timeLeft <= 0) {
                    countdownSpan.textContent = "Poll has ended!";
                    countdownSpan.classList.remove('upcoming', 'starting', 'urgent');
                    countdownSpan.classList.add('upcoming');  // Add 'upcoming' class as it has ended
                    clearInterval(timer); // Stop the countdown when time is up
                } else {
                    const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

                    const timeString = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                    countdownSpan.textContent = timeString;

                    // Dynamically set the color of the countdown based on the time left
                    if (timeLeft > 30 * 60 * 1000) { // More than 30 minutes
                        countdownSpan.classList.remove('starting', 'urgent');
                        countdownSpan.classList.add('upcoming');  // Green
                    } else if (timeLeft > 10 * 60 * 1000) { // More than 10 minutes but less than 30 minutes
                        countdownSpan.classList.remove('upcoming', 'urgent');
                        countdownSpan.classList.add('starting'); // Yellow
                    } else { // Less than 2 minutes
                        countdownSpan.classList.remove('upcoming', 'starting');
                        countdownSpan.classList.add('urgent'); // Red and blinking
                    }
                }
            }, 1000);
        }
    });
});


function toggleMenu() {
    const sidePanel = document.getElementById('side-panel');
    const overlay = document.getElementById('overlay');

    sidePanel.classList.toggle('active'); // Slide the side panel
    overlay.classList.toggle('active'); // Dim the background
}


function showPass(fieldId, showBtn, isVisible) {
    const passwordField = document.getElementById(fieldId);
    passwordField.type = isVisible ? "text" : "password";
    showBtn.textContent = isVisible ? "🙈" : "👁️";
}

function confirmVote() {
    return confirm('This process is irreversible! Are you sure you want to submit your votes?');
}



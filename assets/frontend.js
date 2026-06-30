document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash !== '#iswcr-form') return;

    var attempt = function() {
        var el = document.getElementById('iswcr-form');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth' });
        } else if (attempts < 10) {
            attempts++;
            setTimeout(attempt, 200);
        }
    };

    var attempts = 0;

    attempt();
});

(function () {
    // Unlock the Accept button only after the user has scrolled
    // at least 90% of the terms body
    const body   = document.getElementById('tosBody');
    const btn    = document.getElementById('tosAcceptBtn');
    const hint   = document.getElementById('tosScrollHint');

    function checkTosScroll() {
        const scrolled = body.scrollTop + body.clientHeight;
        const total    = body.scrollHeight;
        if (scrolled >= total * 0.90) {
            btn.disabled = false;
            if (hint) hint.classList.add('hidden');
        }
    }

    // Expose globally so the inline onscroll attribute works
    window.checkTosScroll = checkTosScroll;

    // Also check on load in case content is short enough to not need scrolling
    checkTosScroll();

    // Accept handler
    window.acceptTos = function (event) {
        if (event) {
            event.stopPropagation();
        }
        const modal = document.getElementById('tosModal');

        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ajax=1&action=acceptTerms'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                modal.style.transition = 'opacity 0.25s ease';
                modal.style.opacity    = '0';
                setTimeout(() => {
                    modal.remove();
                }, 260);
            } else {
                alert('Failed to accept terms: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error('Error accepting terms:', err);
            alert('An error occurred. Please try again.');
        });
    };
})();

(function () {
    // Unlock the Accept button only after the user has scrolled
    // at least 90% of the terms body
    const body   = document.getElementById('tosBody');
    const btn    = document.getElementById('tosAcceptBtn');
    const hint   = document.getElementById('tosScrollHint');

    // Ensure button starts disabled
    if (btn) {
        btn.disabled = true;
    }

    function checkTosScroll() {
        if (!body || !btn) return;
        
        const scrolled = body.scrollTop + body.clientHeight;
        const total    = body.scrollHeight;
        // Only enable button if:
        // 1. Content requires scrolling (scrollHeight > clientHeight) AND user scrolled 90%
        // 2. OR content doesn't require scrolling (scrollHeight <= clientHeight)
        const contentRequiresScroll = total > body.clientHeight;
        
        if (!contentRequiresScroll || scrolled >= total * 0.90) {
            btn.disabled = false;
            if (hint) hint.classList.add('hidden');
        } else {
            btn.disabled = true;
            if (hint) hint.classList.remove('hidden');
        }
    }

    // Expose globally so the inline onscroll attribute works
    window.checkTosScroll = checkTosScroll;

    // Check after content loads (don't check on initial load - wait for dynamic content to load)

    // Accept handler
    window.acceptTos = function (event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Prevent submission if button is disabled
        if (btn.disabled) {
            console.warn('Button is disabled - cannot accept terms');
            return false;
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
        
        return false;
    };

    // Attach click event listener instead of onclick
    if (btn) {
        btn.addEventListener('click', acceptTos);
    }
})();

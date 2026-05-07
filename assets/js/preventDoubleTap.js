// Prevent accidental double taps/clicks on important buttons.
(function() {
    const DISABLE_MS = 1200; // default disable duration

    function isActionable(el) {
        if (!el) return false;
        const tag = el.tagName && el.tagName.toLowerCase();
        if (tag === 'button') return true;
        if (tag === 'input' && (el.type === 'submit' || el.type === 'button')) return true;
        if (el.getAttribute && el.getAttribute('role') === 'button') return true;
        return false;
    }

    function disableElement(el, ms = DISABLE_MS) {
        try {
            el.setAttribute('data-disabled-by-prevent', '1');
            el.__prevDisabled = el.disabled;
            el.disabled = true;
            el.classList.add('opacity-50', 'cursor-not-allowed');
            setTimeout(() => {
                // Only re-enable if we set it
                if (el.getAttribute('data-disabled-by-prevent') === '1') {
                    el.disabled = !!el.__prevDisabled;
                    el.classList.remove('opacity-50', 'cursor-not-allowed');
                    el.removeAttribute('data-disabled-by-prevent');
                }
            }, ms);
        } catch (e) { /* ignore */ }
    }

    // Allow page code to re-enable a button early: window.preventDoubleTap.reenable(el)
    window.preventDoubleTap = {
        reenable(el) {
            if (!el) return;
            try {
                el.disabled = !!el.__prevDisabled;
                el.classList.remove('opacity-50', 'cursor-not-allowed');
                el.removeAttribute('data-disabled-by-prevent');
            } catch (e) {}
        }
    };

    // Delegate clicks and protect only the controls explicitly marked for prevention.
    document.addEventListener('click', function(evt) {
        const el = evt.target.closest('button, input[type="submit"], input[type="button"], [role="button"]');
        if (!el) return;

        if (!el.matches('.prevent-double, [data-prevent-double="true"]')) return;

        // Skip if element opted out
        if (el.hasAttribute('data-allow-double') || el.classList.contains('allow-double')) return;

        // Skip elements that are links without role button
        if (el.tagName && el.tagName.toLowerCase() === 'a' && !isActionable(el)) return;

        if (!isActionable(el)) return;

        // If already disabled, prevent further action
        if (el.disabled) {
            evt.stopImmediatePropagation();
            evt.preventDefault();
            return;
        }

        // If element has explicit data-prevent-double="false", skip
        if (el.getAttribute('data-prevent-double') === 'false') return;

        // Protect this click
        disableElement(el, parseInt(el.getAttribute('data-disable-ms') || DISABLE_MS, 10));
    });
})();

(function () {
    'use strict';

    const rules = [
        { selector: '#globalBannerText', maxLength: 250 },
        { selector: '#offense_name, #sanction_name', maxLength: 50 },
        { selector: '#add_email', emailDomain: 'sti.edu', localPartMaxLength: 64 },
        { selector: '#add_contact_number, input[name="contact_number"]', contactNumber: true },
        { selector: '#newStudentNumber, #studentNumber, input[name="student_number"]', studentNumber: true },
        { selector: '#add_do_id, input[name="do_id"]', fixedDigits: 11 },
        { selector: '#newNotes, #editNotes, #notes', maxLength: 250 },
        { selector: '#addItemForm input[name="item_name"], #editItemForm input[name="item_name"]', maxLength: 50 },
        { selector: '#addItemForm input[name="location"], #editItemForm input[name="location"]', maxLength: 50 },
        { selector: '#addItemForm textarea[name="description"], #editItemForm textarea[name="description"]', maxLength: 250 },
        { selector: '#eventName, #editEventName', maxLength: 100 },
        { selector: '#eventLocation, #editEventLocation', maxLength: 100 },
        { selector: '#eventDescription, #editEventDescription', maxLength: 250 },
        { selector: '#itemImageInput', fileTypes: ['image/png', 'image/jpeg', 'image/webp'] },
        { selector: '#rejectCommunityServiceNote', requiredText: true }
    ];

    function setValidity(element, message) {
        element.setCustomValidity(message || '');
    }

    function getRule(element) {
        return rules.find((rule) => element.matches(rule.selector));
    }

    function validateElement(element) {
        if (!(element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement)) {
            return true;
        }

        const rule = getRule(element);
        let message = '';
        const value = element.value;

        if (rule?.requiredText && !value.trim()) {
            message = 'A rejection reason is required.';
        } else if (rule?.fileTypes && element.files?.[0] && !rule.fileTypes.includes(element.files[0].type)) {
            message = 'Item Picture must be a PNG, JPG, or WebP file.';
        } else if (rule?.maxLength && value.length > rule.maxLength) {
            message = `This field must not exceed ${rule.maxLength} characters.`;
        } else if (rule?.fixedDigits && value !== '' && !new RegExp(`^\\d{${rule.fixedDigits}}$`).test(value)) {
            message = `Enter exactly ${rule.fixedDigits} numbers.`;
        } else if (rule?.studentNumber && value !== '' && !/^\d{11}$/.test(value)) {
            message = 'Student Number must contain exactly 11 numbers.';
        } else if (rule?.contactNumber && value !== '' && !/^0\d{3} \d{3} \d{4}$/.test(value)) {
            message = 'Use the contact number format 0XXX XXX XXXX.';
        } else if (rule?.emailDomain && value !== '') {
            const parts = value.split('@');
            if (parts.length !== 2 || parts[0].length > rule.localPartMaxLength || parts[1].toLowerCase() !== rule.emailDomain) {
                message = `Use an email with at most ${rule.localPartMaxLength} characters before @${rule.emailDomain}.`;
            }
        }

        if (!message && (element.type === 'date' || element.type === 'time' || element.type === 'datetime-local') && value) {
            if (value.startsWith('-') || (element.type === 'time' && !/^(?:[01]\d|2[0-3]):[0-5]\d$/.test(value))) {
                message = 'Enter a valid non-negative date or time.';
            }
        }

        setValidity(element, message);
        return !message;
    }

    function applyRules(root = document) {
        rules.forEach((rule) => {
            root.querySelectorAll(rule.selector).forEach((element) => {
                if (rule.maxLength) {
                    element.maxLength = rule.maxLength;
                }
                if (rule.contactNumber) {
                    element.maxLength = 13;
                    element.pattern = '0[0-9]{3} [0-9]{3} [0-9]{4}';
                    element.inputMode = 'numeric';
                }
                if (rule.fixedDigits || rule.studentNumber) {
                    element.maxLength = 11;
                    element.inputMode = 'numeric';
                }
                if (rule.emailDomain) {
                    element.maxLength = rule.localPartMaxLength + rule.emailDomain.length + 1;
                }
                validateElement(element);
            });
        });
    }

    function validateAcademicTerms(form) {
        const names = [
            'sy_start_date', 'sy_end_date',
            'first_semester_start', 'first_semester_end',
            'second_semester_start', 'second_semester_end'
        ];
        const values = Object.fromEntries(names.map((name) => [name, form.elements[name]?.value || '']));
        let message = '';
        if (values.sy_start_date && values.sy_end_date && values.sy_end_date < values.sy_start_date) {
            message = 'SY end must be on or after SY start.';
        } else if (values.first_semester_start && values.first_semester_end && values.first_semester_end < values.first_semester_start) {
            message = '1st semester end must be on or after its start.';
        } else if (values.second_semester_start && values.second_semester_end && values.second_semester_end < values.second_semester_start) {
            message = '2nd semester end must be on or after its start.';
        } else if (values.first_semester_start && values.first_semester_end && values.second_semester_start
            && values.second_semester_end && values.first_semester_start <= values.second_semester_end
            && values.second_semester_start <= values.first_semester_end) {
            message = 'The semester date ranges must not overlap.';
        }

        const firstStart = form.elements.first_semester_start;
        setValidity(firstStart, message);
        return !message;
    }

    document.addEventListener('input', (event) => {
        validateElement(event.target);
        if (event.target.matches('#add_contact_number, input[name="contact_number"]')) {
            const digits = event.target.value.replace(/\D/g, '').slice(0, 11);
            event.target.value = digits.replace(/^(\d{4})(\d{0,3})(\d{0,4}).*$/, (_, first, middle, last) =>
                first + (middle ? ` ${middle}` : '') + (last ? ` ${last}` : ''));
            validateElement(event.target);
        }
    });

    document.addEventListener('change', (event) => validateElement(event.target));
    document.addEventListener('submit', (event) => {
        applyRules(event.target);
        const valid = Array.from(event.target.querySelectorAll('input, textarea')).every(validateElement)
            && (!event.target.querySelector('[name="first_semester_start"]') || validateAcademicTerms(event.target));
        if (!valid) {
            event.preventDefault();
            event.stopImmediatePropagation();
            event.target.querySelector(':invalid')?.focus();
        }
    }, true);

    applyRules();
    new MutationObserver((mutations) => mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE) {
                applyRules(node);
            }
        });
    })).observe(document.documentElement, { childList: true, subtree: true });

    window.AppValidation = { applyRules, validateElement, validateAcademicTerms };
})();

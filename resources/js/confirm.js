/**
 * BotHost Pro — custom confirmation modal system.
 * Registers an Alpine.js store and intercepts clicks on [data-confirm] elements.
 *
 * Data attributes:
 *   data-confirm                       — required, marks the element
 *   data-confirm-title="..."           — modal title
 *   data-confirm-message="..."         — modal body text
 *   data-confirm-warning="..."         — optional amber warning line
 *   data-confirm-type="danger|warning|default|success"
 *   data-confirm-btn="..."             — confirm button label
 *   data-confirm-typed="true"          — require typed confirmation
 *   data-confirm-word="DELETE"         — the word to type (default: DELETE)
 */

document.addEventListener('alpine:init', () => {
    Alpine.store('confirm', {
        open:        false,
        type:        'danger',
        title:       '',
        message:     '',
        warning:     '',
        confirmText: 'Confirm',
        requireTyped: false,
        typedWord:   'DELETE',
        typedValue:  '',
        loading:     false,
        _callback:   null,

        show({ type = 'danger', title = 'Are you sure?', message = '', warning = '', confirmText = 'Confirm', requireTyped = false, typedWord = 'DELETE', onAccept = null }) {
            this.type         = type;
            this.title        = title;
            this.message      = message;
            this.warning      = warning;
            this.confirmText  = confirmText;
            this.requireTyped = requireTyped;
            this.typedWord    = typedWord;
            this.typedValue   = '';
            this.loading      = false;
            this._callback    = onAccept;
            this.open         = true;
        },

        accept() {
            if (this.requireTyped && this.typedValue !== this.typedWord) return;
            this.loading = true;
            if (typeof this._callback === 'function') {
                this._callback();
            }
        },

        cancel() {
            this.open     = false;
            this.loading  = false;
            this._callback = null;
        },
    });
});

// Delegated click handler — intercepts [data-confirm] elements before default action
document.addEventListener('click', function (e) {
    // Walk up DOM tree to find a data-confirm element
    let el = e.target;
    while (el && el !== document.body) {
        if (el.hasAttribute('data-confirm')) break;
        el = el.parentElement;
    }
    if (!el || !el.hasAttribute('data-confirm')) return;

    e.preventDefault();
    e.stopImmediatePropagation();

    const type        = el.dataset.confirmType    || 'danger';
    const title       = el.dataset.confirmTitle   || 'Are you sure?';
    const message     = el.dataset.confirmMessage || '';
    const warning     = el.dataset.confirmWarning || '';
    const confirmText = el.dataset.confirmBtn     || 'Confirm';
    const requireTyped = el.dataset.confirmTyped  === 'true';
    const typedWord   = el.dataset.confirmWord    || 'DELETE';

    // Determine what happens when confirmed
    const form = el.closest('form');
    const href = (el.tagName === 'A') ? (el.getAttribute('href') || null) : null;

    Alpine.store('confirm').show({
        type,
        title,
        message,
        warning,
        confirmText,
        requireTyped,
        typedWord,
        onAccept() {
            if (form) {
                // Clone form submit to avoid data-confirm re-intercepting
                const clone = document.createElement('input');
                clone.type  = 'submit';
                clone.style.display = 'none';
                form.appendChild(clone);
                clone.click();
                form.removeChild(clone);
            } else if (href) {
                window.location.href = href;
            } else {
                // Button/input outside a form — remove attribute and re-click
                el.removeAttribute('data-confirm');
                el.click();
                el.setAttribute('data-confirm', '');
            }
        },
    });
}, true);

const DEFAULT_FIELD_ORDER = ['platform', 'category', 'service', 'quantity', 'link', 'comments'];

function hasFieldError(errors, key) {
    const value = errors?.[key];
    if (Array.isArray(value)) return value.length > 0;
    return Boolean(value);
}

export function scrollToFirstFormError(errors, { fieldOrder = DEFAULT_FIELD_ORDER, formErrorSelector } = {}) {
    const firstKey = fieldOrder.find((key) => hasFieldError(errors, key));

    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(() => {
            if (firstKey) {
                const field = document.querySelector(`[data-form-field="${firstKey}"]`);
                if (field) {
                    field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const focusable = field.querySelector(
                        'input:not([type="hidden"]), textarea, select, button[data-order-trigger]',
                    );
                    focusable?.focus({ preventScroll: true });
                    return;
                }
            }

            if (formErrorSelector) {
                document.querySelector(formErrorSelector)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });
}

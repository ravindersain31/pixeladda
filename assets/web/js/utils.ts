/**
 * Blurs all focusable elements on the page
 */
function blurAllFocusable() {
    const focusableElements = document.querySelectorAll<HTMLElement>(
        'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])'
    );

    focusableElements.forEach(el => {
        el.setAttribute('tabindex', '-1');
        el.blur();
    });
}

/**
 * Focus on an element by ID, blurring all other focusable elements first
 * @param elementId - The ID of the element to focus
 * @returns boolean - true if element was found and focused, false otherwise
 */
function focusElementById(elementId: string): boolean {
    const element = document.getElementById(elementId) as HTMLInputElement | HTMLTextAreaElement | HTMLElement;
    if (element) {
        requestAnimationFrame(() => {
            blurAllFocusable();

            element.setAttribute("tabindex", "0");
            element.focus({ preventScroll: true });
        });
        return true;
    }
    return false;
}

if (typeof window !== 'undefined') {
    (window as any).blurAllFocusable = blurAllFocusable;
    (window as any).focusElementById = focusElementById;
}

export { blurAllFocusable, focusElementById };
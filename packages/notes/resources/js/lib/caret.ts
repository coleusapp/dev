const COPIED_STYLES = [
    'boxSizing',
    'width',
    'paddingTop',
    'paddingRight',
    'paddingBottom',
    'paddingLeft',
    'borderTopWidth',
    'borderLeftWidth',
    'fontFamily',
    'fontSize',
    'fontWeight',
    'lineHeight',
    'letterSpacing',
    'tabSize',
    'whiteSpace',
    'wordWrap',
] as const;

/**
 * Screen coordinates of a character position in a textarea (just below
 * it), measured with an invisible copy of the textarea's text up to that
 * position. Used to place the "/" menu next to the caret in Markdown source.
 */
export const textareaCaretPosition = (textarea: HTMLTextAreaElement, position: number): { left: number; top: number } => {
    const mirror = document.createElement('div');
    const style = window.getComputedStyle(textarea);

    COPIED_STYLES.forEach((property) => {
        mirror.style[property] = style[property];
    });
    mirror.style.position = 'absolute';
    mirror.style.visibility = 'hidden';
    mirror.style.whiteSpace = 'pre-wrap';
    mirror.style.overflow = 'hidden';
    mirror.textContent = textarea.value.slice(0, position);

    const marker = document.createElement('span');
    marker.textContent = '​';
    mirror.appendChild(marker);
    document.body.appendChild(mirror);

    const box = textarea.getBoundingClientRect();
    const left = box.left + marker.offsetLeft - textarea.scrollLeft;
    const top = box.top + marker.offsetTop + marker.offsetHeight - textarea.scrollTop;

    mirror.remove();

    return { left, top };
};

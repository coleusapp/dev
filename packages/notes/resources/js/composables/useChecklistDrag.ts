import { nextTick, onBeforeUnmount, watch, type Ref } from 'vue';

const CHECKBOX = ':scope > input[data-task-line], :scope > p > input[data-task-line]';

/**
 * The source line of a rendered checklist item, read from its checkbox.
 */
export const taskLineOf = (item: Element): number | null => {
    const line = item.querySelector<HTMLInputElement>(CHECKBOX)?.dataset.taskLine;

    return line ? Number(line) : null;
};

/**
 * Where a dragged item lands when dropped on `target`: before it when the
 * pointer is in its upper half, otherwise before the next item of the same
 * list (null = end of the list). Null overall when the drop isn't allowed
 * (another list, or onto itself).
 */
export const dropPosition = (dragged: Element, target: Element, pointerY: number): { beforeLine: number | null } | null => {
    if (dragged === target || dragged.parentElement !== target.parentElement || taskLineOf(target) === null) {
        return null;
    }

    const { top, height } = target.getBoundingClientRect();

    if (pointerY < top + height / 2) {
        return { beforeLine: taskLineOf(target) };
    }

    let next = target.nextElementSibling;
    while (next && taskLineOf(next) === null) {
        next = next.nextElementSibling;
    }

    return { beforeLine: next ? taskLineOf(next) : null };
};

/**
 * Drag-to-reorder for checklist items in the server-rendered preview. Items
 * can move within their own list; the move itself is done by `onMove`
 * (the server rewrites the Markdown and sends the note back).
 */
export const useChecklistDrag = (
    preview: Readonly<Ref<HTMLElement | null>>,
    html: Ref<string>,
    enabled: () => boolean,
    onMove: (line: number, beforeLine: number | null) => void,
) => {
    let dragged: HTMLElement | null = null;

    const markDraggable = (): void => {
        preview.value?.querySelectorAll('li').forEach((item) => {
            item.draggable = enabled() && taskLineOf(item) !== null;
        });
    };

    const clearIndicators = (): void => {
        preview.value?.querySelectorAll('.drop-before, .drop-after').forEach((item) => item.classList.remove('drop-before', 'drop-after'));
    };

    const itemAt = (event: DragEvent): HTMLElement | null =>
        event.target instanceof Element ? event.target.closest<HTMLElement>('li[draggable="true"]') : null;

    const onDragStart = (event: DragEvent): void => {
        dragged = itemAt(event);

        if (dragged && event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', dragged.textContent ?? '');
        }
    };

    const onDragOver = (event: DragEvent): void => {
        const target = itemAt(event);
        const drop = dragged && target ? dropPosition(dragged, target, event.clientY) : null;

        clearIndicators();

        if (drop && target) {
            event.preventDefault();
            target.classList.add(drop.beforeLine === taskLineOf(target) ? 'drop-before' : 'drop-after');
        }
    };

    const onDrop = (event: DragEvent): void => {
        const target = itemAt(event);
        const drop = dragged && target ? dropPosition(dragged, target, event.clientY) : null;
        const line = dragged ? taskLineOf(dragged) : null;

        event.preventDefault();
        clearIndicators();
        dragged = null;

        if (drop && line !== null) {
            onMove(line, drop.beforeLine);
        }
    };

    const onDragEnd = (): void => {
        clearIndicators();
        dragged = null;
    };

    const listeners: [string, (event: DragEvent) => void][] = [
        ['dragstart', onDragStart],
        ['dragover', onDragOver],
        ['drop', onDrop],
        ['dragend', onDragEnd],
    ];

    watch(
        preview,
        (element, previous) => {
            listeners.forEach(([name, listener]) => previous?.removeEventListener(name, listener as EventListener));
            listeners.forEach(([name, listener]) => element?.addEventListener(name, listener as EventListener));
            nextTick(markDraggable);
        },
        { immediate: true },
    );

    watch(html, () => nextTick(markDraggable));

    onBeforeUnmount(() => listeners.forEach(([name, listener]) => preview.value?.removeEventListener(name, listener as EventListener)));

    return { markDraggable };
};

// @vitest-environment jsdom
import { dropPosition, taskLineOf } from '@/composables/useChecklistDrag';
import { describe, expect, it } from 'vitest';

const render = (html: string): HTMLElement => {
    const root = document.createElement('div');
    root.innerHTML = html;

    root.querySelectorAll('li').forEach((item, index) => {
        item.getBoundingClientRect = () => ({ top: index * 20, height: 20 }) as DOMRect;
    });

    return root;
};

const list = render(
    '<ul><li><input type="checkbox" data-task-line="1"> a</li><li><input type="checkbox" data-task-line="2"> b</li><li><input type="checkbox" data-task-line="3"> c</li></ul>' +
        '<ul><li><input type="checkbox" data-task-line="5"> other</li></ul>',
);
const [a, b, c, other] = Array.from(list.querySelectorAll('li'));

describe('checklist drag', () => {
    it('reads the source line from the checkbox, also in loose lists', () => {
        expect(taskLineOf(b)).toBe(2);
        expect(taskLineOf(render('<ul><li><p><input type="checkbox" data-task-line="7"> x</p></li></ul>').querySelector('li')!)).toBe(7);
    });

    it('drops before the target in its upper half and after it in its lower half', () => {
        expect(dropPosition(a, c, 41)).toEqual({ beforeLine: 3 });
        expect(dropPosition(a, b, 35)).toEqual({ beforeLine: 3 });
        expect(dropPosition(a, c, 59)).toEqual({ beforeLine: null });
    });

    it('refuses drops onto itself or into another list', () => {
        expect(dropPosition(a, a, 5)).toBeNull();
        expect(dropPosition(a, other, 65)).toBeNull();
    });
});

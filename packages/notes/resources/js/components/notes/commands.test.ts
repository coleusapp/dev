import { menuCommands } from '@/components/notes/commands';
import { describe, expect, it, vi } from 'vitest';

describe('menuCommands', () => {
    it('turns menu items into commands and runs their handlers', () => {
        const pin = vi.fn();
        const trash = vi.fn();
        const commands = menuCommands('note', [
            [{ label: 'Pin note', icon: 'i-lucide-pin', onSelect: pin }],
            [{ label: 'Move to trash', onSelect: trash }],
        ]);

        expect(commands.map(({ id, label, icon }) => ({ id, label, icon }))).toEqual([
            { id: 'note.0.0', label: 'Pin note', icon: 'i-lucide-pin' },
            { id: 'note.1.0', label: 'Move to trash', icon: undefined },
        ]);

        commands[1].run();
        expect(trash).toHaveBeenCalledOnce();
        expect(pin).not.toHaveBeenCalled();
    });

    it('skips labels, disabled items and items without a handler', () => {
        const commands = menuCommands('sort', [
            [{ type: 'label', label: 'Sort by' }, { label: 'Disabled', disabled: true, onSelect: vi.fn() }, { label: 'Nothing' }],
        ]);

        expect(commands).toEqual([]);
    });

    it('offers the unchecked choices of a checkbox group, prefixed', () => {
        const toggle = vi.fn();
        const commands = menuCommands(
            'sort',
            [
                [
                    { label: 'Title', type: 'checkbox', checked: true, onUpdateChecked: toggle },
                    { label: 'Date created', type: 'checkbox', checked: false, onUpdateChecked: toggle },
                ],
            ],
            'Sort by: ',
        );

        expect(commands.map(({ label }) => label)).toEqual(['Sort by: Date created']);
        commands[0].run();
        expect(toggle).toHaveBeenCalledWith(true);
    });

    it('offers a lone checkbox as a toggle either way', () => {
        const toggle = vi.fn();

        expect(menuCommands('x', [[{ label: 'Group by date', type: 'checkbox', checked: false, onUpdateChecked: toggle }]])[0].label).toBe(
            'Group by date',
        );

        const [off] = menuCommands('x', [[{ label: 'Group by date', type: 'checkbox', checked: true, onUpdateChecked: toggle }]]);
        expect(off.label).toBe('Turn off group by date');
        off.run();
        expect(toggle).toHaveBeenCalledWith(false);
    });
});

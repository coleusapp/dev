import type { DropdownMenuItem } from '@nuxt/ui';

/**
 * One action in the command palette (O7.2).
 */
export type PaletteCommand = {
    id: string;
    label: string;
    icon?: string;
    kbds?: string[];
    run: () => void;
};

export type PaletteGroup = { id: string; label: string; commands: PaletteCommand[] };

/**
 * Turns a dropdown menu (the note's "…" menu, the sort menu) into palette
 * commands, so the palette always offers exactly what the menus do. Labels
 * and disabled items are skipped; a checkbox item becomes "Label" and runs
 * as if it were toggled (checked items are left out, except toggles that
 * have no siblings, which read "Turn off …").
 */
export const menuCommands = (id: string, menu: DropdownMenuItem[][], prefix = ''): PaletteCommand[] =>
    menu.flatMap((group, groupIndex) =>
        group.flatMap((item, index): PaletteCommand[] => {
            if (item.type === 'label' || item.type === 'separator' || item.disabled || !item.label) {
                return [];
            }

            const label = `${prefix}${item.label}`;
            const key = `${id}.${groupIndex}.${index}`;
            const icon = typeof item.icon === 'string' ? item.icon : undefined;

            if (item.type === 'checkbox') {
                const toggle = item.onUpdateChecked as ((checked: boolean) => void) | undefined;

                if (!toggle || (item.checked && group.length > 1)) {
                    return [];
                }

                return [
                    {
                        id: key,
                        label: item.checked ? `${prefix}Turn off ${item.label.toLowerCase()}` : label,
                        icon,
                        run: () => toggle(!item.checked),
                    },
                ];
            }

            const select = item.onSelect as ((event: Event) => void) | undefined;

            return select ? [{ id: key, label, icon, run: () => select(new Event('select')) }] : [];
        }),
    );

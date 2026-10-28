import { ref, watch, type Ref } from 'vue';

/**
 * Keys shared by the editor's suggestion menus: Up/Down move (wrapping,
 * back to the top when the choices change), Enter/Tab choose, Escape
 * closes. handleKey returns whether it used the key.
 */
export const useMenuKeys = <T>(choices: Ref<T[]>, actions: { isOpen: () => boolean; choose: (choice: T) => void; close: () => void }) => {
    const selected = ref(0);

    watch(choices, () => (selected.value = 0));

    const handleKey = (key: string): boolean => {
        const count = choices.value.length;

        if (!actions.isOpen()) {
            return false;
        }

        if (key === 'Escape') {
            actions.close();
        } else if (key === 'ArrowDown' && count) {
            selected.value = (selected.value + 1) % count;
        } else if (key === 'ArrowUp' && count) {
            selected.value = (selected.value - 1 + count) % count;
        } else if ((key === 'Enter' || key === 'Tab') && count) {
            actions.choose(choices.value[selected.value]);
        } else {
            return false;
        }

        return true;
    };

    return { selected, handleKey };
};

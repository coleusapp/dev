import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';

const resolveConfig = {
    pathPrefix: `/app/Extensions/Health/resources/js/pages/`,
    pages: '/app/Extensions/Health/resources/js/pages/**/*.vue',
    alias: '@health',
};

export function resolve(name): {path: string, pages: string} {
    if (name.startsWith('@health')) {
        name = name.replace('@health/', '');

        return {
            path: `/app/Extensions/Health/resources/js/pages/${name}.vue`,
            pages: import.meta.glob<DefineComponent>('/app/Extensions/Health/resources/js/pages/**/*.vue'),
        }
    }
}

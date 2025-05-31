import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import ui from '@nuxt/ui/vue-plugin';
import { plugin as formkitPlugin } from '@formkit/vue';
import formkitConfig from "../../formkit.config";

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;

        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title: string) => `${title} - ${appName}`,
    resolve: async (name: string) => {
        const paths = [`./pages/${name}.vue`];
        let pages = { ...import.meta.glob<DefineComponent>('./pages/**/*.vue') };

        if (name.startsWith('@')) {
            name = name.replace(/^@[A-Za-z_]+\//, '');
            paths.push(`/vendor/coleus/health/resources/js/pages/${name}.vue`);
            pages = {
                ...import.meta.glob<DefineComponent>('/vendor/coleus/health/resources/js/pages/**/*.vue'),
                ...pages
            }
        }

        return resolvePageComponent(paths, pages);
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(ui)
            .use(formkitPlugin, formkitConfig);
        app.config.globalProperties.$toast = useToast();
        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

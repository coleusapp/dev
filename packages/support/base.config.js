import ui from '@nuxt/ui/vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    build: { outDir: 'resources/dist' },
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/js/app.ts'],
            refresh: true,
            hotFile: path.resolve(__dirname, '../../public/hot'),
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        ui({
            prefix: 'Ui',
            dts: false,
            components: false,
            inertia: true,
            ui: {
                colors: {
                    primary: 'indigo',
                    neutral: 'slate',
                },
                table: {
                    slots: {
                        th: 'px-2 py-3.5',
                        td: 'p-2',
                    },
                },
                button: {
                    slots: {
                        base: 'hover:cursor-pointer',
                    },
                },
                slideover: {
                    slots: {
                        content: 'bg-transparent backdrop-blur-xs sm:ring-0 divide-none',
                        body: 'px-2 py-2 sm:px-2 sm:py-2',
                    },
                    variants: {
                        side: {
                            right: {
                                content: 'max-w-sm',
                            },
                            left: {
                                content: 'max-w-sm',
                            },
                        },
                    },
                },
                toast: {
                    defaultVariants: {
                        color: 'neutral',
                    },
                },
                card: {
                    slots: {
                        header: 'p-2 sm:px-2 flex items-center justify-between',
                        body: 'p-2 sm:p-2',
                        footer: 'p-2 sm:px-2',
                    },
                },
                dashboardPanel: {
                    slots: {
                        body: 'flex flex-col gap-4 sm:gap-6 flex-1 overflow-y-auto p-2 sm:p-2',
                    },
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@coleus/support': path.resolve(__dirname, './resources/js'),
        },
    },
});

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
            ssr: 'resources/js/ssr.ts',
            refresh: true,
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
                        header: 'p-2 sm:px-3 flex items-center justify-between',
                        body: 'p-2 sm:p-3',
                        footer: 'p-2 sm:px-3',
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

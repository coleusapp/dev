import { defineConfig, mergeConfig } from 'vite';
import baseConfig from '../support/base.config.js';
import path from 'path';

export default defineConfig(
    mergeConfig(baseConfig, {
        base: '/vendor/frame/',
        resolve: {
            alias: {
                '@': path.resolve(__dirname, 'resources/js'),
            },
        },
    }),
);

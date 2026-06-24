import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        vue({
            template: {
                compilerOptions: {
                    isCustomElement: (tag) => tag.startsWith('media-'),
                },
            },
        }),
    ],
    build: {
        lib: {
            entry: 'resources/js/cp.js',
            name: 'FormsPlus',
            formats: ['iife'],
        },
        outDir: 'dist',
        emptyOutDir: true,
        cssCodeSplit: false,
        rollupOptions: {
            external: ['vue'],
            output: {
                globals: { vue: 'Vue' },
                entryFileNames: 'cp.js',
                assetFileNames: () => 'cp.css',
            },
        },
    },
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
});

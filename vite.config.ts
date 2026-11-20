import { resolve } from 'node:path';
import { defineConfig } from 'vite-plus';
import dts from 'vite-plugin-dts';

export default defineConfig({
    build: {
        rollupOptions: { external: ['vue'] },
        lib: {
            entry: resolve(import.meta.dirname, 'resources/js/index.ts'),
            formats: ['es', 'cjs'],
            fileName: (format) => (format === 'es' ? 'index.js' : 'index.cjs'),
        },
    },
    plugins: [dts({ entryRoot: 'resources/js', include: ['resources/js/**/*.ts'] })],
    fmt: {
        ignorePatterns: [
            '.ai/**',
            '.agents/**',
            '.github/**',
            '*.md',
            'composer.json',
            'config/**',
            'dist/**',
            'logo/**',
            'pint.json',
            'resources/boost/**',
            'src/**',
            'tests/**/*.php',
            'workbench/**',
        ],
        semi: true,
        singleQuote: true,
    },
    lint: {
        ignorePatterns: ['.ai/**', 'dist/**'],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    test: {
        maxWorkers: 2,
        include: ['tests/Frontend/**/*.test.ts'],
        environmentOptions: { happyDOM: { url: 'http://localhost/' } },
    },
});

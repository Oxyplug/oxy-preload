import {defineConfig} from 'vite';
import {resolve} from 'path';

export default defineConfig({
  build: {
    rollupOptions: {
      input: [
        '@/outlined-text-field.js',
        '@/icon.js',
        '@/icon-button.js',
        '@/outlined-button.js',
        '@/filled-button.js',
        '@/divider.js',
        '@/switch.js',
      ],
      output: {
        dir: resolve(__dirname, 'assets/js/dist'),
        format: 'es',
        entryFileNames: '[name].js',
        chunkFileNames: '[name]-[hash].js',
        assetFileNames: '[name]-[hash][extname]',
      },
    },
    emptyOutDir: true, // Clear the dist directory before building
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'assets/js/src'),
    },
  },
});
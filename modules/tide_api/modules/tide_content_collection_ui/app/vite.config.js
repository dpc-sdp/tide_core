import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
  root: './src',
  build: {
    outDir: '../dist',
    rollupOptions: {
      input: {
        'main': resolve(__dirname, 'src/index.js'),
      },
      output: {
        format: 'iife',
        entryFileNames: '[name].js',
        globals: {
          jquery: 'jQuery',
          drupal: 'Drupal',
          once: 'once',
        },
      },
      external: ['jquery', 'drupal', 'once'],
    },
  },
});

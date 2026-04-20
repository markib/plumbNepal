import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: './vitest.setup.ts',
    include: ['resources/js/**/*.test.ts', 'resources/js/**/*.test.tsx'],
    threads: false,
    isolate: true,
    maxWorkers: 1,
  },
});

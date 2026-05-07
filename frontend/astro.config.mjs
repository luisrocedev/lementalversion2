// @ts-check
import { defineConfig } from 'astro/config';

// https://astro.build/config
export default defineConfig({
  server: {
    host: true, // exposes on all network interfaces (0.0.0.0)
    port: 4321,
  },
});

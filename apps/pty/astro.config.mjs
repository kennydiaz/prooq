import sitemap from '@astrojs/sitemap';
import { defineConfig } from 'astro/config';

export default defineConfig({
  site: 'https://prooq.com',
  base: '/pty',
  output: 'static',
  integrations: [sitemap()],
});

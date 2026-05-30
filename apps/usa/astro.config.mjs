import sitemap from '@astrojs/sitemap';
import { defineConfig } from 'astro/config';

export default defineConfig({
  site: 'https://prooq.com',
  base: '/usa',
  output: 'static',
  integrations: [sitemap()],
});

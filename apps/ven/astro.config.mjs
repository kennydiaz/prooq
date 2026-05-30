import sitemap from '@astrojs/sitemap';
import { defineConfig } from 'astro/config';

export default defineConfig({
  site: 'https://prooq.com',
  base: '/ven',
  output: 'static',
  integrations: [sitemap()],
});

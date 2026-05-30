import sitemap from '@astrojs/sitemap';
import { defineConfig } from 'astro/config';

export default defineConfig({
  site: 'https://prooq.com',
  base: '/esp',
  output: 'static',
  integrations: [sitemap()],
});

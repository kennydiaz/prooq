import sitemap from '@astrojs/sitemap';
import { defineConfig } from 'astro/config';

export default defineConfig({
  site: 'https://prooq.com',
  base: '/usa',
  output: 'static',
  // Rutas antiguas en español: se mantienen como redirección para no romper enlaces publicados.
  redirects: {
    '/servicios': '/usa/services',
    '/clientes': '/usa/clients',
  },
  integrations: [sitemap()],
});

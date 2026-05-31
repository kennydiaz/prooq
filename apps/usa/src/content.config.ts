import { defineCollection, z } from 'astro:content';
import type { Loader } from 'astro/loaders';
import { listBlog } from '@prooq/data-client/blog';

// Colección de blog (Content Layer API de Astro 5) servida desde la BD vía la API.
// Los artículos se gestionan en el panel admin; este loader trae los publicados de
// esta sucursal y renderiza su Markdown a HTML en build time. El slug define la URL
// /usa/blog/<slug>.
function blogApiLoader(country: string): Loader {
  return {
    name: 'prooq-blog-api',
    async load({ store, parseData, renderMarkdown, logger }) {
      store.clear();
      let posts;
      try {
        posts = await listBlog(country);
      } catch (err) {
        logger.warn(
          `No se pudo cargar el blog desde la API (${country}): ${
            err instanceof Error ? err.message : String(err)
          }. El blog quedará vacío en este build.`,
        );
        return;
      }
      for (const post of posts) {
        const data = await parseData({
          id: post.slug,
          data: {
            title: post.title,
            description: post.description,
            pubDate: post.pubDate,
            updatedDate: post.updatedDate ?? undefined,
            tags: post.tags ?? [],
            heroImage: post.heroImage ?? undefined,
            author: post.author,
            draft: post.draft,
          },
        });
        store.set({
          id: post.slug,
          data,
          body: post.body,
          rendered: await renderMarkdown(post.body),
        });
      }
    },
  };
}

const blog = defineCollection({
  loader: blogApiLoader('US'),
  schema: z.object({
    title: z.string(),
    description: z.string(),
    pubDate: z.coerce.date(),
    updatedDate: z.coerce.date().optional(),
    tags: z.array(z.string()).default([]),
    heroImage: z.string().optional(),
    author: z.string().default('Kenny Diaz'),
    draft: z.boolean().default(false),
  }),
});

export const collections = { blog };

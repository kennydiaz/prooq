import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders';

// Colección de blog (Content Layer API de Astro 5).
// Cada artículo es un .md en src/content/blog/ — su nombre define el slug y la URL /ven/blog/<slug>.
const blog = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/blog' }),
  schema: z.object({
    title: z.string(),
    description: z.string(),
    pubDate: z.coerce.date(),
    updatedDate: z.coerce.date().optional(),
    tags: z.array(z.string()).default([]),
    heroImage: z.string().optional(),
    draft: z.boolean().default(false),
  }),
});

export const collections = { blog };

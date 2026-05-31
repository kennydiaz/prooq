// Cliente del modulo de blog. Lectura publica por sucursal (listBlog/getBlogPost)
// que cada app usa al construir su /blog en build time, + CRUD admin (cookie de
// sesion via credentials:'include'). El cuerpo viaja en Markdown y la imagen
// destacada se sube como archivo (el API la convierte a WebP 800px).

const apiBase = (): string => import.meta.env.PUBLIC_API_URL ?? 'https://api.prooq.com';

export interface BlogPost {
  id: number;
  country: string;
  slug: string;
  title: string;
  description: string;
  body: string;
  tags: string[];
  /** URL absoluta a la imagen destacada (en api.prooq.com), o null. */
  heroImage: string | null;
  author: string;
  pubDate: string;
  updatedDate: string | null;
  draft: boolean;
  createdAt: string | null;
  updatedAt: string | null;
}

export interface BlogPostInput {
  country: string;
  title: string;
  slug?: string;
  description: string;
  body: string;
  tags?: string[];
  /** Archivo de imagen destacada a subir; el API lo convierte a WebP 800px. */
  heroImage?: File | null;
  /** true para quitar la imagen destacada actual (en update). */
  removeHero?: boolean;
  author?: string;
  pubDate: string;
  updatedDate?: string | null;
  draft?: boolean;
}

async function errorText(res: Response, fallback: string): Promise<string> {
  const body = (await res.json().catch(() => ({}))) as { error?: string };
  return body.error ?? `${fallback} failed: ${res.status}`;
}

// heroImage se guarda relativo ('/uploads/blog/x.webp'); las apps y el admin lo
// consumen cross-domain, asi que lo volvemos absoluto contra el host del API.
function absolutizeHero<T extends BlogPost>(posts: T[]): T[] {
  const base = apiBase().replace(/\/$/, '');
  for (const p of posts) {
    if (p.heroImage?.startsWith('/')) p.heroImage = base + p.heroImage;
  }
  return posts;
}

function toFormData(input: BlogPostInput): FormData {
  const fd = new FormData();
  fd.set('country', input.country);
  fd.set('title', input.title);
  if (input.slug) fd.set('slug', input.slug);
  fd.set('description', input.description);
  fd.set('body', input.body);
  fd.set('tags', (input.tags ?? []).join(','));
  if (input.author) fd.set('author', input.author);
  fd.set('pubDate', input.pubDate);
  if (input.updatedDate) fd.set('updatedDate', input.updatedDate);
  fd.set('draft', input.draft ? '1' : '0');
  if (input.heroImage instanceof File) fd.set('heroImage', input.heroImage);
  if (input.removeHero) fd.set('removeHero', '1');
  return fd;
}

// Publico: posts publicados de una sucursal (recientes primero).
export async function listBlog(country?: string): Promise<BlogPost[]> {
  const url = new URL('/api/blog', apiBase());
  if (country) url.searchParams.set('country', country);
  const res = await fetch(url);
  if (!res.ok) throw new Error(`listBlog failed: ${res.status}`);
  return absolutizeHero((await res.json()) as BlogPost[]);
}

// Publico: un post publicado por sucursal + slug.
export async function getBlogPost(country: string, slug: string): Promise<BlogPost> {
  const res = await fetch(new URL(`/api/blog/${country}/${slug}`, apiBase()));
  if (!res.ok) throw new Error(`getBlogPost failed: ${res.status}`);
  const post = (await res.json()) as BlogPost;
  absolutizeHero([post]); // muta heroImage en sitio
  return post;
}

// Admin: todos los posts (incluye borradores).
export async function getAdminBlog(): Promise<BlogPost[]> {
  const res = await fetch(new URL('/api/admin/blog', apiBase()), { credentials: 'include' });
  if (!res.ok) throw new Error(`getAdminBlog failed: ${res.status}`);
  return absolutizeHero((await res.json()) as BlogPost[]);
}

export async function createBlogPost(input: BlogPostInput): Promise<{ id: number; slug: string }> {
  const res = await fetch(new URL('/api/admin/blog', apiBase()), {
    method: 'POST',
    credentials: 'include',
    body: toFormData(input),
  });
  if (!res.ok) throw new Error(await errorText(res, 'create'));
  return (await res.json()) as { id: number; slug: string };
}

export async function updateBlogPost(
  id: number,
  input: BlogPostInput,
): Promise<{ id: number; slug: string }> {
  const res = await fetch(new URL(`/api/admin/blog/${id}`, apiBase()), {
    method: 'POST',
    credentials: 'include',
    body: toFormData(input),
  });
  if (!res.ok) throw new Error(await errorText(res, 'update'));
  return (await res.json()) as { id: number; slug: string };
}

export async function deleteBlogPost(id: number): Promise<void> {
  const res = await fetch(new URL(`/api/admin/blog/${id}`, apiBase()), {
    method: 'DELETE',
    credentials: 'include',
  });
  if (!res.ok) throw new Error(`delete failed: ${res.status}`);
}

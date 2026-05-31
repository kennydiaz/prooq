// Cliente del modulo de blog. Lectura publica por sucursal (listBlog/getBlogPost)
// que cada app usa al construir su /blog en build time, + CRUD admin (cookie de
// sesion via credentials:'include'). El cuerpo viaja en Markdown.

const apiBase = (): string => import.meta.env.PUBLIC_API_URL ?? 'https://api.prooq.com';

export interface BlogPost {
  id: number;
  country: string;
  slug: string;
  title: string;
  description: string;
  body: string;
  tags: string[];
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
  heroImage?: string | null;
  author?: string;
  pubDate: string;
  updatedDate?: string | null;
  draft?: boolean;
}

async function errorText(res: Response, fallback: string): Promise<string> {
  const body = (await res.json().catch(() => ({}))) as { error?: string };
  return body.error ?? `${fallback} failed: ${res.status}`;
}

// Publico: posts publicados de una sucursal (recientes primero).
export async function listBlog(country?: string): Promise<BlogPost[]> {
  const url = new URL('/api/blog', apiBase());
  if (country) url.searchParams.set('country', country);
  const res = await fetch(url);
  if (!res.ok) throw new Error(`listBlog failed: ${res.status}`);
  return (await res.json()) as BlogPost[];
}

// Publico: un post publicado por sucursal + slug.
export async function getBlogPost(country: string, slug: string): Promise<BlogPost> {
  const res = await fetch(new URL(`/api/blog/${country}/${slug}`, apiBase()));
  if (!res.ok) throw new Error(`getBlogPost failed: ${res.status}`);
  return (await res.json()) as BlogPost;
}

// Admin: todos los posts (incluye borradores).
export async function getAdminBlog(): Promise<BlogPost[]> {
  const res = await fetch(new URL('/api/admin/blog', apiBase()), { credentials: 'include' });
  if (!res.ok) throw new Error(`getAdminBlog failed: ${res.status}`);
  return (await res.json()) as BlogPost[];
}

export async function createBlogPost(input: BlogPostInput): Promise<{ id: number; slug: string }> {
  const res = await fetch(new URL('/api/admin/blog', apiBase()), {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(input),
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
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(input),
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

import type { CountryIso2 } from '@prooq/config/tailwind.preset';

export interface GalleryItem {
  id: number;
  title: string | null;
  alt: string | null;
  caption: string | null;
  filename: string;
  mimeType: string;
  fileSizeBytes: number | null;
  widthPx: number | null;
  heightPx: number | null;
  country: CountryIso2 | null;
  displayOrder: number;
  isPublic: boolean;
  createdAt: string;
  url: string;
}

const apiBase = (): string => import.meta.env.PUBLIC_API_URL ?? 'https://api.prooq.com';

function absolutizeUrls(items: GalleryItem[]): GalleryItem[] {
  const base = apiBase().replace(/\/$/, '');
  for (const it of items) {
    if (it.url.startsWith('/')) it.url = base + it.url;
  }
  return items;
}

export async function getGallery(opts: { country?: CountryIso2 } = {}): Promise<GalleryItem[]> {
  const url = new URL('/api/gallery', apiBase());
  if (opts.country) url.searchParams.set('country', opts.country);

  const res = await fetch(url);
  if (!res.ok) throw new Error(`getGallery failed: ${res.status} ${res.statusText}`);
  return absolutizeUrls((await res.json()) as GalleryItem[]);
}

// Admin: listado completo (todos los countries, incluye non-public).
// Requiere sesion admin — el browser envia la cookie httponly automaticamente.
export async function getAdminGallery(): Promise<GalleryItem[]> {
  const url = new URL('/api/admin/gallery', apiBase());
  const res = await fetch(url, { credentials: 'include' });
  if (!res.ok) throw new Error(`getAdminGallery failed: ${res.status}`);
  return absolutizeUrls((await res.json()) as GalleryItem[]);
}

export interface UploadFields {
  title?: string;
  alt?: string;
  caption?: string;
  country?: CountryIso2 | '';
  displayOrder?: number;
}

export interface UploadResult {
  ok: true;
  id: number;
  filename: string;
  url: string;
  mime_type: string;
  file_size_bytes: number;
  width_px: number | null;
  height_px: number | null;
}

export async function uploadGalleryImage(
  file: File,
  fields: UploadFields = {},
): Promise<UploadResult> {
  const fd = new FormData();
  fd.append('image', file);
  if (fields.title) fd.append('title', fields.title);
  if (fields.alt) fd.append('alt', fields.alt);
  if (fields.caption) fd.append('caption', fields.caption);
  if (fields.country) fd.append('country', fields.country);
  if (fields.displayOrder !== undefined) fd.append('display_order', String(fields.displayOrder));

  const res = await fetch(new URL('/api/admin/gallery/upload', apiBase()), {
    method: 'POST',
    body: fd,
    credentials: 'include',
  });
  if (!res.ok) {
    const body = await res.text();
    throw new Error(`upload failed: ${res.status} ${body}`);
  }
  return (await res.json()) as UploadResult;
}

export async function deleteGalleryItem(id: number): Promise<void> {
  const res = await fetch(new URL(`/api/admin/gallery/${id}`, apiBase()), {
    method: 'DELETE',
    credentials: 'include',
  });
  if (!res.ok) throw new Error(`delete failed: ${res.status}`);
}

export interface AdminUser {
  id: number;
  username: string;
  role: 'admin' | 'editor';
}

export async function getMe(): Promise<{ authenticated: boolean; user?: AdminUser }> {
  const res = await fetch(new URL('/api/admin/me', apiBase()), { credentials: 'include' });
  if (!res.ok) throw new Error(`getMe failed: ${res.status}`);
  return (await res.json()) as { authenticated: boolean; user?: AdminUser };
}

export async function adminLogin(username: string, password: string): Promise<AdminUser> {
  const res = await fetch(new URL('/api/admin/login', apiBase()), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({ username, password }),
  });
  if (!res.ok) {
    const body = await res.json().catch(() => ({ error: 'unknown' }));
    throw new Error((body as { error?: string }).error ?? `login failed: ${res.status}`);
  }
  const data = (await res.json()) as { ok: true; user: AdminUser };
  return data.user;
}

export async function adminLogout(): Promise<void> {
  await fetch(new URL('/api/admin/logout', apiBase()), {
    method: 'POST',
    credentials: 'include',
  });
}

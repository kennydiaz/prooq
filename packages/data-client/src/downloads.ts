import type { CountryIso2 } from '@prooq/config/tailwind.preset';

export interface Download {
  id: number;
  title: string;
  description: string | null;
  filename: string | null;
  fileSizeBytes: number | null;
  mimeType: string | null;
  externalUrl: string | null;
  iconUrl: string | null;
  country: CountryIso2 | null;
  slug: string | null;
  downloadCount: number;
  isPublic: boolean;
}

export interface DownloadAdmin extends Download {
  createdAt: string;
}

export interface DownloadInput {
  title: string;
  description?: string;
  country?: CountryIso2 | '';
  slug?: string;
  externalUrl?: string;
  isPublic?: boolean;
  icon?: File | null;
  file?: File | null;
}

const apiBase = (): string => import.meta.env.PUBLIC_API_URL ?? 'https://api.prooq.com';

// Solo los iconos subidos por el panel (/uploads/...) viven en la API; los
// estaticos por sitio (/pty/images/...) los sirve cada app Astro tal cual.
function absolutizeIcons<T extends { iconUrl: string | null }>(items: T[]): T[] {
  const base = apiBase().replace(/\/$/, '');
  for (const it of items) {
    if (it.iconUrl?.startsWith('/uploads/')) it.iconUrl = base + it.iconUrl;
  }
  return items;
}

async function errorText(res: Response, fallback: string): Promise<string> {
  const body = (await res.json().catch(() => ({}))) as { error?: string };
  return body.error ?? `${fallback} failed: ${res.status}`;
}

function toFormData(input: DownloadInput): FormData {
  const fd = new FormData();
  fd.set('title', input.title);
  if (input.description) fd.set('description', input.description);
  if (input.country) fd.set('country', input.country);
  if (input.slug) fd.set('slug', input.slug);
  if (input.externalUrl) fd.set('external_url', input.externalUrl);
  fd.set('is_public', input.isPublic === false ? '0' : '1');
  if (input.icon) fd.set('icon', input.icon);
  if (input.file) fd.set('file', input.file);
  return fd;
}

export async function getDownloads(opts: { country?: CountryIso2 } = {}): Promise<Download[]> {
  const url = new URL('/api/downloads', apiBase());
  if (opts.country) url.searchParams.set('country', opts.country);

  const res = await fetch(url);
  if (!res.ok) throw new Error(`getDownloads failed: ${res.status} ${res.statusText}`);
  return absolutizeIcons((await res.json()) as Download[]);
}

export function getDownloadFileUrl(idOrSlug: number | string): string {
  return new URL(`/api/downloads/${idOrSlug}/file`, apiBase()).toString();
}

// Admin: listado completo (todos los paises, incluye non-public).
export async function getAdminDownloads(): Promise<DownloadAdmin[]> {
  const res = await fetch(new URL('/api/admin/downloads', apiBase()), { credentials: 'include' });
  if (!res.ok) throw new Error(`getAdminDownloads failed: ${res.status}`);
  return absolutizeIcons((await res.json()) as DownloadAdmin[]);
}

export async function createDownload(input: DownloadInput): Promise<{ id: number }> {
  const res = await fetch(new URL('/api/admin/downloads', apiBase()), {
    method: 'POST',
    credentials: 'include',
    body: toFormData(input),
  });
  if (!res.ok) throw new Error(await errorText(res, 'create'));
  return (await res.json()) as { id: number };
}

export async function updateDownload(id: number, input: DownloadInput): Promise<{ id: number }> {
  const res = await fetch(new URL(`/api/admin/downloads/${id}`, apiBase()), {
    method: 'POST',
    credentials: 'include',
    body: toFormData(input),
  });
  if (!res.ok) throw new Error(await errorText(res, 'update'));
  return (await res.json()) as { id: number };
}

export async function deleteDownload(id: number): Promise<void> {
  const res = await fetch(new URL(`/api/admin/downloads/${id}`, apiBase()), {
    method: 'DELETE',
    credentials: 'include',
  });
  if (!res.ok) throw new Error(`delete failed: ${res.status}`);
}

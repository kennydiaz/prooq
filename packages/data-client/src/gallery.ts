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

export async function getGallery(opts: { country?: CountryIso2 } = {}): Promise<GalleryItem[]> {
  const url = new URL('/api/gallery', apiBase());
  if (opts.country) url.searchParams.set('country', opts.country);

  const res = await fetch(url);
  if (!res.ok) throw new Error(`getGallery failed: ${res.status} ${res.statusText}`);
  const items = (await res.json()) as GalleryItem[];

  // El backend devuelve url relativa (/uploads/gallery/...). La absolutizamos
  // contra apiBase para que <img src> funcione desde cualquier sucursal.
  const base = apiBase();
  for (const it of items) {
    if (it.url.startsWith('/')) it.url = base.replace(/\/$/, '') + it.url;
  }
  return items;
}

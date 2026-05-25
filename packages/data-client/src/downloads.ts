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

const apiBase = (): string => import.meta.env.PUBLIC_API_URL ?? 'https://api.prooq.com';

export async function getDownloads(opts: { country?: CountryIso2 } = {}): Promise<Download[]> {
  const url = new URL('/api/downloads', apiBase());
  if (opts.country) url.searchParams.set('country', opts.country);

  const res = await fetch(url);
  if (!res.ok) throw new Error(`getDownloads failed: ${res.status} ${res.statusText}`);
  return (await res.json()) as Download[];
}

export function getDownloadFileUrl(idOrSlug: number | string): string {
  return new URL(`/api/downloads/${idOrSlug}/file`, apiBase()).toString();
}

import type { CountryIso2 } from '@prooq/config/tailwind.preset';

export interface Download {
  id: number;
  title: string;
  description: string | null;
  filename: string;
  fileSizeBytes: number | null;
  mimeType: string | null;
  country: CountryIso2 | null;
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

export function getDownloadUrl(id: number): string {
  return new URL(`/api/downloads/${id}/file`, apiBase()).toString();
}

import type { CountryIso2 } from '@prooq/config/tailwind.preset';

export interface Client {
  id: number;
  name: string;
  logoUrl: string | null;
  website: string | null;
  country: CountryIso2 | null;
  industry: string | null;
  displayOrder: number;
  isActive: boolean;
}

const apiBase = (): string => import.meta.env.PUBLIC_API_URL ?? 'https://api.prooq.com';

export async function getClients(opts: { country?: CountryIso2 } = {}): Promise<Client[]> {
  const url = new URL('/api/clients', apiBase());
  if (opts.country) url.searchParams.set('country', opts.country);

  const res = await fetch(url);
  if (!res.ok) throw new Error(`getClients failed: ${res.status} ${res.statusText}`);
  return (await res.json()) as Client[];
}

export interface EbopRequestInput {
  company_name: string;
  contact_name: string;
  email: string;
  phone?: string;
  message?: string;
}

export interface EbopRequestResponse {
  ok: boolean;
  id?: number;
  error?: string;
}

const apiBase = (): string => import.meta.env.PUBLIC_API_URL ?? 'https://api.prooq.com';

export async function submitEbopRequest(input: EbopRequestInput): Promise<EbopRequestResponse> {
  const url = new URL('/api/ebop', apiBase());
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(input),
  });

  const data = (await res.json().catch(() => ({}))) as Partial<EbopRequestResponse>;

  if (!res.ok) {
    return { ok: false, error: data.error ?? `submitEbopRequest failed: ${res.status}` };
  }
  return { ok: true, id: data.id };
}

// Cliente de estadisticas para el modulo Dashboard del admin. Requiere sesion
// admin (cookie httponly enviada con credentials: 'include').

const apiBase = (): string => import.meta.env.PUBLIC_API_URL ?? 'https://api.prooq.com';

export interface VisitsSummary {
  total: number;
  uniqueIps: number;
  today: number;
  last7: number;
  last30: number;
  countries: number;
  topCountry: { country: string; count: number } | null;
}

export interface TimeseriesPoint {
  date: string; // YYYY-MM-DD
  count: number;
}

export interface CountryCount {
  country: string; // ISO-2 o "??" si desconocido
  count: number;
}

export interface RecentVisit {
  id: number;
  path: string;
  site: string | null;
  country: string | null;
  ip: string | null;
  createdAt: string;
}

async function getJson<T>(path: string): Promise<T> {
  const res = await fetch(new URL(path, apiBase()), { credentials: 'include' });
  if (!res.ok) throw new Error(`${path} failed: ${res.status}`);
  return (await res.json()) as T;
}

export const getVisitsSummary = (): Promise<VisitsSummary> =>
  getJson<VisitsSummary>('/api/admin/stats/visits/summary');

export const getVisitsTimeseries = (days = 14): Promise<TimeseriesPoint[]> =>
  getJson<TimeseriesPoint[]>(`/api/admin/stats/visits/timeseries?days=${days}`);

export const getVisitsByCountry = (limit = 8): Promise<CountryCount[]> =>
  getJson<CountryCount[]>(`/api/admin/stats/visits/by-country?limit=${limit}`);

export const getRecentVisits = (limit = 50): Promise<RecentVisit[]> =>
  getJson<RecentVisit[]>(`/api/admin/stats/visits/recent?limit=${limit}`);

// Cliente del modulo de equipo. Lectura publica (getTeam) + CRUD admin (cookie
// de sesion via credentials:'include'). Relacion M:N: un miembro puede estar en
// varias sucursales (countries).

const apiBase = (): string => import.meta.env.PUBLIC_API_URL ?? 'https://api.prooq.com';

export interface TeamMemberPublic {
  id: number;
  name: string;
  role: string | null;
  bio: string | null;
  email: string | null;
  country: string;
  displayOrder: number;
  photoUrl: string | null;
}

export interface TeamMemberAdmin {
  id: number;
  name: string;
  role: string | null;
  bio: string | null;
  email: string | null;
  isActive: boolean;
  createdAt: string;
  photoUrl: string | null;
  countries: string[];
}

export interface TeamMemberInput {
  name: string;
  role?: string;
  bio?: string;
  email?: string;
  countries: string[];
  active?: boolean;
  photo?: File | null;
}

function absolutize<T extends { photoUrl: string | null }>(items: T[]): T[] {
  const base = apiBase().replace(/\/$/, '');
  for (const it of items) {
    if (it.photoUrl?.startsWith('/')) it.photoUrl = base + it.photoUrl;
  }
  return items;
}

async function errorText(res: Response, fallback: string): Promise<string> {
  const body = (await res.json().catch(() => ({}))) as { error?: string };
  return body.error ?? `${fallback} failed: ${res.status}`;
}

function toFormData(input: TeamMemberInput): FormData {
  const fd = new FormData();
  fd.set('name', input.name);
  if (input.role) fd.set('role', input.role);
  if (input.bio) fd.set('bio', input.bio);
  if (input.email) fd.set('email', input.email);
  fd.set('countries', input.countries.join(','));
  fd.set('active', input.active === false ? '0' : '1');
  if (input.photo) fd.set('photo', input.photo);
  return fd;
}

// Publico: equipo de una sucursal (o todos si se omite country).
export async function getTeam(country?: string): Promise<TeamMemberPublic[]> {
  const url = new URL('/api/team', apiBase());
  if (country) url.searchParams.set('country', country);
  const res = await fetch(url);
  if (!res.ok) throw new Error(`getTeam failed: ${res.status}`);
  return absolutize((await res.json()) as TeamMemberPublic[]);
}

// Admin: todos los miembros con su lista de paises.
export async function getAdminTeam(): Promise<TeamMemberAdmin[]> {
  const res = await fetch(new URL('/api/admin/team', apiBase()), { credentials: 'include' });
  if (!res.ok) throw new Error(`getAdminTeam failed: ${res.status}`);
  return absolutize((await res.json()) as TeamMemberAdmin[]);
}

export async function createTeamMember(input: TeamMemberInput): Promise<{ id: number }> {
  const res = await fetch(new URL('/api/admin/team', apiBase()), {
    method: 'POST',
    credentials: 'include',
    body: toFormData(input),
  });
  if (!res.ok) throw new Error(await errorText(res, 'create'));
  return (await res.json()) as { id: number };
}

export async function updateTeamMember(
  id: number,
  input: TeamMemberInput,
): Promise<{ id: number }> {
  const res = await fetch(new URL(`/api/admin/team/${id}`, apiBase()), {
    method: 'POST',
    credentials: 'include',
    body: toFormData(input),
  });
  if (!res.ok) throw new Error(await errorText(res, 'update'));
  return (await res.json()) as { id: number };
}

export async function deleteTeamMember(id: number): Promise<void> {
  const res = await fetch(new URL(`/api/admin/team/${id}`, apiBase()), {
    method: 'DELETE',
    credentials: 'include',
  });
  if (!res.ok) throw new Error(`delete failed: ${res.status}`);
}

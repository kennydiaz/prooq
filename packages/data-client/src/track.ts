// Tracking de visitas (analytics). Lo usan las paginas publicas para registrar
// cada vista. La IP y el user-agent los captura la API server-side; aqui solo
// enviamos path, site y referrer.

const apiBase = (): string => import.meta.env.PUBLIC_API_URL ?? 'https://api.prooq.com';

export interface TrackVisitInput {
  /** Ruta visitada, p. ej. "/pty/servicios". */
  path: string;
  /** Sucursal: pty | usa | esp | ven | portal. */
  site?: string;
  /** Referrer (document.referrer), opcional. */
  referrer?: string;
}

/**
 * Registra la visita actual. Fire-and-forget: nunca lanza ni bloquea el render.
 * En desarrollo local NO envia a la API de produccion (evita ensuciar el
 * analytics real con visitas de dev).
 */
export function trackVisit(input: TrackVisitInput): void {
  const base = apiBase();
  const host = typeof location !== 'undefined' ? location.hostname : '';
  const isLocalHost = host === 'localhost' || host === '127.0.0.1';
  if (isLocalHost && base.includes('api.prooq.com')) return;

  try {
    void fetch(new URL('/api/track/visit', base), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(input),
      keepalive: true,
    }).catch(() => {});
  } catch {
    /* noop — el tracking jamas debe romper la pagina */
  }
}

/**
 * Catálogo comercial de SuiteHub tal como se publica en prooq.com.
 *
 * FUENTE CANÓNICA REAL: panel.suitehub.net/?r=catalog/json
 * Este archivo es el respaldo local, igual que src/data/catalogo.json en el
 * repo de suitehub. Si divergen, manda el panel.
 *
 * Modelo vigente desde el 6 de septiembre de 2026 ("modelo simple").
 * Ningún material anterior a esa fecha sirve como referencia de precios.
 */

export const SUITEHUB = {
  url: 'https://suitehub.net/',
  /** Precio de entrada: edición Hub Lite, USD al mes. */
  priceFromUsd: 24,
  /** Ediciones: Hub Lite, Hub Core, Hub Pro, Hub Enterprise. */
  editionCount: 4,
  /** Verticales publicadas en suitehub.net. */
  verticalCount: 15,
  /** Jurisdicciones con facturación electrónica resuelta o en soft launch. */
  countryCount: 4,
} as const;

export interface SuiteHubVertical {
  name: string;
  /** Slug del kit de marca: badge en public/images/suitehub/hub-<slug>-badge.svg. */
  slug: string;
  /** Página del vertical en suitehub.net. Si aún no tiene, apunta al listado. */
  url: string;
}

const verticalUrl = (slug?: string): string =>
  slug ? `${SUITEHUB.url}verticales/${slug}/` : `${SUITEHUB.url}verticales/`;

/**
 * Verticales destacadas en la home de Panamá.
 * OJO: es "Salon", nunca "Beauty". El rename se hizo en septiembre de 2026
 * y quedó rezagado en este sitio.
 * Pet, Clinic, Gym y Hotel todavía no tienen página propia en suitehub.net
 * (404 en septiembre de 2026): enlazan al listado de verticales.
 */
export const SUITEHUB_FEATURED_VERTICALS: readonly SuiteHubVertical[] = [
  { name: 'HUB Taller', slug: 'taller', url: verticalUrl('taller') },
  { name: 'HUB Restaurant', slug: 'restaurant', url: verticalUrl('restaurant') },
  { name: 'HUB POS', slug: 'pos', url: verticalUrl('pos') },
  { name: 'HUB Pet', slug: 'pet', url: verticalUrl() },
  { name: 'HUB Salon', slug: 'salon', url: verticalUrl('salon') },
  { name: 'HUB Clinic', slug: 'clinic', url: verticalUrl() },
  { name: 'HUB Gym', slug: 'gym', url: verticalUrl() },
  { name: 'HUB Hotel', slug: 'hotel', url: verticalUrl() },
];

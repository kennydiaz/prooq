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

/**
 * Verticales destacadas en la home de Panamá.
 * OJO: es "Salon", nunca "Beauty". El rename se hizo en septiembre de 2026
 * y quedó rezagado en este sitio.
 */
export const SUITEHUB_FEATURED_VERTICALS = [
  'HUB Taller',
  'HUB Restaurant',
  'HUB POS',
  'HUB Pet',
  'HUB Salon',
  'HUB Clinic',
  'HUB Gym',
  'HUB Hotel',
] as const;

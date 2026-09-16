/**
 * Datos corporativos del grupo PROOQ.
 *
 * FUENTE ÚNICA DE VERDAD. Ninguna app debe hardcodear estos valores.
 * Si un dato cambia, cambia aquí y se propaga a las 5 apps en el próximo build.
 */

/** Entidad legal panameña. Es la que factura y la que va a licitaciones. */
export const PROOQ_SA = {
  legalName: 'PROOQ, S.A.',
  displayName: 'PROOQ S.A.',
  country: 'PA',
  foundedYear: 2013,
  ruc: '2648864-1-840469',
  dv: '43',
  taxId: '2648864-1-840469 DV 43',
  address: {
    street: 'Vía Argentina, Edificio Alejandra, Oficina 3',
    locality: 'Bella Vista, Ciudad de Panamá',
    country: 'PA',
  },
  phone: '+507 394-7751',
  mobile: '+507 6208-2617',
  email: 'info@prooq.com',
  dgiAuthorized: true,
} as const;

/** Entidad legal estadounidense. Titular de la marca SuiteHub. */
export const PROOQ_LLC = {
  legalName: 'PROOQ LLC',
  displayName: 'PROOQ LLC',
  country: 'US',
  foundedYear: 2025,
  address: {
    street: '2512 N 72nd Ct',
    locality: 'Elmwood Park, IL',
    country: 'US',
  },
  email: 'info@prooq.com',
} as const;

/**
 * Experiencia del grupo y su equipo fundador.
 * NO es la edad de ninguna entidad legal: es trayectoria acumulada.
 * Se muestra idéntico en las cuatro sucursales.
 */
export const GROUP_EXPERIENCE_YEARS = 27;

/** Año en que arranca la trayectoria del equipo, antes de constituir PROOQ S.A. */
export const GROUP_TRAJECTORY_SINCE = 1999;

/** Instancias de software SuiteHub activas. Distinto de clientes de infraestructura. */
export const SUITEHUB_ACTIVE_INSTANCES = 30;

/** Certificaciones de fabricante y acreditaciones vigentes. */
export const CERTIFICATIONS = [
  'Cisco CCNA',
  'Dahua',
  'Hikvision',
  'Grandstream',
  'FAA Part 107',
  'Adobe',
] as const;

/**
 * Formulario público de postulación de HubPro (atajo de modules/personal/postular.php).
 * Cada envío entra a HubPro como "candidato nuevo por revisar", así que todas las
 * sucursales enlazan aquí en vez de tener un formulario propio.
 */
export const JOBS_APPLICATION_URL = 'https://hubpro.prooq.com/empleo.php';

/** PAC autorizados por la DGI con los que hay integración propia. */
export const DGI_PAC_COUNT = 5;

// One-shot: genera INSERT SQL para los 14 downloads + portals del V1 panama.
// 10 file downloads (.exe) + 4 portal links externos.
//
// Uso: node scripts/seed-downloads.mjs > /tmp/downloads-seed.sql

const escape = (s) =>
  s === null || s === undefined
    ? 'NULL'
    : "'" + String(s).replace(/\\/g, '\\\\').replace(/'/g, "''") + "'";
const num = (n) => (n === null || n === undefined ? 'NULL' : String(n));

const ICON_BASE = '/pty/images/downloads/';

const rows = [
  // Sección 1: Asistencia remota + Soft. administrativos
  {
    slug: 'anydesk',
    title: 'AnyDesk',
    description: 'App para asistencia remota.',
    filename: 'AnyDesk.exe',
    file_size_bytes: 8388608,
    icon: 'anydesk.webp',
  },
  {
    slug: 'rustdesk',
    title: 'Rustdesk',
    description: 'App para asistencia remota.',
    filename: 'rustdesk.exe',
    file_size_bytes: 25165824,
    icon: 'rustdesk.jpg',
  },
  {
    slug: 'innovalite',
    title: 'InnovaSoft Lite',
    description: 'Sistema administrativo para microempresas y PYMES.',
    filename: 'innovasoftlite.exe',
    file_size_bytes: 33554432,
    icon: 'innovasoft-lite.png',
  },
  {
    slug: 'innovapro',
    title: 'InnovaSoft Pro',
    description: 'Sistema administrativo multi-módulo avanzado.',
    filename: 'innovasoftpro.exe',
    file_size_bytes: 90177536,
    icon: 'innovasoft-pro.png',
  },
  {
    slug: 'retails',
    title: 'RetailsPOS',
    description: 'Sistema de punto de venta.',
    filename: 'retailspos.exe',
    file_size_bytes: 102760448,
    icon: 'retailspos.png',
  },

  // Sección 2: Portals externos (sin filename, con external_url)
  {
    slug: 'portal-innovasoft',
    title: 'Portal InnovaSoft',
    description: 'Plataforma de licenciamiento.',
    external_url: 'https://plataforma.innovasoftlatam.com/',
    icon: 'licenciamiento.png',
  },
  {
    slug: 'portal-digifact',
    title: 'Folios DigiFact',
    description: 'Portal de gestión de folios Digifact.',
    external_url: 'https://fepa.digifact.com.pa/',
    icon: 'digifact.jpg',
  },
  {
    slug: 'portal-thefactory',
    title: 'Folios The Factory HKA',
    description: 'Portal de gestión de folios The Factory.',
    external_url: 'https://distribuidores.thefactoryhka.com.pa/',
    icon: 'the_factory.jpg',
  },
  {
    slug: 'portal-facturafacil',
    title: 'Factura Fácil',
    description: 'Portal de facturación electrónica.',
    external_url: 'https://panel.facturafacil.com.pa/pages/login',
    icon: 'factura_facil.png',
  },

  // Sección 3: Utilitarios técnicos (CCTV + redes)
  {
    slug: 'smartpss',
    title: 'Smart PSS',
    description: 'Software de gestión de cámaras Dahua.',
    filename: 'smartpss.exe',
    file_size_bytes: 122683392,
    icon: 'smartpss.jpg',
  },
  {
    slug: 'ivms',
    title: 'iVMS-4200',
    description: 'Software de gestión de cámaras Hikvision.',
    filename: 'ivms4200.exe',
    file_size_bytes: 348127232,
    icon: 'ivms-4200.png',
  },
  {
    slug: 'sadp',
    title: 'SADP',
    description: 'Utilitario para cámaras Hikvision.',
    filename: 'sadp.exe',
    file_size_bytes: 68157440,
    icon: 'sadp.jpg',
  },
  {
    slug: 'configtool',
    title: 'Config Tool',
    description: 'Utilitario para cámaras Dahua.',
    filename: 'configtool.exe',
    file_size_bytes: 35651584,
    icon: 'configtool.jpg',
  },
  {
    slug: 'ipscanner',
    title: 'Advanced IP Scanner',
    description: 'Utilitario para escaneo de redes.',
    filename: 'ipscanner.exe',
    file_size_bytes: 22020096,
    icon: 'ipscanner.png',
  },
];

const values = rows
  .map((r) => {
    const isExternal = !!r.external_url;
    return `(${escape(r.title)}, ${escape(r.description)}, ${isExternal ? 'NULL' : escape(r.filename)}, ${isExternal ? 'NULL' : num(r.file_size_bytes)}, ${isExternal ? 'NULL' : "'application/octet-stream'"}, ${escape(r.external_url)}, ${escape(ICON_BASE + r.icon)}, 'PA', ${escape(r.slug)}, 1)`;
  })
  .join(',\n');

process.stdout.write(
  '-- 14 entries: 10 file downloads + 4 portals externos\n' +
    'INSERT INTO downloads (title, description, filename, file_size_bytes, mime_type, external_url, icon_url, country, slug, is_public) VALUES\n' +
    values +
    ';\n',
);

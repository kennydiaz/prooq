/**
 * Catálogo de servicios de esta sucursal. Fuente única: lo usan la página
 * /servicios (catálogo completo) y la home (vista previa con enlace al catálogo).
 */
export interface DigitalService {
  icon: string;
  title: string;
  description: string;
  features: readonly string[];
  highlight?: boolean;
}

export const SERVICES: readonly DigitalService[] = [
  {
    icon: '🌐',
    title: 'Desarrollo Web',
    description: 'Soluciones web profesionales para empresas y negocios.',
    features: ['Landing pages', 'Sitios corporativos', 'E-commerce', 'Aplicaciones web'],
  },
  {
    icon: '💻',
    title: 'Instalación y Activación de Software',
    description: 'Instalación profesional de software y paquetes empresariales.',
    features: [
      'Licencias originales',
      'Suite ofimática',
      'Software contable',
      'Soporte post-instalación',
    ],
  },
  {
    icon: '📡',
    title: 'Redes y Conectividad',
    description: 'Optimización y configuración de redes empresariales y residenciales.',
    features: [
      'Wi-Fi empresarial',
      'Switches y routers',
      'Cableado estructurado',
      'Diagnóstico de red',
    ],
  },
  {
    icon: '🖨️',
    title: 'Impresoras y Periféricos',
    description: 'Instalación y configuración de impresoras y periféricos de oficina.',
    features: ['Impresoras multifunción', 'Escáneres', 'POS y lectoras', 'Mantenimiento'],
  },
  {
    icon: '🔒',
    title: 'Seguridad Digital',
    description: 'Protección y optimización de sistemas y dispositivos.',
    features: ['Antivirus empresariales', 'Firewalls', 'Backups automáticos', 'Auditorías'],
  },
  {
    icon: '🖥️',
    title: 'Servicios Remotos y Asistencia Técnica',
    description: 'Soporte y soluciones rápidas sin visita presencial.',
    features: [
      'Acceso remoto seguro',
      'Soporte por chat/video',
      'Diagnóstico en línea',
      'Reparaciones express',
    ],
  },
  {
    icon: '☁️',
    title: 'Servicios en la Nube',
    description: 'Configuración y gestión de servicios cloud empresariales.',
    features: [
      'Microsoft 365',
      'Google Workspace',
      'Almacenamiento en la nube',
      'Migración a la nube',
    ],
  },
  {
    icon: '⚡',
    title: 'Optimización y Mantenimiento Digital',
    description: 'Mejora de rendimiento y mantenimiento profesional de sistemas.',
    features: [
      'Tuning de rendimiento',
      'Limpieza de sistemas',
      'Actualizaciones',
      'Plan preventivo',
    ],
  },
  {
    icon: '🛠️',
    title: 'Asesorías y Configuraciones Especiales',
    description: 'Soluciones personalizadas y configuración de dispositivos inteligentes.',
    features: [
      'Setup IoT/Smart Home',
      'Personalización avanzada',
      'Migración de datos',
      'Recuperación de información',
    ],
  },
  {
    icon: '🤖',
    title: 'Agentes Inteligentes con n8n',
    description: 'Automatización avanzada y agentes inteligentes para empresas.',
    features: ['Workflows N8N', 'Chatbots IA', 'Integraciones API', 'Procesos automatizados'],
    highlight: true,
  },
];

/** Servicios que se muestran en la home: primero los destacados, luego el resto en orden. */
export const HOME_SERVICES: readonly DigitalService[] = [
  ...SERVICES.filter((s) => s.highlight),
  ...SERVICES.filter((s) => !s.highlight),
].slice(0, 6);

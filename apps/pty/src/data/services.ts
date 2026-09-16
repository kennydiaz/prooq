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
    features: [
      'Landing pages',
      'Sitios corporativos y empresariales',
      'E-commerce',
      'Webs administrables (WordPress / CMS personalizados)',
      'Formularios dinámicos e integraciones',
      'SEO básico e implementación de Analytics',
    ],
  },
  {
    icon: '💻',
    title: 'Instalación y Activación de Software',
    description: 'Instalación profesional de software y paquetes empresariales.',
    features: [
      'Microsoft Office / Office 365',
      'Antivirus',
      'Configuración de software empresarial',
      'Activación de licencias',
      'Actualización y mantenimiento',
    ],
  },
  {
    icon: '🔌',
    title: 'Redes Cableadas y WiFi',
    description: 'Instalación y configuración de redes empresariales.',
    features: [
      'Redes cableadas y WiFi',
      'Configuración de routers y switches',
      'Diagnóstico y optimización',
      'Instalación de puntos de red',
    ],
  },
  {
    icon: '📹',
    title: 'Seguridad Electrónica',
    description: 'Videovigilancia y control de acceso profesional.',
    features: [
      'Videovigilancia (CCTV)',
      'Control de acceso',
      'Alarmas y sensores',
      'Integración con móviles',
    ],
  },
  {
    icon: '🛠️',
    title: 'Asistencia Técnica',
    description: 'Soporte especializado y mantenimiento preventivo.',
    features: [
      'Soporte remoto y presencial',
      'Solución de incidencias',
      'Mantenimiento preventivo',
      'Asesoría técnica',
    ],
  },
  {
    icon: '🤖',
    title: 'Automatización e IA',
    description: 'Automatización de procesos y agentes inteligentes con n8n.',
    features: [
      'Automatización de procesos',
      'Integración de sistemas',
      'Agentes inteligentes (n8n)',
      'Desarrollo de scripts',
    ],
    highlight: true,
  },
  {
    icon: '🔒',
    title: 'Ciberseguridad',
    description: 'Protección de datos y auditoría de sistemas.',
    features: [
      'Protección de datos',
      'Firewall y seguridad perimetral',
      'Auditoría de sistemas',
      'Capacitación en ciberseguridad',
    ],
  },
  {
    icon: '💡',
    title: 'Consultoría Tecnológica',
    description: 'Asesoría tecnológica y propuestas de mejora.',
    features: [
      'Evaluación de infraestructura',
      'Propuestas de mejora',
      'Selección de tecnología',
      'Implementación de soluciones',
    ],
  },
  {
    icon: '🧑‍💻',
    title: 'Sistemas Administrativos',
    description: 'Software a medida para optimizar la gestión de tu empresa.',
    features: ['Integración ERP', 'Dashboards', 'Automatización de procesos', 'Analítica de datos'],
  },
  {
    icon: '🛠️',
    title: 'Automatización y Control Inteligente',
    description: 'Soluciones para automatizar procesos, edificios y sistemas de seguridad.',
    features: [
      'Automatización de edificios',
      'IoT',
      'Sensores inteligentes',
      'Control de procesos',
    ],
  },
];

/** Servicios que se muestran en la home: primero los destacados, luego el resto en orden. */
export const HOME_SERVICES: readonly DigitalService[] = [
  ...SERVICES.filter((s) => s.highlight),
  ...SERVICES.filter((s) => !s.highlight),
].slice(0, 6);

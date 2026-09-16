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
    icon: '💻',
    title: 'Desarrollo Web',
    description:
      'Sitios web modernos, responsivos y optimizados. Desde landing pages hasta aplicaciones web complejas.',
    features: [
      'Diseño responsive y moderno',
      'Optimización SEO',
      'E-commerce y tiendas online',
      'Aplicaciones web personalizadas',
      'CMS administrables',
    ],
    highlight: true,
  },
  {
    icon: '🤖',
    title: 'Agentes Inteligentes',
    description: 'Chatbots e IA conversacional para automatizar la atención al cliente 24/7.',
    features: [
      'Chatbots inteligentes con n8n',
      'Asistentes virtuales',
      'Automatización de procesos',
      'Integración con sistemas existentes',
      'Workflows IA personalizados',
    ],
    highlight: true,
  },
  {
    icon: '📱',
    title: 'Consultoría Digital',
    description:
      'Asesoramiento especializado en transformación digital para empresas en Cádiz y Andalucía.',
    features: [
      'Estrategia digital',
      'Análisis de procesos',
      'Implementación tecnológica',
      'Formación y soporte',
    ],
  },
];

/** Servicios que se muestran en la home: primero los destacados, luego el resto en orden. */
export const HOME_SERVICES: readonly DigitalService[] = [
  ...SERVICES.filter((s) => s.highlight),
  ...SERVICES.filter((s) => !s.highlight),
].slice(0, 6);

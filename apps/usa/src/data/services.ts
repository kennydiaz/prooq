/**
 * Catálogo de servicios de esta sucursal. Fuente única: lo usan la página
 * /services (catálogo completo) y la home (vista previa con enlace al catálogo).
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
    icon: '📹',
    title: 'Electronic Security',
    description: 'Advanced solutions to protect your home and business.',
    features: [
      'CCTV & video surveillance',
      'Access control systems',
      'Smart alarms',
      '24/7 monitoring',
    ],
  },
  {
    icon: '🔌',
    title: 'Wired Networks',
    description: 'Installation and maintenance of high-quality structured cabling and networks.',
    features: [
      'Cat6/Cat7 cabling',
      'Network design',
      'Switch & router config',
      'Maintenance plans',
    ],
  },
  {
    icon: '🧑‍💻',
    title: 'Administrative Systems',
    description: 'Custom software to optimize your business management.',
    features: ['ERP integration', 'Custom dashboards', 'Workflow automation', 'Data analytics'],
  },
  {
    icon: '🛠️',
    title: 'Technical Support',
    description: 'Specialized technical support for all our services.',
    features: [
      'On-site visits',
      'Remote troubleshooting',
      'SLA support plans',
      'Emergency response',
    ],
  },
  {
    icon: '🤖',
    title: 'Automation & Smart Control',
    description: 'Solutions to automate processes, buildings, and security systems.',
    features: ['Building automation', 'IoT integration', 'Smart sensors', 'Process control'],
    highlight: true,
  },
  {
    icon: '🔒',
    title: 'Cybersecurity & Data Protection',
    description:
      'Protect your information with access control, encryption, and continuous monitoring.',
    features: ['Threat detection', 'Encryption at rest', 'Access policies', 'Compliance audits'],
  },
  {
    icon: '🌐',
    title: 'Network & Server Infrastructure',
    description: 'Design and management of local, remote, and cloud networks.',
    features: [
      'Server deployment',
      'VPN & remote access',
      'Cloud infrastructure',
      'Load balancing',
    ],
  },
  {
    icon: '💡',
    title: 'Technology Consulting',
    description: 'We advise companies on efficient technology adoption.',
    features: ['Digital transformation', 'Tech stack audits', 'Vendor selection', 'Roadmaps'],
  },
  {
    icon: '🛠️',
    title: 'Comprehensive Projects',
    description: 'End-to-end implementations from design to deployment.',
    features: [
      'Project management',
      'Multi-vendor coordination',
      'Phased rollouts',
      'Post-launch support',
    ],
  },
];

/** Servicios que se muestran en la home: primero los destacados, luego el resto en orden. */
export const HOME_SERVICES: readonly DigitalService[] = [
  ...SERVICES.filter((s) => s.highlight),
  ...SERVICES.filter((s) => !s.highlight),
].slice(0, 6);

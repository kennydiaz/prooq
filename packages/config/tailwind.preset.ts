// Tokens espejo del @theme de packages/styles/tokens.css.
// Tailwind v4 vive en CSS, pero estos valores se consumen también desde
// Astro frontmatter, Alpine.js y scripts de build — por eso el duplicado en TS.
// Si cambias un valor aquí, sincroniza también tokens.css.

export const colors = {
  brand: {
    DEFAULT: '#003cff',
    deep: '#00014d',
    darker: '#0028cc',
    light: '#4d9fff',
  },
  accent: {
    cyan: '#00ffe7',
    cyanSoft: '#00c6ff',
    neon: '#00ffae',
    neonGreen: '#00ff63',
    gold: '#c9a227',
    amber: '#ffb300',
  },
  surface: {
    DEFAULT: '#050d1f',
    raised: '#0a1833',
    deep: '#001f54',
  },
  text: {
    DEFAULT: '#ffffff',
    soft: '#ececec',
    secondary: '#e0e0e0',
    muted: '#94a3b8',
    subtle: '#64748b',
    onLight: '#111111',
  },
} as const;

export const fonts = {
  display: ['Orbitron', 'system-ui', 'sans-serif'],
  body: ['Montserrat', 'system-ui', 'sans-serif'],
  portal: ['Poppins', 'system-ui', 'sans-serif'],
} as const;

export const breakpoints = {
  sm: '640px',
  md: '768px',
  lg: '1024px',
  xl: '1280px',
  '2xl': '1536px',
} as const;

export type Country = 'pty' | 'usa' | 'esp' | 'ven';
export type CountryIso2 = 'PA' | 'US' | 'ES' | 'VE';

export const COUNTRY_TO_ISO2: Record<Country, CountryIso2> = {
  pty: 'PA',
  usa: 'US',
  esp: 'ES',
  ven: 'VE',
};

export const ISO2_TO_COUNTRY: Record<CountryIso2, Country> = {
  PA: 'pty',
  US: 'usa',
  ES: 'esp',
  VE: 'ven',
};

// Tokens espejo del @theme de packages/styles/tokens.css.
// Tailwind v4 vive en CSS, pero estos valores se consumen también desde
// Astro frontmatter, Alpine.js y scripts de build — por eso el duplicado en TS.

export const colors = {
  brand: {
    DEFAULT: '#0a2540',
    accent: '#00d4ff',
    neon: '#39ff14',
  },
  surface: {
    DEFAULT: '#0b0f17',
    raised: '#111827',
  },
  text: {
    DEFAULT: '#e5e7eb',
    muted: '#9ca3af',
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

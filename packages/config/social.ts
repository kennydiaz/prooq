export const SOCIAL_LINKS = [
  { name: 'YouTube', handle: '@prooqsa', url: 'https://www.youtube.com/@prooqsa', icon: 'youtube' },
  {
    name: 'Instagram',
    handle: '@prooq',
    url: 'https://www.instagram.com/prooq/',
    icon: 'instagram',
  },
  { name: 'Facebook', handle: '/prooq', url: 'https://www.facebook.com/prooq/', icon: 'facebook' },
  { name: 'X (Twitter)', handle: '@prooqllc', url: 'https://x.com/prooqllc', icon: 'x' },
  {
    name: 'LinkedIn',
    handle: 'company/75542387',
    url: 'https://www.linkedin.com/company/75542387/',
    icon: 'linkedin',
  },
  { name: 'TikTok', handle: '@prooqsa', url: 'https://www.tiktok.com/@prooqsa', icon: 'tiktok' },
  { name: 'GitHub', handle: '/prooq', url: 'https://github.com/prooq', icon: 'github' },
  {
    name: 'Google Business',
    handle: 'share',
    url: 'https://share.google/FAt5Z7HtEK2NiKFsK',
    icon: 'google',
  },
  {
    name: 'WhatsApp',
    handle: '+507 6208-2617',
    url: 'https://wa.me/50762082617',
    icon: 'whatsapp',
  },
] as const;

export type SocialLink = (typeof SOCIAL_LINKS)[number];
export type SocialIcon = SocialLink['icon'];
export type SocialName = SocialLink['name'];

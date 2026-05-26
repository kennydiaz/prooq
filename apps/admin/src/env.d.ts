/// <reference types="astro/client" />

declare global {
  interface Window {
    __ADMIN_BASE__?: string;
    __SKIP_AUTH_CHECK__?: boolean;
  }
}

export {};

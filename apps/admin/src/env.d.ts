/// <reference types="astro/client" />

declare global {
  interface Window {
    __ADMIN_BASE__?: string;
  }
}

export {};

import type { CountryIso2 } from '@prooq/config/tailwind.preset';

export type ChatRole = 'user' | 'assistant';

export interface ChatMessageInput {
  conversationId: string;
  message: string;
  sourceSite?: CountryIso2;
}

export interface ChatResponse {
  reply: string;
  conversationId: string;
}

const apiBase = (): string => import.meta.env.PUBLIC_API_URL ?? 'https://api.prooq.com';

export async function sendChatMessage(input: ChatMessageInput): Promise<ChatResponse> {
  const url = new URL('/api/chat', apiBase());
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(input),
  });
  if (!res.ok) throw new Error(`sendChatMessage failed: ${res.status} ${res.statusText}`);
  return (await res.json()) as ChatResponse;
}

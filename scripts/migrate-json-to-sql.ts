#!/usr/bin/env tsx
// One-shot: lee panama/data/{clients,downloads}.json del V1 y emite INSERTs SQL en db/seeds/.
// Uso: pnpm tsx scripts/migrate-json-to-sql.ts
// Pre-requisitos: `pnpm add -D tsx` en el workspace root.

import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { resolve } from 'node:path';

const V1_ROOT = process.env.PROOQ_V1_ROOT ?? 'c:/xampp/htdocs/prooq';
const OUT_DIR = resolve('db/seeds');

interface V1Client {
  name?: string;
  logo?: string;
  website?: string;
  industry?: string;
}

interface V1Download {
  title?: string;
  description?: string;
  filename?: string;
  size?: number;
  mime?: string;
}

const esc = (v: unknown): string => {
  if (v === null || v === undefined || v === '') return 'NULL';
  const s = String(v).replace(/\\/g, '\\\\').replace(/'/g, "''");
  return `'${s}'`;
};

async function migrateClients(): Promise<void> {
  const path = resolve(V1_ROOT, 'panama/data/clients.json');
  const raw = await readFile(path, 'utf8');
  const clients = JSON.parse(raw) as V1Client[];

  const inserts = clients.map(
    (c, i) =>
      `INSERT INTO clients (name, logo_url, website, country, industry, display_order, is_active) VALUES (` +
      `${esc(c.name)}, ${esc(c.logo)}, ${esc(c.website)}, 'PA', ${esc(c.industry)}, ${i}, 1);`,
  );

  const out = `-- Seed: clients (migrado de ${path})\n${inserts.join('\n')}\n`;
  await writeFile(resolve(OUT_DIR, 'clients.sql'), out, 'utf8');
  console.log(`✓ Wrote ${inserts.length} clients → db/seeds/clients.sql`);
}

async function migrateDownloads(): Promise<void> {
  const path = resolve(V1_ROOT, 'panama/data/downloads.json');
  const raw = await readFile(path, 'utf8');
  const downloads = JSON.parse(raw) as V1Download[];

  const inserts = downloads.map(
    (d) =>
      `INSERT INTO downloads (title, description, filename, file_size_bytes, mime_type, country, is_public) VALUES (` +
      `${esc(d.title)}, ${esc(d.description)}, ${esc(d.filename)}, ${d.size ?? 'NULL'}, ${esc(d.mime)}, 'PA', 1);`,
  );

  const out = `-- Seed: downloads (migrado de ${path})\n${inserts.join('\n')}\n`;
  await writeFile(resolve(OUT_DIR, 'downloads.sql'), out, 'utf8');
  console.log(`✓ Wrote ${inserts.length} downloads → db/seeds/downloads.sql`);
}

await mkdir(OUT_DIR, { recursive: true });
await Promise.all([migrateClients(), migrateDownloads()]);
console.log('Done.');

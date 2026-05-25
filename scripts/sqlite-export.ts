#!/usr/bin/env tsx
// One-shot: lee panama/data/prooq.db (SQLite) y emite INSERTs SQL en db/seeds/.
// Uso: pnpm tsx scripts/sqlite-export.ts
// Pre-requisitos: `pnpm add -D tsx better-sqlite3 @types/better-sqlite3`.

import { mkdir, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import Database from 'better-sqlite3';

const V1_DB = process.env.PROOQ_V1_DB ?? 'c:/xampp/htdocs/prooq/panama/data/prooq.db';
const OUT_DIR = resolve('db/seeds');

const esc = (v: unknown): string => {
  if (v === null || v === undefined) return 'NULL';
  if (typeof v === 'number') return String(v);
  if (typeof v === 'bigint') return v.toString();
  const s = String(v).replace(/\\/g, '\\\\').replace(/'/g, "''");
  return `'${s}'`;
};

await mkdir(OUT_DIR, { recursive: true });

const db = new Database(V1_DB, { readonly: true });

const tables = db
  .prepare("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
  .all() as { name: string }[];

console.log(`Found ${tables.length} tables: ${tables.map((t) => t.name).join(', ')}`);

for (const { name } of tables) {
  const rows = db.prepare(`SELECT * FROM ${name}`).all() as Record<string, unknown>[];
  if (rows.length === 0) {
    console.log(`- ${name}: empty, skipping`);
    continue;
  }

  const firstRow = rows[0];
  if (!firstRow) continue;
  const columns = Object.keys(firstRow);

  const inserts = rows.map((row) => {
    const values = columns.map((c) => esc(row[c])).join(', ');
    return `INSERT INTO ${name} (${columns.join(', ')}) VALUES (${values});`;
  });

  const out = `-- Seed: ${name} (exportado de ${V1_DB})\n${inserts.join('\n')}\n`;
  await writeFile(resolve(OUT_DIR, `sqlite_${name}.sql`), out, 'utf8');
  console.log(`✓ ${name}: ${rows.length} rows → db/seeds/sqlite_${name}.sql`);
}

db.close();
console.log('Done.');

// One-shot: lee apps/pty/src/data/clients.json y genera INSERT SQL listo para
// cargar a u444075633_prooq_v2.clients via mysql CLI o phpMyAdmin.
//
// Uso: node scripts/seed-clients.mjs > /tmp/clients-seed.sql

import data from '../apps/pty/src/data/clients.json' with { type: 'json' };

const escape = (s) => "'" + String(s).replace(/\\/g, '\\\\').replace(/'/g, "''") + "'";

const rows = data.map(
  (c, i) => `(${escape(c.name)}, ${escape(`/pty/images/clientes/${c.img}`)}, 'PA', ${i}, 1)`,
);

process.stdout.write(
  'INSERT INTO clients (name, logo_url, country, display_order, is_active) VALUES\n',
);
process.stdout.write(rows.join(',\n'));
process.stdout.write(';\n');

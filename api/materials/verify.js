const { init, sqlOne, sql } = require('../_lib/db');
const { getMaterials, MATERIAL_ITEMS } = require('../_lib/materials');

module.exports = async function handler(req, res) {
  res.setHeader('Content-Type', 'application/json');

  if (req.method !== 'GET') {
    res.writeHead(405);
    return res.end(JSON.stringify({ ok: false, error: 'Method not allowed' }));
  }

  try { await init(); } catch (err) {
    res.writeHead(500);
    return res.end(JSON.stringify({ ok: false, error: 'Database not ready' }));
  }

  const url = new URL(req.url, 'http://localhost');
  const token = url.searchParams.get('token');

  if (!token || typeof token !== 'string' || !/^[a-f0-9]{48}$/.test(token)) {
    res.writeHead(401);
    return res.end(JSON.stringify({ ok: false, error: 'Invalid or missing link.' }));
  }

  const app = await sqlOne`SELECT id, fullname, town, status, materials_token FROM applications WHERE materials_token = ${token}`;

  if (!app || app.status !== 'accepted') {
    res.writeHead(404);
    return res.end(JSON.stringify({ ok: false, error: 'This link is not valid.' }));
  }

  const firstname = (app.fullname || '').trim().split(/\s+/)[0] || 'Agent';

  const slotKeys = MATERIAL_ITEMS.map((m) => m.slot);
  const mediaRows = await sql`SELECT slot_key, blob_url, filename, file_size, updated_at FROM media WHERE slot_key = ANY(${slotKeys})`;
  const mediaMap = {};
  for (const row of mediaRows) mediaMap[row.slot_key] = row;

  res.writeHead(200);
  return res.end(JSON.stringify({
    ok: true,
    agent: { id: app.id, firstname, fullname: app.fullname, town: app.town },
    materials: getMaterials(mediaMap)
  }));
};
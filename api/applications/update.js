const { init, sql } = require('../_lib/db');
const { requireAuth, readJson } = require('../_lib/auth');

module.exports = async function handler(req, res) {
  res.setHeader('Content-Type', 'application/json');
  const user = requireAuth(req, res);
  if (!user) return;

  try { await init(); } catch (err) {
    res.writeHead(500);
    return res.end(JSON.stringify({ ok: false, error: 'Database not ready' }));
  }

  const data = await readJson(req);

  const id = parseInt(data.id);
  if (!id) { res.writeHead(400); return res.end(JSON.stringify({ ok: false, error: 'Invalid ID' })); }

  const allowed = ['pending','shortlisted','contacted','accepted','rejected'];

  if (data.action === 'save_notes' && typeof data.notes === 'string') {
    await sql`UPDATE applications SET notes = ${data.notes}, updated_at = NOW() WHERE id = ${id}`;
    res.writeHead(200);
    return res.end(JSON.stringify({ ok: true, message: 'Notes saved' }));
  }

  if (data.status && allowed.includes(data.status)) {
    await sql`UPDATE applications SET status = ${data.status}, updated_at = NOW() WHERE id = ${id}`;
    res.writeHead(200);
    return res.end(JSON.stringify({ ok: true, message: `Status changed to ${data.status}` }));
  }

  res.writeHead(400);
  return res.end(JSON.stringify({ ok: false, error: 'Invalid request' }));
};

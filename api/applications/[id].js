const { init, sql, sqlOne } = require('../_lib/db');
const { requireAuth } = require('../_lib/auth');

module.exports = async function handler(req, res) {
  res.setHeader('Content-Type', 'application/json');
  const user = requireAuth(req, res);
  if (!user) return;

  try { await init(); } catch (err) {
    res.writeHead(500);
    return res.end(JSON.stringify({ ok: false, error: 'Database not ready' }));
  }

  const url = new URL(req.url, 'http://localhost');
  const id = parseInt(url.pathname.split('/').pop());
  if (!id) { res.writeHead(400); return res.end(JSON.stringify({ ok: false, error: 'Invalid ID' })); }

  if (req.method === 'GET') {
    const app = await sqlOne`SELECT * FROM applications WHERE id = ${id}`;
    if (!app) { res.writeHead(404); return res.end(JSON.stringify({ ok: false, error: 'Not found' })); }
    res.writeHead(200);
    return res.end(JSON.stringify({ ok: true, application: app }));
  }

  if (req.method === 'PATCH' || req.method === 'POST') {
    let body = '';
    for await (const chunk of req) body += chunk;
    let data;
    try { data = JSON.parse(body); } catch { data = {}; }

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
  }

  res.writeHead(405);
  return res.end(JSON.stringify({ ok: false, error: 'Method not allowed' }));
};

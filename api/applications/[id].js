const { init, sql, sqlOne } = require('../_lib/db');
const { requireAuth, readJson } = require('../_lib/auth');
const crypto = require('crypto');

function baseUrl(req) {
  const proto = req.headers['x-forwarded-proto'] || 'http';
  const host = req.headers['x-forwarded-host'] || req.headers.host || 'localhost';
  return `${proto}://${host}`;
}

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
    const data = await readJson(req);

    const allowed = ['pending','shortlisted','contacted','accepted','rejected'];

    if (data.action === 'save_notes' && typeof data.notes === 'string') {
      await sql`UPDATE applications SET notes = ${data.notes}, updated_at = NOW() WHERE id = ${id}`;
      res.writeHead(200);
      return res.end(JSON.stringify({ ok: true, message: 'Notes saved' }));
    }

    if (data.action === 'send_materials') {
      const app = await sqlOne`SELECT * FROM applications WHERE id = ${id}`;
      if (!app) { res.writeHead(404); return res.end(JSON.stringify({ ok: false, error: 'Not found' })); }
      if (app.status !== 'accepted') {
        res.writeHead(400);
        return res.end(JSON.stringify({ ok: false, error: 'Approve the application before sending materials.' }));
      }

      let token = app.materials_token;
      let alreadySent = Boolean(token && app.materials_sent_at);
      if (!token) {
        token = crypto.randomBytes(24).toString('hex');
        await sql`UPDATE applications SET materials_token = ${token}, materials_sent_at = NOW(), updated_at = NOW() WHERE id = ${id}`;
      }

      const origin = baseUrl(req);
      const materialsUrl = `${origin}/materials?token=${encodeURIComponent(token)}`;
      const firstname = (app.fullname || '').trim().split(/\s+/)[0] || 'Agent';

      const msg = `Hello ${firstname}! Your Wazambi GPS Agent materials are ready. Open them here: ${materialsUrl} Complete the Agent Guide and onboarding videos at your own pace, then you can start finding customers. Welcome aboard!`;

      let phoneDigits = String(app.whatsapp || '').replace(/[^\d]/g, '');
      if (phoneDigits.startsWith('0')) phoneDigits = '260' + phoneDigits.slice(1);
      const whatsappUrl = phoneDigits ? `https://wa.me/${phoneDigits}?text=${encodeURIComponent(msg)}` : null;

      res.writeHead(200);
      return res.end(JSON.stringify({
        ok: true,
        message: alreadySent ? 'Materials link already created.' : 'Materials link created and marked as sent.',
        materialsUrl,
        whatsappUrl,
        emailSubject: 'Your Wazambi GPS Agent materials are ready',
        emailBody: msg,
        sentAt: app.materials_sent_at || new Date().toISOString()
      }));
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

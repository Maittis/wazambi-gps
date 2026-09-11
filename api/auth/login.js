const { init, sqlOne } = require('../_lib/db');
const { setSessionCookie, readJson } = require('../_lib/auth');
const bcrypt = require('bcryptjs');

module.exports = async function handler(req, res) {
  res.setHeader('Content-Type', 'application/json');

  if (req.method !== 'POST') {
    res.writeHead(405);
    return res.end(JSON.stringify({ ok: false, error: 'Method not allowed' }));
  }

  try { await init(); } catch (err) {
    res.writeHead(500);
    return res.end(JSON.stringify({ ok: false, error: 'Database not ready' }));
  }

  const data = await readJson(req);

  const username = (data.username || '').trim();
  const password = data.password || '';

  if (!username || !password) {
    res.writeHead(422);
    return res.end(JSON.stringify({ ok: false, error: 'Please enter both username and password.' }));
  }

  const admin = await sqlOne`SELECT id, username, password FROM admins WHERE username = ${username}`;
  if (!admin) {
    res.writeHead(401);
    return res.end(JSON.stringify({ ok: false, error: 'Invalid username or password.' }));
  }

  const valid = await bcrypt.compare(password, admin.password);
  if (!valid) {
    res.writeHead(401);
    return res.end(JSON.stringify({ ok: false, error: 'Invalid username or password.' }));
  }

  setSessionCookie(res, admin.id, admin.username);
  res.writeHead(200);
  return res.end(JSON.stringify({ ok: true, username: admin.username }));
};

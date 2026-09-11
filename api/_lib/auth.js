const crypto = require('crypto');
const SECRET = process.env.SESSION_SECRET || 'wazambi-default-secret-change-me';

function sign(payload) {
  const json = Buffer.from(JSON.stringify(payload)).toString('base64url');
  const sig = crypto.createHmac('sha256', SECRET).update(json).digest('base64url');
  return `${json}.${sig}`;
}

function verify(cookie) {
  if (!cookie) return null;
  const [json, sig] = cookie.split('.');
  if (!json || !sig) return null;
  const expected = crypto.createHmac('sha256', SECRET).update(json).digest('base64url');
  if (sig !== expected) return null;
  try {
    const data = JSON.parse(Buffer.from(json, 'base64url').toString());
    if (data.exp && data.exp < Date.now()) return null;
    return data;
  } catch { return null; }
}

function getCookie(req, name) {
  const raw = req.headers.cookie || '';
  const match = raw.split(';').map(s => s.trim()).find(s => s.startsWith(name + '='));
  return match ? match.split('=').slice(1).join('=') : null;
}

function requireAuth(req, res) {
  const token = getCookie(req, 'wz_session');
  const data = verify(token);
  if (!data) {
    res.writeHead(401, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ ok: false, error: 'Not authenticated' }));
    return null;
  }
  return data;
}

function setSessionCookie(res, adminId, username) {
  const value = sign({ adminId, username, exp: Date.now() + 7 * 86400000 });
  const cookie = `wz_session=${value}; Path=/; HttpOnly; SameSite=Lax; Max-Age=${7 * 86400}`;
  res.setHeader('Set-Cookie', cookie);
}

function clearSessionCookie(res) {
  res.setHeader('Set-Cookie', 'wz_session=; Path=/; HttpOnly; Max-Age=0');
}

async function readJson(req) {
  if (req.body && typeof req.body === 'object' && !Buffer.isBuffer(req.body)) return req.body;
  if (req.body) {
    try { return JSON.parse(req.body.toString()); } catch { return {}; }
  }
  let body = '';
  for await (const chunk of req) body += chunk;
  try { return JSON.parse(body); } catch { return {}; }
}

async function readJson(req) {
  if (req.body && typeof req.body === 'object' && !Buffer.isBuffer(req.body)) return req.body;
  if (req.body) {
    try { return JSON.parse(req.body.toString()); } catch { return {}; }
  }
  let body = '';
  for await (const chunk of req) body += chunk;
  try { return JSON.parse(body); } catch { return {}; }
}

async function readBody(req) {
  if (req.body && Buffer.isBuffer(req.body)) return req.body;
  if (req.body && typeof req.body === 'string') return Buffer.from(req.body);
  const chunks = [];
  for await (const chunk of req) chunks.push(Buffer.isBuffer(chunk) ? chunk : Buffer.from(String(chunk)));
  return Buffer.concat(chunks);
}

async function parseMultipart(req, form) {
  const { PassThrough } = require('stream');
  const buffer = await readBody(req);
  const stream = new PassThrough();
  stream.headers = req.headers;
  stream.write(buffer);
  stream.end();
  const [fields, files] = await new Promise((resolve, reject) => {
    form.parse(stream, (err, fields, files) => (err ? reject(err) : resolve([fields, files])));
  });
  return { fields, files };
}

module.exports = { sign, verify, getCookie, requireAuth, setSessionCookie, clearSessionCookie, readJson, readBody, parseMultipart };

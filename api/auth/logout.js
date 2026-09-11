const { clearSessionCookie } = require('../_lib/auth');

module.exports = async function handler(req, res) {
  res.setHeader('Content-Type', 'application/json');
  clearSessionCookie(res);
  res.writeHead(200);
  res.end(JSON.stringify({ ok: true }));
};

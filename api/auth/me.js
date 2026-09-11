const { requireAuth } = require('../_lib/auth');

module.exports = async function handler(req, res) {
  res.setHeader('Content-Type', 'application/json');
  const user = requireAuth(req, res);
  if (!user) return;
  res.writeHead(200);
  res.end(JSON.stringify({ ok: true, username: user.username }));
};

const { requireAuth, readJson } = require('../../_lib/auth');
const { handleUpload } = require('@vercel/blob/client');

module.exports = async function handler(req, res) {
  res.setHeader('Content-Type', 'application/json');

  const user = requireAuth(req, res);
  if (!user) return;

  const body = await readJson(req);

  try {
    const event = await handleUpload({
      token: process.env.BLOB_READ_WRITE_TOKEN,
      request: req,
      body,
      onBeforeGenerateToken: async () => ({
        maximumSizeInBytes: 500 * 1024 * 1024,
        addRandomSuffix: false,
        allowOverwrite: true,
      }),
    });
    res.writeHead(200);
    return res.end(JSON.stringify(event));
  } catch (err) {
    console.error('handleUpload error:', err);
    res.writeHead(400);
    return res.end(JSON.stringify({ ok: false, error: String(err && err.message || err) }));
  }
};
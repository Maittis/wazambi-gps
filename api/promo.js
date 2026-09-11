const { init, sqlOne } = require('../_lib/db');

module.exports = async function handler(req, res) {
  res.setHeader('Cache-Control', 'no-store');
  if (req.method !== 'GET' && req.method !== 'HEAD') {
    res.writeHead(405);
    return res.end();
  }
  try {
    await init();
    const row = await sqlOne`SELECT blob_url FROM media WHERE slot_key = 'wazambi-promo.mp4'`;
    if (!row || !row.blob_url) {
      res.writeHead(404);
      return res.end('not found');
    }
    res.writeHead(302, { Location: row.blob_url });
    return res.end();
  } catch (err) {
    console.error('promo redirect failed:', err);
    res.writeHead(500);
    return res.end('error');
  }
};
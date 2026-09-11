const { init, sql, sqlOne } = require('../_lib/db');
const { requireAuth } = require('../_lib/auth');
const formidable = require('formidable');
const { put } = require('@vercel/blob');

module.exports = async function handler(req, res) {
  res.setHeader('Content-Type', 'application/json');

  const user = requireAuth(req, res);
  if (!user) return;

  try { await init(); } catch (err) {
    res.writeHead(500);
    return res.end(JSON.stringify({ ok: false, error: 'Database not ready' }));
  }

  if (req.method === 'GET') {
    const rows = await sql`SELECT * FROM media ORDER BY slot_key`;
    return res.end(JSON.stringify({ ok: true, media: rows }));
  }

  if (req.method === 'POST') {
    const form = formidable({
      maxFileSize: 300 * 1024 * 1024,
      keepExtensions: true,
      allowEmptyFiles: false,
    });

    let fields = {};
    let uploadFile = null;
    try {
      const [f, files] = await new Promise((resolve, reject) => {
        form.parse(req, (err, fields, files) => {
          if (err) reject(err);
          else resolve([fields, files]);
        });
      });
      fields = f;
      uploadFile = files.upload ? files.upload[0] : null;
    } catch (err) {
      res.writeHead(400);
      return res.end(JSON.stringify({ ok: false, error: 'Failed to parse upload' }));
    }

    const slotKey = (fields.slot_key || '').toString().replace(/[^a-zA-Z0-9._-]/g, '');
    if (!slotKey || !uploadFile) {
      res.writeHead(400);
      return res.end(JSON.stringify({ ok: false, error: 'Missing slot or file' }));
    }

    const isImage = /\.(jpg|jpeg|png|webp)$/i.test(slotKey);
    const isVideo = /\.(mp4|webm|mov)$/i.test(slotKey);
    if (!isImage && !isVideo) {
      res.writeHead(400);
      return res.end(JSON.stringify({ ok: false, error: 'Unknown slot type' }));
    }

    const category = isImage ? 'images' : 'media';
    const blobName = `${category}/${slotKey}`;

    try {
      const blob = await put(blobName, uploadFile, {
        access: 'public',
        contentType: uploadFile.mimetype || (isImage ? 'image/jpeg' : 'video/mp4'),
        addRandomSuffix: false,
      });

      await sql`
        INSERT INTO media (slot_key, blob_url, filename, file_size, updated_at)
        VALUES (${slotKey}, ${blob.url}, ${uploadFile.originalFilename || slotKey}, ${uploadFile.size || 0}, NOW())
        ON CONFLICT (slot_key) DO UPDATE SET
          blob_url = ${blob.url},
          filename = ${uploadFile.originalFilename || slotKey},
          file_size = ${uploadFile.size || 0},
          updated_at = NOW()
      `;

      res.writeHead(200);
      return res.end(JSON.stringify({ ok: true, url: blob.url, slot: slotKey }));
    } catch (err) {
      console.error('Upload failed:', err);
      res.writeHead(500);
      return res.end(JSON.stringify({ ok: false, error: 'Upload failed' }));
    }
  }

  res.writeHead(405);
  return res.end(JSON.stringify({ ok: false, error: 'Method not allowed' }));
};

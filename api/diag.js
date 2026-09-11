const { Pool } = require('pg');
const { init } = require('./_lib/db');

module.exports = async function handler(req, res) {
  res.setHeader('Content-Type', 'application/json');
  const out = {
    hasDbUrl: !!process.env.DATABASE_URL,
    dbUrlPrefix: process.env.DATABASE_URL ? process.env.DATABASE_URL.slice(0, 30) + '...' : null,
  };
  try {
    await init();
    out.initOk = true;
  } catch (err) {
    out.initOk = false;
    out.error = String(err && err.message || err);
  }
  if (process.env.DATABASE_URL) {
    const pool = new Pool({ connectionString: process.env.DATABASE_URL, connectionTimeoutMillis: 8000, max: 1 });
    try {
      const r = await pool.query('SELECT now() AS t');
      out.connectOk = true;
      out.time = r.rows[0].t;
    } catch (err) {
      out.connectOk = false;
      out.error = String(err && err.message || err);
    } finally {
      try { await pool.end(); } catch {}
    }
  }
  res.writeHead(200);
  res.end(JSON.stringify(out, null, 2));
};
const { init, sql, sqlUnsafe } = require('../_lib/db');
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
  const statusFilter = url.searchParams.get('status') || '';
  const search = url.searchParams.get('q') || '';
  const page = Math.max(1, parseInt(url.searchParams.get('page')) || 1);
  const perPage = 25;
  const offset = (page - 1) * perPage;

  const allowedStatuses = ['','pending','shortlisted','contacted','accepted','rejected'];
  const sf = allowedStatuses.includes(statusFilter) ? statusFilter : '';

  // Stats
  const stats = (await sql`SELECT
    (SELECT COUNT(*)::int FROM applications) as total,
    (SELECT COUNT(*)::int FROM applications WHERE status='pending') as pending,
    (SELECT COUNT(*)::int FROM applications WHERE status='shortlisted') as shortlisted,
    (SELECT COUNT(*)::int FROM applications WHERE status='contacted') as contacted,
    (SELECT COUNT(*)::int FROM applications WHERE status='accepted') as accepted,
    (SELECT COUNT(*)::int FROM applications WHERE status='rejected') as rejected
  `)[0];

  let where = [];
  let params = [];
  let paramIdx = 1;

  if (sf) { where.push(`status = $${paramIdx++}`); params.push(sf); }
  if (search) {
    where.push(`(fullname ILIKE $${paramIdx} OR email ILIKE $${paramIdx+1} OR whatsapp ILIKE $${paramIdx+2} OR town ILIKE $${paramIdx+3})`);
    params.push(`%${search}%`, `%${search}%`, `%${search}%`, `%${search}%`);
    paramIdx += 4;
  }

  const whereSql = where.length ? 'WHERE ' + where.join(' AND ') : '';
  const countResult = await sqlUnsafe(`SELECT COUNT(*)::int as cnt FROM applications ${whereSql}`, ...params);
  const totalRows = countResult[0].cnt;
  const totalPages = Math.max(1, Math.ceil(totalRows / perPage));

  const rows = await sqlUnsafe(
    `SELECT id, fullname, whatsapp, email, town, status, weekly_customers, created_at
     FROM applications ${whereSql}
     ORDER BY created_at DESC
     LIMIT ${perPage} OFFSET ${offset}`,
    ...params
  );

  res.writeHead(200);
  return res.end(JSON.stringify({ ok: true, stats, rows, totalRows, totalPages, page }));
};

const { init, sql, sqlUnsafe } = require('../_lib/db');
const { requireAuth } = require('../_lib/auth');

module.exports = async function handler(req, res) {
  const user = requireAuth(req, res);
  if (!user) return;

  try { await init(); } catch (err) {
    res.writeHead(500);
    res.setHeader('Content-Type', 'text/plain');
    return res.end('Database not ready');
  }

  const url = new URL(req.url, 'http://localhost');
  const status = url.searchParams.get('status') || '';
  const search = url.searchParams.get('q') || '';

  const allowedStatuses = ['','pending','shortlisted','contacted','accepted','rejected'];
  const sf = allowedStatuses.includes(status) ? status : '';

  let where = [];
  let params = [];
  let pi = 1;
  if (sf) { where.push(`status = $${pi++}`); params.push(sf); }
  if (search) {
    where.push(`(fullname ILIKE $${pi} OR email ILIKE $${pi+1} OR whatsapp ILIKE $${pi+2} OR town ILIKE $${pi+3})`);
    params.push(`%${search}%`, `%${search}%`, `%${search}%`, `%${search}%`);
    pi += 4;
  }
  const whereSql = where.length ? 'WHERE ' + where.join(' AND ') : '';

  const rows = await sqlUnsafe(
    `SELECT id, fullname, whatsapp, email, town, age_18, smartphone,
      sales_experience, experience_detail, sales_methods,
      reachable_businesses, weekly_customers, first_five, areas_covered,
      first_seven_days, why_you, complete_onboarding,
      understands_commission, approved_info_prices, info_accurate,
      status, notes, created_at
     FROM applications ${whereSql}
     ORDER BY created_at DESC`,
    ...params
  );

  const BOM = '\uFEFF';
  const headers = ['ID','Full Name','WhatsApp','Email','Town','18+','Smartphone',
    'Sales Experience','Experience Detail','Sales Methods','Reachable Businesses',
    'Weekly Customers','First 5 Customers','Areas Covered','First 7 Days',
    'Why Select You','Complete Onboarding','Understands Commission',
    'Approved Info & Prices','Info Accurate','Status','Notes','Applied'];

  let csv = BOM + headers.join(',') + '\r\n';
  for (const r of rows) {
    csv += [r.id, r.fullname, r.whatsapp, r.email, r.town, r.age_18, r.smartphone,
      r.sales_experience, r.experience_detail, r.sales_methods,
      r.reachable_businesses, r.weekly_customers, r.first_five, r.areas_covered,
      r.first_seven_days, r.why_you, r.complete_onboarding,
      r.understands_commission, r.approved_info_prices, r.info_accurate,
      r.status, r.notes,
      new Date(r.created_at).toISOString()
    ].map(v => `"${String(v || '').replace(/"/g, '""')}"`).join(',') + '\r\n';
  }

  const filename = `wazambi_agents_${new Date().toISOString().slice(0,10)}.csv`;
  res.writeHead(200, {
    'Content-Type': 'text/csv; charset=utf-8',
    'Content-Disposition': `attachment; filename="${filename}"`,
  });
  res.end(csv);
};

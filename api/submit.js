const { init, sql } = require('./_lib/db');
const formidable = require('formidable');
const fs = require('fs');
const { put } = require('@vercel/blob');

module.exports = async function handler(req, res) {
  res.setHeader('Content-Type', 'application/json');

  if (req.method === 'OPTIONS') {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'POST');
    return res.status(200).end();
  }
  if (req.method !== 'POST') {
    res.writeHead(405);
    return res.end(JSON.stringify({ ok: false, error: 'Method not allowed' }));
  }

  try {
    await init();
  } catch (err) {
    console.error('DB init failed:', err);
    res.writeHead(500);
    return res.end(JSON.stringify({ ok: false, errors: ['Database not ready. Please try again later.'] }));
  }

  let fields = {};
  let cvFile = null;

  try {
    const form = formidable({
      maxFileSize: 10 * 1024 * 1024, // 10 MB
      keepExtensions: false,
      allowEmptyFiles: false,
      filter: ({ mimetype }) => mimetype === 'application/pdf',
    });

    const [f, files] = await new Promise((resolve, reject) => {
      form.parse(req, (err, fields, files) => {
        if (err) reject(err);
        else resolve([fields, files]);
      });
    });

    fields = f;
    cvFile = files.cv ? files.cv[0] : null;
  } catch (err) {
    console.error('Parse error:', err);
    res.writeHead(400);
    return res.end(JSON.stringify({ ok: false, errors: ['Failed to parse form data.'] }));
  }

  const get = (k) => {
    const v = fields[k];
    return Array.isArray(v) ? v[0] : (v || '');
  };

  const required = ['fullname','whatsapp','email','town','age_18','smartphone',
    'sales_experience','sales_methods','knows_vehicles',
    'weekly_customers','first_five','why_you',
    'attend_both','travel_own_cost','understands_commission','agree_declaration'];

  const errors = [];
  for (const f of required) {
    const v = get(f);
    if (!v || v === '') errors.push(f.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) + ' is required.');
  }
  if (get('age_18') !== 'Yes') errors.push('You must be 18 years or older.');
  if (get('attend_both') !== 'Yes') errors.push('You must be able to attend both training days.');
  if (get('understands_commission') !== 'Yes') errors.push('You must confirm this is commission-based.');
  if (get('agree_declaration') !== 'Yes') errors.push('You must accept the application declaration.');
  if (!cvFile) errors.push('Please upload your CV in PDF format.');
  if (cvFile && cvFile.originalFilename && !cvFile.originalFilename.endsWith('.pdf')) errors.push('CV must be a PDF file.');

  if (errors.length > 0) {
    res.writeHead(422);
    return res.end(JSON.stringify({ ok: false, errors }));
  }

  // Upload CV to Vercel Blob
  const cvName = `cv_${Date.now()}_${Math.random().toString(36).slice(2,8)}.pdf`;
  let cvBlobUrl = null;
  try {
    const blob = await put(`cvs/${cvName}`, fs.createReadStream(cvFile.filepath), {
      access: 'public',
      contentType: 'application/pdf',
    });
    cvBlobUrl = blob.url;
  } catch (err) {
    console.error('CV upload failed:', err);
    // Continue without CV if Blob fails
  }

  // Insert record
  try {
    await sql`
      INSERT INTO applications
        (fullname, whatsapp, email, town, age_18, smartphone, sales_experience,
         experience_detail, sales_methods, knows_vehicles, weekly_customers,
         first_five, why_you, attend_both, travel_own_cost,
         understands_commission, cv_filename, agree_declaration)
      VALUES
        (${get('fullname')}, ${get('whatsapp')}, ${get('email')}, ${get('town')},
         ${get('age_18')}, ${get('smartphone')}, ${get('sales_experience')},
         ${get('experience_detail')}, ${get('sales_methods')}, ${get('knows_vehicles')},
         parseInt(get('weekly_customers')) || 0, ${get('first_five')}, ${get('why_you')},
         ${get('attend_both')}, ${get('travel_own_cost')}, ${get('understands_commission')},
         ${cvBlobUrl}, ${get('agree_declaration')})
    `;
    res.writeHead(200);
    return res.end(JSON.stringify({ ok: true, message: 'Application received' }));
  } catch (err) {
    console.error('Insert error:', err);
    res.writeHead(500);
    return res.end(JSON.stringify({ ok: false, errors: ['Failed to save application. Please try again.'] }));
  }
};

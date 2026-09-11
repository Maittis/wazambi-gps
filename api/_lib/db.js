const { neon } = require('@neondatabase/serverless');

let _pool;

function getPool() {
  if (!_pool) {
    const { Pool } = require('pg');
    _pool = new Pool({ connectionString: process.env.DATABASE_URL, max: 2 });
  }
  return _pool;
}

async function sql(strings, ...values) {
  const client = getPool();
  const result = await client.query(strings.join('?'), values);
  return result.rows;
}

async function sqlOne(strings, ...values) {
  const rows = await sql(strings, ...values);
  return rows[0] || null;
}

async function sqlUnsafe(text, ...values) {
  const client = getPool();
  const result = await client.query(text, values);
  return result.rows;
}

async function ensureSchema() {
  const client = getPool();
  await client.query(`
    CREATE TABLE IF NOT EXISTS applications (
      id                    SERIAL PRIMARY KEY,
      fullname              VARCHAR(255) NOT NULL,
      whatsapp              VARCHAR(80)  NOT NULL,
      email                 VARCHAR(255) NOT NULL,
      town                  VARCHAR(255) NOT NULL,
      age_18                VARCHAR(10)  NOT NULL DEFAULT 'No',
      smartphone            VARCHAR(10)  NOT NULL DEFAULT 'No',
      sales_experience      VARCHAR(10)  NOT NULL DEFAULT 'No',
      experience_detail     TEXT,
      sales_methods         VARCHAR(255) NOT NULL,
      knows_vehicles        VARCHAR(10)  NOT NULL DEFAULT 'No',
      weekly_customers      INT          NOT NULL DEFAULT 0,
      first_five            TEXT         NOT NULL,
      why_you               TEXT         NOT NULL,
      attend_both           VARCHAR(10)  NOT NULL DEFAULT 'No',
      travel_own_cost       VARCHAR(10)  NOT NULL DEFAULT 'No',
      understands_commission VARCHAR(10) NOT NULL DEFAULT 'No',
      cv_filename           VARCHAR(255) DEFAULT NULL,
      agree_declaration     VARCHAR(10)  NOT NULL DEFAULT 'No',
      status                VARCHAR(20)  NOT NULL DEFAULT 'pending',
      notes                 TEXT         DEFAULT NULL,
      created_at            TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
      updated_at            TIMESTAMPTZ  NOT NULL DEFAULT NOW()
    )
  `);
  await client.query(`CREATE INDEX IF NOT EXISTS idx_status  ON applications(status)`);
  await client.query(`CREATE INDEX IF NOT EXISTS idx_created ON applications(created_at)`);

  await client.query(`
    CREATE TABLE IF NOT EXISTS admins (
      id         SERIAL PRIMARY KEY,
      username   VARCHAR(100) NOT NULL UNIQUE,
      password   VARCHAR(255) NOT NULL,
      created_at TIMESTAMPTZ  NOT NULL DEFAULT NOW()
    )
  `);

  await client.query(`
    CREATE TABLE IF NOT EXISTS media (
      slot_key  VARCHAR(255) PRIMARY KEY,
      blob_url  TEXT    NOT NULL,
      filename  VARCHAR(255) NOT NULL,
      file_size BIGINT  DEFAULT 0,
      updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    )
  `);

  // Seed admin (idempotent)
  const bcrypt = require('bcryptjs');
  const existing = await sqlOne`SELECT id FROM admins WHERE username = 'admin'`;
  if (!existing) {
    const hash = await bcrypt.hash('wazambi2026', 10);
    await sql`INSERT INTO admins (username, password) VALUES ('admin', ${hash})`;
  }
}

let _schemaReady = false;
async function init() {
  if (!_schemaReady) {
    await ensureSchema();
    _schemaReady = true;
  }
}

module.exports = { sql, sqlOne, sqlUnsafe, init };

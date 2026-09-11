module.exports = async function handler(req, res) {
  res.setHeader('Content-Type', 'application/json');
  const peek = (k) => {
    const v = process.env[k];
    if (!v) return null;
    return v.length > 8 ? v.slice(0, 3) + '...' + v.slice(-3) + ` (len ${v.length})` : '<short>';
  };
  res.end(JSON.stringify({
    BLOB_READ_WRITE_TOKEN: peek('BLOB_READ_WRITE_TOKEN'),
    BLOB_STORE_ID: peek('BLOB_STORE_ID'),
    BLOB_WEBHOOK_PUBLIC_KEY: peek('BLOB_WEBHOOK_PUBLIC_KEY'),
    VERCEL_OIDC_TOKEN: peek('VERCEL_OIDC_TOKEN'),
  }, null, 2));
};
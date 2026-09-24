// Agent onboarding materials manifest.
// URLs are looked up in the `media` table (uploaded via the admin panel),
// falling back to environment variables if a slot has not been uploaded yet.

const MATERIAL_ITEMS = [
  { key: 'guide', slot: 'materials_guide.pdf', kind: 'pdf', title: 'Agent Guide', desc: 'Your step-by-step guide to the Wazambi GPS agent programme.' },
  { key: 'video_intro', slot: 'materials_video_1.mp4', kind: 'video', title: 'Introduction video', desc: 'Welcome to the programme: how it works and what to expect.' },
  { key: 'video_role', slot: 'materials_video_2.mp4', kind: 'video', title: 'Your role as an agent', desc: 'What it means to be an independent Wazambi GPS agent.' },
  { key: 'video_find', slot: 'materials_video_3.mp4', kind: 'video', title: 'Finding customers', desc: 'How to use your chosen sales methods to find customers.' },
  { key: 'video_sales', slot: 'materials_video_4.mp4', kind: 'video', title: 'Sales process', desc: 'How a sale flows from intro to installation.' },
  { key: 'video_objections', slot: 'materials_video_5.mp4', kind: 'video', title: 'Handling objections', desc: 'Common customer questions and how to answer them.' },
  { key: 'video_close', slot: 'materials_video_6.mp4', kind: 'video', title: 'Closing and commission', desc: 'Closing tips and how commission is recorded.' },
  { key: 'pricing', slot: 'materials_pricing.pdf', kind: 'pdf', title: 'Product and pricing guide', desc: 'Product details and the approved prices you must use.' },
  { key: 'commission', slot: 'materials_commission.pdf', kind: 'pdf', title: 'Commission explanation', desc: 'How commissions and payments work.' },
  { key: 'marketing', slot: 'materials_marketing.pdf', kind: 'pdf', title: 'Approved marketing information', desc: 'The approved information and prices you may use in marketing.' }
];

const ENV_FALLBACK = {
  guide: 'MATERIAL_GUIDE_URL',
  video_intro: 'MATERIAL_VIDEO_1_URL',
  video_role: 'MATERIAL_VIDEO_2_URL',
  video_find: 'MATERIAL_VIDEO_3_URL',
  video_sales: 'MATERIAL_VIDEO_4_URL',
  video_objections: 'MATERIAL_VIDEO_5_URL',
  video_close: 'MATERIAL_VIDEO_6_URL',
  pricing: 'MATERIAL_PRICING_URL',
  commission: 'MATERIAL_COMMISSION_URL',
  marketing: 'MATERIAL_MARKETING_URL'
};

// mediaMap: { slot_key: { blob_url, filename, file_size, updated_at } }
function getMaterials(mediaMap = {}) {
  return MATERIAL_ITEMS.map((item) => {
    const row = mediaMap[item.slot];
    const url = (row && row.blob_url) || process.env[ENV_FALLBACK[item.key]] || null;
    return {
      ...item,
      url,
      filename: row ? row.filename : null,
      fileSize: row ? row.file_size : null,
      updatedAt: row ? row.updated_at : null
    };
  });
}

module.exports = { MATERIAL_ITEMS, ENV_FALLBACK, getMaterials };
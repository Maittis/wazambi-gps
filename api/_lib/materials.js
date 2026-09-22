// Agent onboarding materials manifest.
// Each item resolves to an environment variable holding the file/link URL.
// Until a URL is configured the item is shown as "coming soon" to the agent.
function getMaterials() {
  const items = [
    { key: 'guide', kind: 'pdf', title: 'Agent Guide', desc: 'Your step-by-step guide to the Wazambi GPS agent programme.' },
    { key: 'video_intro', kind: 'video', title: 'Introduction video', desc: 'Welcome to the programme: how it works and what to expect.' },
    { key: 'video_role', kind: 'video', title: 'Your role as an agent', desc: 'What it means to be an independent Wazambi GPS agent.' },
    { key: 'video_find', kind: 'video', title: 'Finding customers', desc: 'How to use your chosen sales methods to find customers.' },
    { key: 'video_sales', kind: 'video', title: 'Sales process', desc: 'How a sale flows from intro to installation.' },
    { key: 'video_objections', kind: 'video', title: 'Handling objections', desc: 'Common customer questions and how to answer them.' },
    { key: 'video_close', kind: 'video', title: 'Closing and commission', desc: 'Closing tips and how commission is recorded.' },
    { key: 'pricing', kind: 'pdf', title: 'Product and pricing guide', desc: 'Product details and the approved prices you must use.' },
    { key: 'commission', kind: 'pdf', title: 'Commission explanation', desc: 'How commissions and payments work.' },
    { key: 'marketing', kind: 'pdf', title: 'Approved marketing information', desc: 'The approved information and prices you may use in marketing.' }
  ];

  const envMap = {
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

  return items.map((item) => ({ ...item, url: process.env[envMap[item.key]] || null }));
}

module.exports = { getMaterials };
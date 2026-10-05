const translations = {
 home: ['Desert hotel in Egypt', 'فندق في الصحراء الغربية بمصر', '埃及西部沙漠酒店'],
 rooms: ['Rooms', 'الغرف', '客房'], about: ['Our story', 'حكايتنا', '我们的故事'],
 services: ['Hotel services', 'خدمات الفندق', '酒店服务'], experiences: ['Desert experiences', 'تجارب الصحراء', '沙漠体验'],
 gallery: ['Hotel gallery', 'صور الفندق', '酒店相册'], blog: ['Desert journal', 'يوميات الصحراء', '沙漠日志'],
 contact: ['Contact the hotel', 'تواصل مع الفندق', '联系酒店'], faq: ['Frequently asked questions', 'الأسئلة الشائعة', '常见问题'],
 privacy: ['Privacy policy', 'سياسة الخصوصية', '隐私政策'], terms: ['Terms and conditions', 'الشروط والأحكام', '条款与条件'],
};
const intros = ['Explore rooms, hotel services and desert experiences at B&W Sahara Sky, between Bahariya Oasis and Farafra in Egypt.',
 'اكتشف الغرف وخدمات الفندق وتجارب الصحراء في B&W Sahara Sky على طريق الواحات البحرية والفرافرة في مصر.',
 '探索B&W Sahara Sky酒店的客房、服务与沙漠体验，酒店位于埃及拜哈里耶绿洲与法拉夫拉之间。'];
export const languages = ['en', 'ar', 'zh'];
export function seoConfig(env = process.env) {
 const indexable = env.HOTEL_INDEXABLE === '1';
 const origin = env.HOTEL_SITE_ORIGIN || '';
 if (origin) {
  const url = new URL(origin);
  if (url.protocol !== 'https:' || url.username || url.password || url.pathname !== '/' || url.search || url.hash)
   throw new Error('HOTEL_SITE_ORIGIN must be a bare HTTPS origin.');
 }
 if (indexable && !origin) throw new Error('Indexable builds require HOTEL_SITE_ORIGIN.');
 return {origin: origin ? new URL(origin).origin : '', indexable, translations, intros};
}
export function routePath(file) { return file.replace(/index\.html$/, '').replace(/404\.html$/, '404.html'); }
export function publicPath(path) { return !/^\/(?:booking|login|register|forgot-password|design-review)(?:\/|$)/.test(path) && path !== '/404.html'; }
export function sitemap(config, routes) {
 const escape = value => value.replaceAll('&', '&amp;').replaceAll('"', '&quot;');
 const entries = config.indexable ? routes.map(routePath).filter(publicPath).flatMap(path => languages.map(lang => {
  const url = code => `${config.origin}${path}?lang=${code}`;
  return `<url><loc>${escape(url(lang))}</loc>${languages.map(code => `<xhtml:link rel="alternate" hreflang="${code === 'zh' ? 'zh-Hans' : code}" href="${escape(url(code))}"/>`).join('')}<xhtml:link rel="alternate" hreflang="x-default" href="${escape(url('en'))}"/></url>`;
 })) : [];
 return `<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">${entries.join('')}</urlset>\n`;
}
export function robots(config) {
 if (!config.indexable) return 'User-agent: *\nDisallow: /\n';
 return 'User-agent: *\nAllow: /\n' + ['booking','login','register','forgot-password','design-review'].map(path => `Disallow: /${path}/\n`).join('') + `Sitemap: ${config.origin}/sitemap.xml\n`;
}

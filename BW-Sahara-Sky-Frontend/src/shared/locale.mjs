export const languages = ['en', 'ar', 'zh'];
export const defaultLocale = 'en';
export const rtlLocales = ['ar'];
// Source templates use English paths; these are the only public URL variants.
export const routeDefinitions = [
 ['home','/','/ar/','/zh/'],
 ['rooms','/rooms/','/ar/ghoraf/','/zh/kefang/'],
 ['about','/about-us/','/ar/aan-al-fondoq/','/zh/guanyu-jiudian/'],
 ['services','/services/','/ar/khedmat/','/zh/fuwu/'],
 ['experiences','/experiences/','/ar/tajarob-al-sahraa/','/zh/shamo-tiyan/'],
 ['gallery','/galleries/','/ar/sowar/','/zh/xiangce/'],
 ['blog','/blog/','/ar/yawmeyat/','/zh/shamo-rizhi/',false],
 ['contact','/contact-us/','/ar/tawasul/','/zh/lianxi/'],
 ['faq','/faq/','/ar/asela-shaea/','/zh/changjian-wenti/'],
 ['privacy','/privacy/','/ar/khososiya/','/zh/yinsi-zhengce/',false],
 ['terms','/term-and-conditions/','/ar/shorout/','/zh/tiaokuan/',false],
 ['guide','/guides/planning-a-desert-stay/','/ar/dalil/takhteet-eqama-sahraweya/','/zh/zhinan/shamo-zhusu-jihua/'],
 ['room-deluxe-single','/rooms/bw-sahara-sky-hotel-deluxe-room-single/','/ar/ghoraf/deluxe-fardeya/','/zh/kefang/haohua-danren/'],
 ['room-deluxe-double','/rooms/bw-sahara-sky-hotel-deluxe-room-double/','/ar/ghoraf/deluxe-mozdawaga/','/zh/kefang/haohua-shuangren/'],
 ['room-standard-single','/rooms/standard-room-single/','/ar/ghoraf/qeyaseya-fardeya/','/zh/kefang/biaozhun-danren/'],
 ['room-standard-double','/rooms/bw-sahara-sky-hotel-standard-room-double/','/ar/ghoraf/qeyaseya-mozdawaga/','/zh/kefang/biaozhun-shuangren/'],
 ['room-standard-triple','/rooms/bw-sahara-sky-hotel-standard-room-triple/','/ar/ghoraf/qeyaseya-tholatheyya/','/zh/kefang/biaozhun-sanren/'],
 ['room-superior-single','/rooms/bw-sahara-sky-hotel-superior-room-single/','/ar/ghoraf/momayaza-fardeya/','/zh/kefang/gaoji-danren/'],
 ['room-superior-double','/rooms/bw-sahara-sky-hotel-superior-room-double/','/ar/ghoraf/momayaza-mozdawaga/','/zh/kefang/gaoji-shuangren/'],
 ['domes','/rooms/copper-glass-domes/','/ar/ghoraf/qebab-zogageya/','/zh/kefang/boli-qiongding/',false],
 ['story-slow','/blog/the-art-of-a-slower-stay/','/ar/yawmeyat/eqama-ala-mahlak/','/zh/shamo-rizhi/man-jiezou/',false],
 ['story-stars','/blog/a-night-beneath-the-stars/','/ar/yawmeyat/leila-taht-al-nojoom/','/zh/shamo-rizhi/xingkong-zhi-ye/',false],
 ['story-desert','/blog/a-desert-made-for-discovery/','/ar/yawmeyat/sahraa-lel-ektshaf/','/zh/shamo-rizhi/tansuo-shamo/',false],
 ['booking','/booking/','/ar/takhteet-eqama/','/zh/zhusu-jihua/',false],
 ['review','/booking/review/','/ar/takhteet-eqama/moragaa/','/zh/zhusu-jihua/queren/',false],
 ['confirmation','/booking/confirmation/','/ar/takhteet-eqama/moayena/','/zh/zhusu-jihua/yulan/',false],
 ['login','/login/','/ar/dokhool/','/zh/denglu/',false],
 ['register','/register/','/ar/hesab-gadeed/','/zh/zhuce/',false],
 ['forgot','/forgot-password/','/ar/esteeadet-kalemet-moroor/','/zh/zhaohui-mima/',false],
 ['approval','/design-review/','/ar/moragaat-tasmeem/','/zh/sheji-shenhe/',false],
 ['404','/404.html','/ar/404.html','/zh/404.html',false],
];
export function resolveRoute(pathname) {
 let path;
 try { path = decodeURIComponent(pathname).replace(/\/index\.html$/, '/'); } catch { return null; }
 if (path !== '/' && !path.endsWith('/') && !path.endsWith('.html')) path += '/';
 for (const row of routeDefinitions) {
  const i = row.slice(1, 4).indexOf(path);
  if (i >= 0) return {id:row[0],locale:languages[i],logical:row[1],path:row[i+1],indexable:row[4]!==false};
 }
 return null;
}
export function localizedPath(logicalOrId, locale='en') {
 if (!languages.includes(locale)) locale='en';
 const row=routeDefinitions.find(r=>r[0]===logicalOrId||r[1]===logicalOrId)||routeDefinitions.find(r=>r[0]===resolveRoute(logicalOrId)?.id);
 return row ? row[languages.indexOf(locale)+1] : logicalOrId;
}
export function templateFile(row) {return row[1].endsWith('.html')?row[1]:row[1]+'index.html';}

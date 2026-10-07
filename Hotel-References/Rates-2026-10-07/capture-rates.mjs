import {writeFile} from 'node:fs/promises';
const raw=[
 ['611','Deluxe Single','room','USD',347,260,280,299],
 ['612','Deluxe Double','room','USD',377,283,304,325],
 ['622','Deluxe Twin','room','USD',377,283,304,325],
 ['711','Standard Single','room','USD',293,220,236,253],
 ['712','Standard Double','room','USD',313,235,253,270],
 ['722','Standard Twin','room','USD',313,235,253,270],
 ['733','Standard Triple','room','USD',378,284,305,326],
 ['811','Superior Single','room','USD',313,235,253,270],
 ['812','Superior Double','room','USD',338,254,273,292],
 ['822','Superior Twin','room','USD',338,254,273,292],
 ['911','Premium Single','room','USD',394,296,318,340],
 ['912','Premium Double','room','USD',429,322,346,370],
 ['922','Premium Twin','room','USD',429,322,346,370],
 [null,'Premium Luxury Single','room','USD',null,null,null,null],
 [null,'Premium Luxury Double','room','USD',null,null,null,null],
 ['111','Extra bed','extra','USD',50,50,50,50],
 ['000','Free room for 1 Tour Leader','extra','USD',null,null,null,null],
 ['101','SGL Guide room','guide-room','EGP',null,4100,4100,4100],
 ['102','Twin Guide room','guide-room','EGP',null,8200,8200,8200],
 ['103','TPL Guide room','guide-room','EGP',null,12300,12300,12300],
 ['L01','Single Camping Tent','tent','USD',100,75,81,86],
 ['L02','Double Camping Tent','tent','USD',120,90,97,104],
 ['X01','Cairo Roundtrip & Safari — 2 days / 1 night','package','USD',201,185,185,185],
 ['X02','Cairo Roundtrip & Safari — 3 days / 2 nights','package','USD',451,361,375,415],
 ['C01','5-Cairo 5-seater car-5','vehicle','USD',283,200,210,260],
 ['C02','7-Cairo luxury SUV-7','vehicle','USD',293,210,220,270],
 ['C03','13-Toyota Hiace-13','vehicle','USD',420,310,320,380],
 ['C04','off-road vehicle-Safari','vehicle','USD',386,330,340,420],
 ['C05','White Desert','vehicle','USD',283,210,220,260],
 ['C06','Black Desert Oasis','vehicle','USD',174,110,120,160]
];
const rows=raw.map(([code,name,kind,currency,booking,travelAgency,tourGuide,bw])=>({code,name,kind,currency,prices:{booking,travelAgency,tourGuide,bw},rateBasis:null,taxInclusive:null,mealsIncluded:null,approval:'reference-only',...(code==='000'?{note:'Free label in source; eligibility conditions not supplied. Dashes are not interpreted as unconditional zero prices.'}:{})}));
const data={hotel:'B&W Sahara Sky Hotel',received:'2026-10-07',validFrom:'2026-10-01',validThrough:'2027-05-31',source:'Original-Rate-Sheet.jpg',sourceType:'User-provided photograph/table; manual transcription',channelInterpretation:{booking:'Source header BOOKing; exact platform to confirm',travelAgency:'Travel Agency',tourGuide:'Tour Guide',bw:'B&W; interpretation as direct website price requires owner confirmation'},nullMeaning:'Blank/dash in source, not a zero price',unresolved:['Per room/night vs per person vs per booking/package basis','Taxes, meals, supplements and restrictions','B&W direct website applicability','Room-type code legend conflicts with main table','Single/Double variants share merged quantity cells; do not sum as separate inventory','Double king-bed labels conflict with twin-bed descriptions in the current local catalogue','Premium Luxury prices/codes missing','Free Tour Leader eligibility conditions'],rows};
await writeFile(new URL('rates.reference.json',import.meta.url),JSON.stringify(data,null,2)+'\n');
const table='| Code | Item | Currency | Booking | Travel Agency | Tour Guide | B&W |\n| --- | --- | --- | ---: | ---: | ---: | ---: |\n'+rows.map(r=>`| ${r.code??'Not listed'} | ${r.name} | ${r.currency} | ${Object.values(r.prices).map(v=>v??'Not listed').join(' | ')} |`).join('\n');
await writeFile(new URL('RATE_REFERENCE.md',import.meta.url),`# Hotel rate reference — received 2026-10-07\n\nSource: Original-Rate-Sheet.jpg supplied by the user. Effective 2026-10-01 through 2027-05-31. Reference transcription only; not applied to the website, database, booking engine or payments.\n\nThe columns are separate channel rates. B&W is not automatically assumed to be the website rate. All rows use USD except Guide rooms, whose cells explicitly use EGP. Missing values/dashes are not zero prices. The source does not state per-night/per-person basis or whether tax/meals are included.\n\n${table}\n\n## Local catalogue comparison\n\n| Existing room | Local USD price | Sheet agency USD | Sheet B&W USD | Finding |\n| --- | ---: | ---: | ---: | --- |\n| Deluxe Single | 260 | 260 | 299 | Matches agency column |\n| Deluxe Double | 283 | 283 | 325 | Matches agency column |\n| Standard Single | 220 | 220 | 253 | Matches agency column |\n| Standard Double | 235 | 235 | 270 | Matches agency column |\n| Standard Triple | 235 | 284 | 326 | Does not match this row's agency rate |\n| Superior Single | 254 | 235 | 270 | Matches Superior Double agency rate, not Single |\n| Superior Double | 254 | 254 | 292 | Matches agency column |\n\nObserved via local /api/hotel/rooms on 2026-10-07; these are local records, not a verification of live hotel prices. Five of seven prices match the agency column. If the B&W column is confirmed for direct web sales, all seven local prices need review. No price update is authorized or applied from this comparison.\n\n## Catalogue and mapping issues\n\n- The existing public local catalogue has seven entries. The sheet has 13 priced conventional room/occupancy entries plus two Premium Luxury entries without prices. The four explicit Twin entries and Premium Single/Double are absent as separate local catalogue entries. Camping tents, Guide rooms, packages and transport need separate business/inventory treatment. A rate-plan variant is not automatically a separate physical room.\n- The Chinese source distinguishes 大床 (large/double bed) from 双床 (twin beds). Existing Deluxe/Standard/Superior Double descriptions say twin beds. Confirm the physical configuration before mapping Double and Twin or reusing photographs.\n- Main table category codes: 6=Deluxe, 7=Standard, 8=Superior, 9=Premium. Bottom legend instead says 6=Deluxe, 7=Premium, 8=Standard, 9=Superior. Preserve the literal row codes; do not derive categories from the conflicting legend.\n- Merged quantity cells cover Single/Double variants (Deluxe 5, Standard 5, Superior 4, Premium 5). Twin quantities are Deluxe 5, Standard 4, Superior 4, Premium 20; Standard Triple is 1. Premium Luxury shows 1 on each row; camping tent rows show 10 each. These source quantities are not confirmed independent pools or live availability; do not total duplicated rate variants.\n- Premium Luxury entries have no price or code. Tour Leader is labelled free but conditions are absent. Neither can be enabled as a universally free bookable option.\n- C04 B&W 420 exceeds its Booking 386. Do not use a universal channel discount or assume B&W is always cheapest.\n- Safari durations are explicitly 2 days/1 night and 3 days/2 nights. Price unit, passenger capacity, accommodation, meals, permits and transport inclusions remain unconfirmed.\n- Validity crosses calendar years. A future implementation needs dated rate plans, channel access and currency-safe totals; expiry cannot silently carry these prices forward.\n\n## Required business confirmation\n\nConfirm direct website channel; billing unit for rooms, tents, packages and vehicles; tax/meal inclusions; Single/Double/Twin stock sharing and physical beds; Premium Luxury rates; Tour Leader terms; authoritative code legend. This rate list does not resolve Pay@Hotel-only external checkout or missing payment-link delivery.\n\nThis folder is a local business reference outside the Git checkouts. Agency/guide price columns have not been published on the customer-facing site.\n`);
console.log(JSON.stringify({rows:rows.length,referenceOnly:true,appliedToPricing:false}));

# Hotel rate reference — received 2026-10-07

Source: Original-Rate-Sheet.jpg supplied by the user. Effective 2026-10-01 through 2027-05-31. Reference transcription only; not applied to the website, database, booking engine or payments.

The columns are separate channel rates. B&W is not automatically assumed to be the website rate. All rows use USD except Guide rooms, whose cells explicitly use EGP. Missing values/dashes are not zero prices. The source does not state per-night/per-person basis or whether tax/meals are included.

| Code | Item | Currency | Booking | Travel Agency | Tour Guide | B&W |
| --- | --- | --- | ---: | ---: | ---: | ---: |
| 611 | Deluxe Single | USD | 347 | 260 | 280 | 299 |
| 612 | Deluxe Double | USD | 377 | 283 | 304 | 325 |
| 622 | Deluxe Twin | USD | 377 | 283 | 304 | 325 |
| 711 | Standard Single | USD | 293 | 220 | 236 | 253 |
| 712 | Standard Double | USD | 313 | 235 | 253 | 270 |
| 722 | Standard Twin | USD | 313 | 235 | 253 | 270 |
| 733 | Standard Triple | USD | 378 | 284 | 305 | 326 |
| 811 | Superior Single | USD | 313 | 235 | 253 | 270 |
| 812 | Superior Double | USD | 338 | 254 | 273 | 292 |
| 822 | Superior Twin | USD | 338 | 254 | 273 | 292 |
| 911 | Premium Single | USD | 394 | 296 | 318 | 340 |
| 912 | Premium Double | USD | 429 | 322 | 346 | 370 |
| 922 | Premium Twin | USD | 429 | 322 | 346 | 370 |
| Not listed | Premium Luxury Single | USD | Not listed | Not listed | Not listed | Not listed |
| Not listed | Premium Luxury Double | USD | Not listed | Not listed | Not listed | Not listed |
| 111 | Extra bed | USD | 50 | 50 | 50 | 50 |
| 000 | Free room for 1 Tour Leader | USD | Not listed | Not listed | Not listed | Not listed |
| 101 | SGL Guide room | EGP | Not listed | 4100 | 4100 | 4100 |
| 102 | Twin Guide room | EGP | Not listed | 8200 | 8200 | 8200 |
| 103 | TPL Guide room | EGP | Not listed | 12300 | 12300 | 12300 |
| L01 | Single Camping Tent | USD | 100 | 75 | 81 | 86 |
| L02 | Double Camping Tent | USD | 120 | 90 | 97 | 104 |
| X01 | Cairo Roundtrip & Safari — 2 days / 1 night | USD | 201 | 185 | 185 | 185 |
| X02 | Cairo Roundtrip & Safari — 3 days / 2 nights | USD | 451 | 361 | 375 | 415 |
| C01 | 5-Cairo 5-seater car-5 | USD | 283 | 200 | 210 | 260 |
| C02 | 7-Cairo luxury SUV-7 | USD | 293 | 210 | 220 | 270 |
| C03 | 13-Toyota Hiace-13 | USD | 420 | 310 | 320 | 380 |
| C04 | off-road vehicle-Safari | USD | 386 | 330 | 340 | 420 |
| C05 | White Desert | USD | 283 | 210 | 220 | 260 |
| C06 | Black Desert Oasis | USD | 174 | 110 | 120 | 160 |

## Local catalogue comparison

| Existing room | Local USD price | Sheet agency USD | Sheet B&W USD | Finding |
| --- | ---: | ---: | ---: | --- |
| Deluxe Single | 260 | 260 | 299 | Matches agency column |
| Deluxe Double | 283 | 283 | 325 | Matches agency column |
| Standard Single | 220 | 220 | 253 | Matches agency column |
| Standard Double | 235 | 235 | 270 | Matches agency column |
| Standard Triple | 235 | 284 | 326 | Does not match this row's agency rate |
| Superior Single | 254 | 235 | 270 | Matches Superior Double agency rate, not Single |
| Superior Double | 254 | 254 | 292 | Matches agency column |

Observed via local /api/hotel/rooms on 2026-10-07; these are local records, not a verification of live hotel prices. Five of seven prices match the agency column. If the B&W column is confirmed for direct web sales, all seven local prices need review. No price update is authorized or applied from this comparison.

## Catalogue and mapping issues

- The existing public local catalogue has seven entries. The sheet has 13 priced conventional room/occupancy entries plus two Premium Luxury entries without prices. The four explicit Twin entries and Premium Single/Double are absent as separate local catalogue entries. Camping tents, Guide rooms, packages and transport need separate business/inventory treatment. A rate-plan variant is not automatically a separate physical room.
- The Chinese source distinguishes 大床 (large/double bed) from 双床 (twin beds). Existing Deluxe/Standard/Superior Double descriptions say twin beds. Confirm the physical configuration before mapping Double and Twin or reusing photographs.
- Main table category codes: 6=Deluxe, 7=Standard, 8=Superior, 9=Premium. Bottom legend instead says 6=Deluxe, 7=Premium, 8=Standard, 9=Superior. Preserve the literal row codes; do not derive categories from the conflicting legend.
- Merged quantity cells cover Single/Double variants (Deluxe 5, Standard 5, Superior 4, Premium 5). Twin quantities are Deluxe 5, Standard 4, Superior 4, Premium 20; Standard Triple is 1. Premium Luxury shows 1 on each row; camping tent rows show 10 each. These source quantities are not confirmed independent pools or live availability; do not total duplicated rate variants.
- Premium Luxury entries have no price or code. Tour Leader is labelled free but conditions are absent. Neither can be enabled as a universally free bookable option.
- C04 B&W 420 exceeds its Booking 386. Do not use a universal channel discount or assume B&W is always cheapest.
- Safari durations are explicitly 2 days/1 night and 3 days/2 nights. Price unit, passenger capacity, accommodation, meals, permits and transport inclusions remain unconfirmed.
- Validity crosses calendar years. A future implementation needs dated rate plans, channel access and currency-safe totals; expiry cannot silently carry these prices forward.

## Required business confirmation

Confirm direct website channel; billing unit for rooms, tents, packages and vehicles; tax/meal inclusions; Single/Double/Twin stock sharing and physical beds; Premium Luxury rates; Tour Leader terms; authoritative code legend. This rate list does not resolve Pay@Hotel-only external checkout or missing payment-link delivery.

This folder is a local business reference outside the Git checkouts. Agency/guide price columns have not been published on the customer-facing site.

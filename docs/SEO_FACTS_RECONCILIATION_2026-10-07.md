# B&W Sahara Sky — SEO Entity & Facts Reconciliation

Date: 2026-10-07
Status: working evidence register. Public-source facts are not automatically approved hotel facts.

## Purpose

Keep SEO/entity work moving without inventing business data. A value can be used in production schema/content only when it is already published by the hotel's own CMS or confirmed by the owner. OTA/Google data below is evidence for reconciliation, not an instruction to overwrite the CMS.

## Strong public evidence

### Current OTA name
Booking.com currently presents the property as **B&W Sahara Sky Hotel**.

Source:
https://www.booking.com/hotel/eg/b-amp-w-sahara-sky-camp-al-farafra-desert.html

### Address/location wording
Booking.com:
Al Wahat Al Bahriya - Al Farafra Road Al Wahat Al Bahriya, 12935 Bawiti, Egypt

Google Hotels:
Al Wahat Al Bahriya - Al Farafra Rd, Al Wahat Al Baharia, Giza Governorate 12935, Egypt

These describe the same general property location but are not identical enough to manufacture a PostalAddress from them without owner/CMS confirmation.

### Current Booking.com room types
- Tent
- Standard King Room
- Standard Twin Room
- Superior King Room
- Superior Twin Room
- Deluxe King Room
- Deluxe Twin Room
- Standard Triple Room

Do not hardcode room availability, remaining-room counts, prices or offers from OTA pages. They are date-dependent.

### Amenities repeatedly visible on Booking.com
- Outdoor swimming pool
- Restaurant
- Room service
- Airport shuttle
- Free Wi-Fi
- Private parking
- Breakfast
- 24-hour front desk appears in property description

Restaurant name shown by Booking.com: **Dune & Dine**.

These still require owner/CMS reconciliation before being emitted as permanent Hotel schema facts.

### Booking.com policy currently shown
Booking.com currently displays check-in from 12:00 PM to 2:00 PM and check-out from 12:00 PM to 2:00 PM.

This conflicts with old Riorelax demo seed data (2 PM / 10 AM) and with earlier unverified project notes. Do not add checkinTime/checkoutTime to schema until the hotel confirms its actual direct-booking policy.

## Entity consistency issue

Google Hotels currently presents the entity as **B&W Sahara Sky Camp**, while Booking.com presents it as **B&W Sahara Sky Hotel**.

Google Hotels also currently shows:
- phone: +20 10 98255777
- address on Al Wahat Al Bahriya - Al Farafra Rd
- checkout: 12:00 PM

This naming mismatch is a Local SEO/entity cleanup item. Do not change the website back to "Camp" merely to match a stale/legacy Google entity. First confirm the official brand name, then align the owned Google profile and other citations to that decision.

Google Hotels evidence:
https://www.google.com/travel/hotels/entity/ChkImKWIq8KR6MtAGg0vZy8xMXBkcjAxbjNtEAE

## Social evidence

A Linktree branded @Bwsaharaskyhotel currently links hotel TikTok, Instagram and Facebook profiles and describes the brand as B&W Sahara Sky Hotel.

Source:
https://linktr.ee/Bwsaharaskyhotel

Do not add a social URL to sameAs until the exact destination profile is confirmed as official/owned.

## Production rules

1. CMS/owner truth beats OTA aggregators.
2. Never publish Riorelax demo contact/policy data.
3. Never copy dynamic OTA prices, availability, review counts or ratings into static schema.
4. Do not add AggregateRating unless the rating is visible on the site and the implementation satisfies search-engine policy.
5. Do not add check-in/out until the direct-booking policy is confirmed.
6. Do not create a duplicate Google Business Profile. Reconcile/claim the existing entity first.
7. Keep the canonical entity name consistent across site title, Hotel schema, Google Business Profile, major OTAs and official social profiles after owner confirmation.

## Still requires first-party confirmation

- final official public brand: Hotel vs Camp
- exact postal address / map pin / coordinates
- direct-booking check-in and check-out policy
- official phone and email for the website
- exact official social profile URLs
- permanent amenities/services to advertise on the website
- room inventory/specifications in the hotel's own CMS
- whether Dune & Dine is the permanent official restaurant name

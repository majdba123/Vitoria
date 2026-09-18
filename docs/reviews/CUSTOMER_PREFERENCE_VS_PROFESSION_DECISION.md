# Customer "Profession" Field — Decision Record

## Requirement source

The stakeholder's customer-profile examples reference professions such as
**مهندس زراعي** (agricultural engineer) and **طبيب بيطري** (veterinary
doctor), suggesting customers may want a stored "profession" field.

## Decision

**`preferred_product_type` is Product Preference — it is NOT a profession,
and it is not labeled as one anywhere in the UI.**

The existing `preferred_product_type` column on `users` (values:
`agriculture` / `veterinary`) models **what kind of products the customer
buys**, not **what job they hold**. The two concepts overlap in this
marketplace (an agricultural engineer tends to buy agricultural supplies),
but they are not the same fact, and conflating them would be wrong:

- A customer with no stated profession still has a product preference
  (everyone buying on the platform needs one for recommendations and
  catalog defaults).
- A customer's profession, if ever collected, is personal data with no
  purchase-flow purpose; preference directly drives what the store shows
  them.

## Current implementation

- Profile screen (`resources/js/Components/preferences/ProductType.jsx`)
  labels the field as **تفضيل المنتجات / Product Preference**, never
  "profession" (مهنة).
- Admin customer directory filters it as **Product Interest**
  (`admin.th_product_interest`, `admin.all_product_interests`).
- The values are rendered as product-domain labels (Agriculture /
  Veterinary), never as job titles.

## What was deliberately NOT done

No new `profession` column, no profession picker, and no re-labeling of the
preference field as "profession" were added in this pass, because:

1. The stakeholder's examples (مهندس زراعي / طبيب بيطري) read as personas
   describing the target audience, not as a required stored attribute.
2. Adding a second, similar-sounding field risks both confusion and data
   divergence ("preference: agriculture, profession: veterinarian") with no
   defined business use for the stored profession value.

## Trigger to revisit

If the stakeholder confirms that profession must be a separately stored,
editable customer attribute (e.g. for segmentation, syndicate reporting, or
professional-pricing rules), add a dedicated nullable `profession` column +
profile field, and keep `preferred_product_type` exactly as it is. That is a
business-decision-gated change, not a localization or labeling fix.

# Plan: transferring a property to another TenaFi account

**Status:** plan for review by Kevin and Glen. Nothing here is built yet.
**Switch:** ships behind a new admin setting `property_transfers_enabled`,
which starts **off**.
**Risk:** Needs Kevin. It touches the database, who can see what, and guest
data.

## 0. What this means (confirmed)

A **property owner** (the TenaFi account holder: a host or business) can hand
one of their properties to **another TenaFi account**, together with its WiFi
page, devices, guest list and history. Only the current owner can start it,
and the new owner must accept. **Confirmed on 11 November 2026: owner to
owner.**

Typical cases: a rental or café is **sold**; a new **manager** or management
company takes over; an owner moves a property **between their own accounts**.

## 1. What moves, and what doesn't

| Moves to the new owner | Stays with the old owner or is reset |
|---|---|
| The property, its name, branding and WiFi page | The old owner's account, other properties and billing |
| Access points / TenaFi devices (they keep working, no visit needed) | The old owner's subscription: their unit count goes down by one |
| Guest list, visits, reviews data and WiFi history | The old owner's team members' access to this property (removed) |
| Campaigns and their results; offers, amenities, events | PMS connection credentials (cleared; the new owner reconnects) |
| Monthly reports history for this property | Notification settings (the new owner uses their own) |
| A device on **Pay monthly** (only if the new owner takes over the agreement; see §5) | |

In the database, most records hang off `property_id` (guests, access points,
amenities, orders, visits, staff connections, WiFi events), so they follow the
property automatically. Records that also store a `user_id` (`campaigns`,
`events`, `guests`) need that field moved too. **Check first** what
`guests.user_id` holds, because its relation is described as "the user
account linked to this guest".

## 2. Guest data: the legal part ⚖️

Guests gave consent to *the venue* (for example "Stay Awhile Rentals"). After a
sale, a different person or company controls that data. Under the **Data
Protection Act, 2019**, that's a change of data controller. The safe way:

1. **Tell the guests.** After the transfer, one message goes out (WhatsApp,
   SMS or email) in the venue's name: *"Stay Awhile is now run by [new
   owner]. You'll keep getting our offers; reply STOP to opt out."*
2. **Wait before marketing.** The new owner can't send campaigns to the
   transferred list for **14 days**, so guests have time to opt out. Opt-outs
   are honoured and carried over.
3. **Going forward:** reword the WiFi page consent so it names the venue and
   says the list may pass to a new owner if the business is sold.
4. The old owner **doesn't keep a copy** of the guest list after the
   transfer. They get a summary report (numbers only) for their records.

Ask a lawyer to confirm the 14-day notice approach and the consent wording.

## 3. How it works, step by step

1. **Start (old owner only).** Property → Settings → **Transfer this
   property**. The owner enters the new owner's email or phone, ticks what to
   include (guest list: on by default, with the notice above), and confirms
   with their **password and 2FA code**. Admins and team members **cannot**
   start a transfer.
2. **Invite.** The new owner gets an email and SMS link (single use, expires
   in 7 days). They sign in or create a TenaFi account, verify their phone,
   and see exactly what they'll receive (counts: guests, devices, campaigns).
   They accept the terms, including the guest notice.
3. **Device check.** If a device on the site is on **Pay monthly** with a
   balance, either the old owner pays it off, or the new owner takes over the
   agreement (identity checks and a signature, as in the device plan). The
   transfer can't complete until one of the two happens.
4. **Cooling-off (48 hours).** Both sides see "Transfer pending". The old
   owner can cancel. TenaFi support is notified and can hold it if something
   looks wrong (e.g. the old owner says it wasn't them). The old owner's email
   and phone get an *"If this wasn't you, cancel here"* message.
5. **Hand-over (automatic, one transaction).** The property moves to the new
   owner; the linked records follow; team access and PMS credentials are
   cleared; the guest notice is queued; the old owner loses access
   immediately. **The WiFi keeps running throughout**: devices and the WiFi
   page aren't touched.
6. **After.** The new owner sees the property in their dashboard. If they have
   no plan yet, the WiFi keeps running for a **14-day grace period** while
   they subscribe. Both sides get a confirmation email.

## 4. Records and safety

- New table `property_transfers`: property, from user, to user, invited
  email/phone, options (JSON), status (`pending_acceptance →
  pending_devices → cooling_off → completed | cancelled | expired`),
  timestamps for each step, the IP address of each action, and counts of what
  moved.
- Every action is written to an audit log.
- **Undo:** for 30 days, an admin can reverse a completed transfer, but only
  when **both** owners agree in writing (support ticket).
- Rate limit: one pending transfer per property; a few per account per day.
- A property can't be transferred while an event on it is live or tickets are
  on sale (to keep it simple at first).

## 5. Devices on Pay monthly

A transfer never silently moves a debt. The options:

1. The old owner **pays off** the remaining balance (the payoff amount is shown
   in the transfer screen), then the device moves with the property, owned by
   the new owner.
2. The new owner **takes over** the agreement: identity checks, a new signed
   agreement for the remaining months, and the same instalment dates.

## 6. Screens

- **Host → Property settings:** "Transfer this property" (start, status,
  cancel).
- **Invite page** for the new owner (accept or decline), working at 390px.
- **Admin → Property transfers:** a `DataTable` of all transfers with status,
  plus hold, cancel and (with both owners' agreement) reverse.

## 7. Build order (behind the switch)

1. `property_transfers` table, the start/accept/cancel flow and the
   hand-over transaction, without devices on Pay monthly (block those).
   **Built (November 2026):** Properties → Transfers, behind Admin → Settings
   "Property transfers" (off). The owner picks a property, enters the new
   owner's email and their own password; the recipient, signed in with that
   email, accepts or declines from the same page (an in-app notification and
   a notice on Properties tell them); the owner can cancel while it waits;
   offers expire after 7 days. On acceptance, in one transaction: the
   property, its campaigns and events move; guests, devices, visits, orders
   and amenities follow the property; the old owner's team loses access at
   once; a PMS connection is marked disconnected. Blocked while a device on
   the site is still being paid off. Not yet: guest notice and the 14-day
   campaign hold (step 2), so until then use it only for moves between
   your own accounts; the 48-hour cooling-off and emails (step 3).
   Feature tests: only the owner can start; a host never sees another host's
   property before acceptance; everything linked moves; the old owner loses
   access; the WiFi isn't interrupted.
2. Guest notice message and the 14-day campaign hold.
3. Cooling-off, the *"wasn't you?"* message and the admin screen.
4. Device agreement payoff or take-over (after the device plan's first steps
   exist).

## 8. Open questions

1. ~~Confirm the meaning in §0~~ Confirmed: owner to owner.
2. Is 14 days' notice to guests enough? (lawyer)
3. Should moving between **your own** accounts skip the guest notice? (Same
   controller, so probably yes.)
4. Who pays if the new owner never subscribes after the grace period? (WiFi
   pauses, as for an unpaid plan.)

## 9. Related, separate idea: guest self-service

Not part of this plan. A guest could ask, from a link in any TenaFi message, to **see, move or delete
their details**: their right of access and portability under the Data
Protection Act. That would be a separate, smaller feature: a guest
self-service page with a one-time code by SMS, showing which venues hold their
details, with "stop messages" and "delete me" per venue. Moving a guest's data
*to another venue* isn't recommended: each venue needs the guest's own consent,
which they give by logging in there.

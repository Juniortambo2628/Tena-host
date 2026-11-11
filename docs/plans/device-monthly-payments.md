# Plan: paying for a TenaFi device monthly, with safeguards

**Status:** plan for review by Kevin and Glen. Nothing here is built yet.
**Switch:** everything ships behind a new admin setting
`device_financing_enabled`, which starts **off**.
**Risk:** Needs Kevin. It touches payments (M-Pesa, Paystack), the database
and access control.

## 1. What we're protecting against

On "Pay monthly", a client gets a device worth KES 25,000 to 48,000 for KES 0
upfront and pays it off over 12 months. Until the last payment, TenaFi carries
the risk of:

| Risk | Example |
|---|---|
| Non-payment | The client stops paying in month 3. |
| Theft or disappearance | The client (or someone at the site) takes the device and closes the business. |
| Moving the device | The device is unplugged and used at another site that isn't on a plan. |
| Tampering | The device is factory-reset to cut it off from TenaFi. |
| Damage | The device comes back broken. |

**A limit to be honest about:** a physical factory reset can't be prevented.
After a reset the device works as an ordinary WiFi access point with any
controller, so we can't "brick" it remotely. Protection therefore comes in four
layers: **(1) the contract and checks before install, (2) service that stops
when payments stop, (3) tracking, so we notice quickly, and (4) a clear return
and recovery process.**

## 2. Decisions needed first

1. **Ownership:** the device stays TenaFi's property until the final payment,
   then becomes the client's. The site already says "then it's yours", so this
   matches. ⚖️ This makes it a hire-purchase-style agreement. Check whether the
   Hire-Purchase Act (Cap. 507) applies and what it requires (written
   agreement, statements, notice before repossession).
2. **Deposit:** the site promises "KES 0 upfront", so there's no deposit. The
   **first month is paid at installation** instead (plan + device add-on, by
   M-Pesa, before the technician leaves).
3. **Late fees:** none, or a small fixed fee? Keep it simple to start: none.
4. **Grace period and suspension days** (proposed in §5): agree the numbers.
5. **Credit reference bureau (CRB) reporting:** only once a lawyer confirms we
   can, under the Data Protection Act and CRB rules.
6. **Insurance:** for the device fleet, or offered to clients?

## 3. Before installation: who gets "Pay monthly"

- **Verified identity:** national ID or passport; a KRA PIN or business
  registration for businesses; the M-Pesa number checked by a KES 1 test or a
  confirmed first payment.
- **Verified site:** address, GPS pin and a photo of the premises, recorded at
  the site survey ("Book a demo").
- **Signed agreement** (e-signature in the dashboard) covering: ownership until
  paid off; the monthly amount and dates; what happens if a payment is missed,
  **including that service is paused remotely**; early payoff; return
  condition; and charges for loss or damage.
- **Limits:** a maximum number of financed devices per new client (e.g. 2)
  until 3 payments are made on time.
- An admin approves each agreement before installation (a single click, with
  the checks listed above shown).

## 4. At installation: tie the device to the client

- Record the device's **serial number and MAC** against the agreement and the
  site (the `access_points` row already holds the MAC).
- Fix a **tamper-evident TenaFi asset sticker** with a QR code, and take a
  photo of the installed device with the sticker visible.
- Adopt the device into **TenaFi's controller site** for that client. Turn off
  local management (no local login, no SSH), so only TenaFi can change it.
- Take the **first payment** before the technician leaves. The agreement's
  start date is the installation date.

## 5. Every month: collecting, reminding, pausing

### Data (new tables)

- `device_agreements`: client (user), site (property), access point, device
  type, price, monthly add-on, months, start date, paid-off date, and **status**:
  `pending_approval → active → overdue → suspended → return_requested →
  returned | paid_off | written_off | lost_or_stolen`.
- `device_instalments`: agreement, due date, amount, amount paid, paid at,
  payment reference, status (`due`, `paid`, `partly_paid`, `late`, `waived`).
- `device_events`: an audit log of every reminder, charge attempt,
  suspension, resume, admin action and note.

Payments go through the existing `MpesaTransaction` / `SubscriptionService`
path. The monthly bill is **one amount** (plan + device add-on). A partial
payment is allocated to the oldest unpaid instalment first.

### Collecting

- **M-Pesa STK push** on the due date (existing `MpesaService`), with the
  amount and "TenaFi device + plan" as the description.
- Later: **M-Pesa Ratiba** (standing orders) or a saved card (Paystack), so
  payments happen without a prompt.
- A daily scheduled command, `devices:billing` (08:00, Africa/Nairobi), works
  out what's due, sends reminders and moves agreements through the steps below.

### The reminder and pause ladder (proposed days)

| Day | What happens | Client sees |
|---|---|---|
| D-3 | Reminder by WhatsApp/SMS and email | "Your TenaFi payment of KES X is due on …" |
| D0 | STK push sent; reminder | M-Pesa prompt |
| D+1, D+3 | STK push retried; reminder | "Payment overdue: pay now to keep your WiFi page running" |
| D+7 | **Status: overdue.** Dashboard banner; campaigns and review requests paused | Banner with a "Pay now" button |
| D+14 | **Status: suspended.** TenaFi WiFi paused (see below); host told by phone call as well | "Your TenaFi WiFi is paused until payment" |
| D+30 | Formal notice: pay the full overdue amount or return the device within 14 days | Letter by email and SMS |
| D+45 | **Return requested**; collection visit booked | Visit scheduled |
| D+60+ | Not recovered: `lost_or_stolen` or `written_off`; next steps decided by an admin (legal, CRB if allowed) | |

Every step: the client is told *before* it happens; an admin can **pause the
ladder** (dispute, hospital, agreed payment plan) or **waive** an instalment,
with a reason that's logged; paying the overdue amount **restores everything
within minutes** (the M-Pesa callback resumes the service automatically).

### "Deny access": how a pause works technically

Two layers, both reversible:

1. **App level (simple, build first):** in `WifiPortalController`, a property
   whose agreement is `suspended` stops authorising guests and shows a neutral
   "WiFi is paused at this venue" page. The dashboard still lets the host log
   in, **pay**, and **export their guest list**: under the Data Protection Act
   it's their data.
2. **Device level (stronger):** add `disableDevice()` / `enableDevice()` to
   `App\Services\Unifi\UnifiService` to disable the access point (or its
   TenaFi network) through the controller API. This also stops anyone using
   the device for ordinary WiFi while it's paused. Test it on the controller
   first, since the cloud API may not support it everywhere.

The host's own router and internet are never touched; only the TenaFi network
pauses.

## 6. Tracking: noticing theft or moving quickly

These build on `access_points.last_seen` and the existing `alerts:check`
command:

- **Offline alert:** a financed device offline for 6+ hours during business
  hours → alert to TenaFi support (the host already gets outage alerts).
- **Moved:** the device reports from a different public IP / internet provider
  than the one recorded at installation → flag "possibly moved"; support calls
  the client.
- **Reset or removed:** the device disappears from TenaFi's controller site
  (not just offline) → flag "possible reset"; call within 24 hours; if there's
  no answer, the agreement goes to `return_requested`.
- **Asset register:** serial numbers of every financed device. If one is
  reported stolen, give the serial to the distributor and the police (OB
  number).

## 7. Returns and payoff

**Early payoff (any time):** the dashboard shows the payoff amount (the
remaining instalments). Paying it marks the agreement `paid_off`, and the
device is the client's. This happens automatically after the 12th payment.

**Return** (client leaves in the first 12 months, an upgrade swap, or default):

1. A return request is created, by the client in the dashboard or by an admin.
2. Pickup or drop-off is booked; the client gets the date by SMS.
3. The technician collects the device, checks it against a checklist (device,
   bracket, cable, damage, sticker intact) and photographs it.
4. A final statement is issued: overdue amounts, plus a charge for damage or
   missing parts if any. For leaving early, the terms
   already say "pay off the rest of the device or return it", so a returned
   device in good condition settles the device balance.
5. The device is removed from the client's controller site and unlinked from
   the property, then refurbished and returned to stock.
6. The client keeps their guest list (export offered); the WiFi page is
   switched off.

## 8. Screens

- **Admin → Device agreements:** a `DataTable` with status, client, site,
  device, next due date, balance and days overdue, plus filters ("overdue",
  "suspended", "possibly moved"). Row actions: send reminder, retry charge,
  pause ladder, suspend now, resume, start return, mark returned, waive,
  write off. A detail dialog shows the instalments and the audit log.
- **Host → Billing:** device balance, next payment, payoff amount, "Pay now",
  and the signed agreement (PDF).
- **Technician checklist** (phone-friendly): serial, MAC, photos, sticker,
  first payment, signature.

## 9. Build order (each step its own small PR, behind the switch)

1. Tables, models and the admin list, with agreements recorded by hand. This
   is useful on its own as the asset register. **Built (November 2026):**
   Admin → Devices (shown in the menu once Admin → Settings "Device register"
   is on). Record a device; see its instalments, balance and days late;
   record part or full payments with the M-Pesa code; change the status with a
   note; every action is logged. The device is marked paid off automatically
   after the last instalment.
2. Instalment schedule, `devices:billing`, reminders and M-Pesa STK collection
   with automatic marking as paid.
3. Overdue banner, campaign pause, and the app-level WiFi pause and resume.
4. Device-level disable/enable through the controller (after testing on a
   spare unit).
5. Returns workflow and technician checklist.
6. Theft and moving detection alerts.
7. Agreement signing in the dashboard (e-signature) and KYC upload.

Tests for each: Pest feature tests for every status change, the ladder days
(using `travelTo`), the M-Pesa callback resuming service, and that a host only
ever sees their own agreements.

## 10. Legal checklist ⚖️

- Hire-Purchase Act (Cap. 507): does it apply? If so, what must the agreement
  and notices contain, and what are the rules before repossession?
- Consumer Protection Act, 2012: fair terms, clear total cost.
- Data Protection Act, 2019: collecting ID and KYC data, retention, and the
  right to export.
- Is remotely pausing service allowed when it's stated in the signed agreement?
- CRB reporting: is it allowed, and through which licensed bureau?

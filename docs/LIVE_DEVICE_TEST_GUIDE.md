# Live Device Test Guide

Step-by-step walkthrough for connecting real WiFi hardware and verifying guest capture,
splash-page routing, and host engagement. Written for the Hestia VPS production site
(https://tena-fi.com). Do these in order.

## 0. Prerequisites (admin)

1. **UniFi controller credentials** — Admin → Settings → WiFi (UniFi):
   - Controller URL (e.g. `https://192.168.1.1:8443` or the UniFi OS URL)
   - Username and password
   - Site (usually `default`), console type (UniFi OS vs classic)
   - Click **Test connection**. Must report success before continuing.
2. **Guest network on the controller** — for the target SSID:
   - Enable the guest hotspot / captive portal
   - Portal type: **External Portal Server** (also called "External Portal")
   - Portal URL: `https://tena-fi.com/portal`
   - Allowed hosts before auth (if the controller supports it):
     `tena-fi.com`, `www.tena-fi.com`
   - The SSID name recorded in the property must match the SSID broadcast.

## 1. Host side (per property)

1. **Configure the property** (WiFi Access → Configure Unit):
   - Basic Info: name, address
   - WiFi Configuration: WiFi SSID (must match the controller's guest SSID),
     occupancy limit (number of recent guests that triggers an alert)
   - Brand & Splash: pick one of the five templates (Bento, Horizon, Clover,
     Monogram, Nocturne) and an accent colour, plus a cover photo
   - Save, then open **Splash Studio** (property card or property page) to add the
     cover photo and logo, welcome heading and note, house rules, emergency numbers,
     nearby picks, the Google review link, offers and cross-sell cards. The studio
     shows a live phone/laptop preview of unsaved changes; **Open full preview**
     shows the saved page in a new tab (previews never authorize a device).
   - After a guest connects, a property with splash content shows the same tabs on
     the "You're connected" page, with a button on to the page they were opening.
2. **Register the access point** (Access Points → Add Access Point):
   - MAC address: the AP's device MAC (usually on the label or in the controller
     device list). Colon, hyphen or bare hex all accepted — stored normalized.
   - Assign it to the property.
3. Open the property page — it now shows:
   - A **WiFi setup** checklist with the portal URL to paste into the controller
   - **WiFi guest engagement**: guest count, visits, last-24h connections,
     opt-ins, and the latest guests (auto-refreshes every 30 s)

## 2. Guest side (the actual device test)

On a phone (turn off mobile data so WiFi is the only route):

1. Join the property's guest SSID.
2. The phone should redirect to `https://tena-fi.com/portal?...` within a few
   seconds. The page shows the property name, chosen template, and logo.
   - If it does not redirect, open `http://neverssl.com` in the browser —
     captive-portal detection uses plain HTTP.
3. New guest: fill in first name + WhatsApp number + consent, tap **Connect**.
4. You should land on the original destination (or a connected page) and have
   internet access.
5. Verify on the host dashboard:
   - Guest appears under the property's engagement list
   - Visit counted as 1
6. Reconnect the same phone within 6 hours → one-tap "Welcome back", no new
   visit. After 6 hours → counts as a second visit.

## 3. What "success" looks like end-to-end

| Step | Expected |
|---|---|
| GET `/portal` with AP MAC | Property name + template + logo shown |
| POST `/portal/connect` (valid) | Device authorized on the controller, guest row created/updated, redirect to original URL |
| POST `/portal/connect` (bad phone/consent) | Form re-shown with field errors (HTTP 422), no guest row, device not authorized |
| AP not registered in TenaFi | Splash shows "ask your host to link this access point" (HTTP 422) |
| Controller unreachable | Splash re-shown with "We could not connect you" (HTTP 503), no guest row, no visit counted |
| Returning device | One-tap button, visit counted (6-hour visit gap) |

## 4. Troubleshooting

- **Splash never appears**: check the controller's external-portal URL and that
  the AP's MAC is registered against the right property. Test with
  `https://tena-fi.com/portal?ap=<AP_MAC>&id=<phone_MAC>&ssid=<SSID>`.
- **"This access point is not linked"**: register the AP MAC under Access Points.
- **"We could not connect you" (503)**: the controller rejected authorization —
  check Admin → WiFi settings credentials and the site name. No guest is recorded
  on failure, by design.
- **Guest not showing on the dashboard**: dashboard polls every 30 s; guest rows
  only appear after successful authorization. PMS-synced and manually added
  guests have no device MAC and are excluded from the WiFi engagement counters.
- **Client count differs from guest count**: the AP's client count comes from the
  controller every ~5 minutes and includes devices that never completed the form.

## 5. SMS / WhatsApp routing (messaging test)

When messaging credentials are available (Africa's Talking, WhatsApp Cloud API),
the STOP and reply webhooks are already routed:

- WhatsApp inbound: `https://tena-fi.com/api/whatsapp/webhook`
- SMS inbound: `https://tena-fi.com/api/sms/inbound`
- Both are CSRF-exempt; M-Pesa callbacks (`/api/mpesa/*`) are exempt too.

Set the callbacks in the Meta app and the Africa's Talking dashboard once the
credentials land in the production `.env`.

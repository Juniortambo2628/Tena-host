# TenaFi device images: what's safe to use, and prompts to make them

For Glen and Kevin. The new device pages (`/pricing/lite`, `/pricing/pro`,
`/pricing/outdoor`) each have one main picture and three more picture slots.
They're uploaded in **Admin → Public Pages → Devices & pricing**, so no code
change is needed once the images exist.

## 1. Can we use pictures of the actual device?

*This isn't legal advice. Check the points marked ⚖️ with a lawyer or with the
supplier before going live.*

| Option | OK? | Why |
|---|---|---|
| The manufacturer's product photos and marketing images (from their website, a shop or a datasheet), even with their logo edited out | **No** | The manufacturer owns the copyright. Resellers usually need written permission or access to the manufacturer's reseller asset library. |
| The manufacturer's name or logo on our site | **No** | It's their trademark, and it would tell competitors and customers what we resell. The site already blocks these names (a test fails if they appear). |
| **Our own photos of the real device**, installed at a client's site or on a plain background, with a TenaFi sticker | **Best option** | We own photos we take ourselves. They show customers exactly what they get, which matters under the Consumer Protection Act (no misleading pictures). ⚖️ Check that the reseller/distributor agreement allows stickers or relabelling, and don't claim TenaFi *makes* the device ("TenaFi device" as the name of our service is fine). |
| AI-generated pictures with the TenaFi logo | **OK with care** | Usable as long as the picture looks like what is actually installed: same shape, size and colour. A picture that shows a different-looking device could mislead buyers. Don't copy the manufacturer's logo or distinctive markings. ChatGPT's terms give us rights to the images we generate. |

**Recommendation:** use AI images now to launch, and replace them with real
photos from the first installations. Keep the people in AI images fictional,
and don't use real clients' faces without their written consent.

## 2. How to make the images in ChatGPT

1. Start a new ChatGPT chat with image generation turned on.
2. **Upload the TenaFi logo file** (the yellow "TenaFi" wordmark with the WiFi
   arcs, PNG with a transparent background) and say: *"Use this exact logo
   wherever I ask for the TenaFi logo. Don't change its colours or letters, and
   don't add any other logos, brand names or text."*
3. For the device images, describe the device as in section A. If you have
   **your own** photo of a unit (not the manufacturer's), you can upload it
   and say: *"Draw a device with this shape, but with no markings except the
   TenaFi logo."*
4. Paste one prompt at a time from below. If a result adds stray text or logos,
   reply: *"Same image, but remove all text and logos except the TenaFi logo."*
5. Download each image at full size and either upload it in Admin (slots listed
   under each group) or send it to me in Claude Code and I'll place it.

**Style line to add to every prompt** (it keeps the set consistent):

> Photorealistic, natural warm daylight, modern East African setting (Nairobi
> or the Kenyan coast), people of African descent, clean and uncluttered,
> shallow depth of field. No text, signs or logos anywhere except the TenaFi
> logo where asked.

### A. Device pictures (the main picture for each device)

Size: wide, about **1320 × 780**, transparent or plain light background.
Upload to **Device 1/2/3: picture**.

What the real device looks like (from the reference pictures shared with this request): a
**smooth, matte white, round flat disc**. A **smaller circle in the centre**
has a **soft blue glowing ring** around its edge, and a small logo sits in
the middle of that circle. In our pictures the **TenaFi logo** takes that
spot. Don't upload the manufacturer's photos to ChatGPT as a reference
(ChatGPT might copy them, logo included); describe the device instead, as
below, or use your own photo of a unit.

1. **TenaFi Lite, front view:** *"A product photo of a smooth, matte white,
   round, flat WiFi access point seen straight on from the front, like a
   white disc. In the centre there is a smaller flat circle with a thin, soft
   blue glowing ring around its edge, and the TenaFi logo small and light grey
   in the middle of that circle. Even studio light, a soft reflection below,
   plain white background. Wide format 1320×780 with the device centred."*
2. **TenaFi Lite, angled:** *"The same white disc-shaped WiFi device, seen at a
   low three-quarter angle so its slim, gently domed profile shows, blue
   ring glowing, TenaFi logo in the centre circle. Plain white background,
   soft shadow, wide 1320×780."*
3. **TenaFi Pro:** *"The same style of product photo of a slightly larger and
   thicker version of the white disc WiFi device, with the blue glowing ring
   and the TenaFi logo in the centre circle, seen at a low three-quarter angle.
   Plain white background, soft shadow, wide 1320×780."*
4. **TenaFi Outdoor:** *"A product photo of a weatherproof outdoor WiFi
   access point: a white, rounded, rugged unit on a short grey metal pole
   bracket, with a small blue status light and the TenaFi logo small on the
   front. Plain white background, soft shadow, wide 1320×780."*

### B. How it works: how it connects and how it's mounted

Size: landscape **1600 × 1200**, clean 3D product-render style on a
white or very light grey background (the style of the reference sheet
shared with this request). Use the device description from A. These work as a "How it works"
strip on /pricing or as each device's page pictures.

1. **How it connects (diagram):** *"A clean 3D illustration on a white
   background. On the left, a small globe icon labelled 'Internet'. A thin
   blue line runs from it to a generic black home/office internet router
   (no brand) labelled 'Your router'. Another thin blue line, representing one
   network cable, runs to the white disc-shaped TenaFi WiFi device, glowing
   softly blue, labelled 'TenaFi device'. Small grey rounded labels, simple
   sans-serif text, nothing else written. Lots of white space."* (If it has a
   switch in between, add: *"between the router and the device, a slim grey
   network switch labelled 'Switch (only for several devices)'."*)
2. **On the wall:** *"A 3D render of the white disc-shaped TenaFi WiFi device
   mounted on a smooth light-grey wall next to wooden slat panelling and a
   green plant leaf, soft blue light ring, TenaFi logo in the centre circle.
   Calm, minimal, soft daylight."*
3. **Wall mounting, exploded view:** *"An exploded-view 3D render on the
   same wall: the white disc TenaFi device floating slightly away from the
   wall, its round white mounting bracket behind it, four screws and four wall
   plugs in a line pointing into four drill holes, and one network cable
   coming out of a hole in the wall towards the device. Clean, technical,
   minimal, light background."*
4. **On the ceiling:** *"A 3D render of the white disc TenaFi device mounted
   flush on a white speckled ceiling tile, seen from below at an angle, soft
   blue ring glowing, TenaFi logo in the centre circle. Minimal, bright."*
5. **Ceiling mounting, exploded view:** *"An exploded-view 3D render of the
   white disc TenaFi device below a ceiling tile: above it the round mounting
   bracket and a backing plate on the other side of the tile, screws, and one
   network cable dropping down through the tile to the device. Clean,
   technical, light background."*

### B2. The guest journey (optional, for the homepage)

These follow the four steps on the site:
*We install it → Guests opt in → We follow up → They come back*.

1. **We install it:** *"A TenaFi technician in a dark polo shirt with a small
   TenaFi logo on the chest, on a short ladder, fixing the white disc-shaped
   TenaFi WiFi device to the ceiling of a bright café. The café owner watches
   and smiles. One network cable runs neatly to a router on a shelf."*
2. **Guests opt in:** *"Close-up of a guest's hand holding a smartphone in a
   hotel lobby. The phone screen shows a branded WiFi welcome page with a
   'Connect to WiFi' button and fields for name and phone number. The TenaFi
   logo appears small at the bottom of the screen. The lobby is softly
   blurred behind."*
3. **We follow up:** *"A smiling woman relaxing on a sofa at home, looking at
   her phone, which shows a friendly WhatsApp-style message from a café: a
   thank-you and a 'Leave us a review' button. Warm evening light."*
4. **They come back:** *"Friends returning to the same café and greeting the
   barista by name, a busy happy scene. A small 'Free WiFi by TenaFi' table
   stand with the TenaFi logo sits on the counter."*

### C. Each device in use (three per device, for its own page)

Size: landscape **1600 × 1200**. Upload to **Device N: page picture 1/2/3**.

**TenaFi Lite (Device 1)**
1. *"A stylish one-bedroom Airbnb apartment living room in Nairobi with a
   small round white WiFi device with the TenaFi logo on the ceiling. A guest
   sits on the sofa using a laptop."*
2. *"A modern hair salon with a client in the chair checking her phone. A
   small round white TenaFi device is visible on the ceiling above."*
3. *"Close-up of the small round white TenaFi device on a white ceiling,
   blue light ring softly glowing, TenaFi logo visible."*

**TenaFi Pro (Device 2)**
1. *"A busy restaurant at dinner time, many diners using phones. A round white
   TenaFi WiFi device with the TenaFi logo is mounted on the ceiling."*
2. *"A boutique hotel corridor or lounge with a round white TenaFi device on
   the ceiling, guests arriving with luggage."*
3. *"A gym or clinic waiting area with people on their phones, a round white
   TenaFi device on the ceiling."*

**TenaFi Outdoor (Device 3)**
1. *"A garden wedding reception at dusk with string lights, guests taking
   photos on their phones. A white weatherproof TenaFi device with the TenaFi
   logo is mounted on a pole at the edge of the lawn."*
2. *"A restaurant terrace by the sea at the Kenyan coast, diners at tables, a
   white outdoor TenaFi device mounted on a wall bracket."*
3. *"Close-up of the white outdoor TenaFi device on a metal pole against a
   blue sky, TenaFi logo visible."*

## 3. Before uploading

- Check every image for invented text or a second logo, and regenerate if you
  find one.
- Check the device looks like the real one: round, white, the right size for
  the room.
- File names help: `tenafi-pro-main.png`, `tenafi-pro-use-1.jpg`, and so on.

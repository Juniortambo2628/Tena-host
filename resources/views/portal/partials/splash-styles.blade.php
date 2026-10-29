{{-- Self-contained splash styles: pre-login devices can't reach a CDN or font host yet.
     Five templates share one markup; each sets tokens and a wide-screen arrangement.
     Below 1024px every template is an app-like stack with a bottom tab dock. --}}
<style>
    /* Tokens live on body: the accent arrives as an inline style there, and derived colours must see it. */
    .sp {
        --accent: #FFD300; --accent-ink: #111;
        --accent-deep: #6b5a00; --accent-soft: #fff6c2;
        --ink: #17171b; --muted: #6c6c75; --canvas: #f3efe9; --paper: #fff; --paper-2: #f7f4ef;
        --field-bg: #f2efea; --danger: #b42318;
        --btn-bg: var(--ink); --btn-ink: #fff;
        --tabs-bg: rgba(23, 23, 27, .06); --tab-on-bg: var(--paper); --tab-on-ink: var(--ink);
        --r-card: 28px; --r-field: 14px; --r-btn: 999px;
        --sans: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        --display: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        --title-weight: 300; --title-track: -.04em;
        --shadow: 0 1px 2px rgba(25, 18, 8, .04), 0 22px 50px -22px rgba(25, 18, 8, .28);
    }
    @supports (color: color-mix(in srgb, red, blue)) {
        .sp {
            --accent-deep: color-mix(in srgb, var(--accent) 55%, #000);
            --accent-soft: color-mix(in srgb, var(--accent) 16%, #fff);
        }
    }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    .sp {
        font-family: var(--sans); color: var(--ink); background: var(--canvas);
        -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%; min-height: 100vh; line-height: 1.45;
    }
    :where(.sp) a { color: inherit; }
    .sp h1, .sp h2, .sp h3, .sp p { margin: 0; }
    .sp-icon { flex: none; display: block; }
    .sp-mark { display: none; }

    /* ---------- shared pieces ---------- */
    .sp-photo { background: #2a2622 center / cover no-repeat; }
    .sp-photo--empty {
        background-image: radial-gradient(120% 90% at 85% 10%, var(--accent) 0%, transparent 55%),
            radial-gradient(90% 80% at 0% 100%, var(--accent-deep) 0%, transparent 60%),
            linear-gradient(160deg, #2c2a27, #121214);
    }
    .sp-brand { display: flex; align-items: center; gap: 10px; font-weight: 650; font-size: 15px; letter-spacing: -.01em; }
    .sp-brand__logo { display: block; max-height: 40px; max-width: 150px; object-fit: contain; }
    .sp-brand__mono {
        width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center;
        background: var(--accent); color: var(--accent-ink); font-weight: 800; font-size: 18px;
    }
    .sp-brand__logo + .sp-brand__name { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
    .sp-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .28em; text-transform: uppercase; opacity: .85; margin-bottom: 14px; }
    .sp-title {
        font-family: var(--display); font-weight: var(--title-weight); letter-spacing: var(--title-track);
        line-height: .98; font-size: clamp(38px, 10vw, 52px); overflow-wrap: anywhere;
    }
    .sp-hl { color: var(--accent); }
    /* Headlines often sit on busy photos. */
    .splash--bento .sp-hero, .splash--nocturne .sp-hero { text-shadow: 0 2px 28px rgba(0, 0, 0, .45); }
    @media (max-width: 1023.98px) { .splash--clover .sp-hero, .splash--monogram .sp-hero, .splash--horizon .sp-hero { text-shadow: none; } .sp-hero { text-shadow: 0 2px 24px rgba(0, 0, 0, .4); } }
    .sp-lede { margin-top: 14px; font-size: 16px; max-width: 46ch; opacity: .9; }

    .sp-connect { background: var(--paper); border-radius: var(--r-card); padding: 24px; box-shadow: var(--shadow); position: relative; }
    .sp-connect__head { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
    .sp-badge-icon { width: 46px; height: 46px; border-radius: 15px; display: grid; place-items: center; background: var(--accent); color: var(--accent-ink); flex: none; }
    .sp-connect h2 { font-size: 19px; font-weight: 650; letter-spacing: -.015em; }
    .sp-connect__sub { font-size: 13px; color: var(--muted); }
    .sp-field { display: block; margin-bottom: 12px; }
    .sp-field > span:first-child { display: block; font-size: 12px; font-weight: 600; margin: 0 0 6px 2px; }
    .sp-field em { font-style: normal; font-weight: 400; color: var(--muted); }
    .sp input[type=text], .sp input[type=email], .sp input[type=tel], .sp select {
        width: 100%; font: inherit; font-size: 16px; padding: 13px 14px; border: 0; border-radius: var(--r-field);
        background: var(--field-bg); color: var(--ink); -webkit-appearance: none; appearance: none; outline: none;
        box-shadow: inset 0 0 0 1.5px transparent; transition: box-shadow .15s, background .15s;
    }
    .sp input::placeholder { color: var(--muted); opacity: .7; }
    .sp input:focus, .sp select:focus { box-shadow: inset 0 0 0 2px var(--ink); }
    .sp input[aria-invalid=true] { box-shadow: inset 0 0 0 2px var(--danger); }
    .sp-tel { display: flex; border-radius: var(--r-field); background: var(--field-bg); overflow: hidden; }
    .sp-tel input[type=tel] { background: transparent; padding-left: 6px; }
    .sp-tel-prefix { display: flex; align-items: center; padding-left: 14px; font-weight: 650; color: var(--muted); }
    .sp-birthday { display: grid; grid-template-columns: 1fr 2fr; gap: 8px; }
    .sp-check { display: flex; gap: 10px; align-items: flex-start; font-size: 12.5px; line-height: 1.45; color: var(--muted); margin: 8px 0 10px; }
    .sp-check input { width: 20px; height: 20px; margin: 0; flex: none; accent-color: var(--ink); }
    .sp-field-error { display: block; color: var(--danger); font-size: 12px; margin: 5px 2px 6px; }
    .sp-error { background: #fdecea; color: var(--danger); border-radius: 14px; padding: 12px 14px; font-size: 14px; margin: 0 0 14px; }
    .sp-submit {
        width: 100%; border: 0; border-radius: var(--r-btn); background: var(--btn-bg); color: var(--btn-ink);
        font: inherit; font-size: 16px; font-weight: 650; padding: 16px 22px; margin-top: 8px; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 10px; text-decoration: none; -webkit-appearance: none;
        transition: transform .15s, opacity .15s;
    }
    .sp .sp-submit { color: var(--btn-ink); }
    .sp-submit:active { transform: scale(.98); }
    .sp-submit:disabled { opacity: .55; cursor: default; }
    .sp-done { text-align: center; padding: 8px 0; }
    .sp-done__icon { width: 64px; height: 64px; border-radius: 50%; margin: 0 auto 16px; display: grid; place-items: center; background: var(--accent); color: var(--accent-ink); }
    .sp-done h2 { font-size: 24px; }
    .sp-done p { color: var(--muted); margin: 8px 0 16px; font-size: 15px; }
    .sp-done .sp-pill { margin-top: 12px; }
    .sp-powered { font-size: 11px; color: var(--muted); text-align: center; padding: 18px 16px 24px; }
    .sp-preview-notice {
        position: fixed; z-index: 50; top: 10px; left: 50%; transform: translateX(-50%); width: max-content; max-width: calc(100% - 20px);
        background: rgba(17, 17, 17, .85); color: #fff; font-size: 12px; padding: 8px 14px; border-radius: 999px; backdrop-filter: blur(8px);
    }

    /* tabs + panes */
    .sp-tabs { display: none; }
    .sp-js .sp-tabs { display: flex; }
    .sp-tab {
        border: 0; background: transparent; font: inherit; font-size: 14px; font-weight: 600; color: var(--muted);
        padding: 10px 16px; border-radius: 999px; display: flex; align-items: center; gap: 8px; cursor: pointer; white-space: nowrap;
        transition: background .2s, color .2s; -webkit-tap-highlight-color: transparent;
    }
    .sp-tab[aria-selected=true] { background: var(--tab-on-bg); color: var(--tab-on-ink); box-shadow: 0 4px 14px -6px rgba(0, 0, 0, .25); }
    .sp-tab .sp-icon { width: 17px; height: 17px; }
    .sp-pane { margin-bottom: 28px; }
    .sp-js [data-pane]:not(.is-active) { display: none !important; }
    .sp-pane__head { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
    .sp-pane__kicker { font-size: 11px; font-weight: 700; letter-spacing: .2em; text-transform: uppercase; color: var(--accent-deep); margin-bottom: 4px; }
    .sp-pane__title { font-family: var(--display); font-size: 28px; font-weight: var(--title-weight); letter-spacing: -.03em; line-height: 1.05; }
    .sp-rail-ctl { display: none; gap: 8px; }
    .sp-rail-ctl button {
        width: 40px; height: 40px; border-radius: 50%; border: 0; background: var(--paper); color: var(--ink);
        display: grid; place-items: center; cursor: pointer; box-shadow: var(--shadow);
    }

    .sp-grid { display: grid; gap: 14px; }
    .sp-card {
        background: var(--paper); border-radius: var(--r-card); padding: 24px; box-shadow: var(--shadow);
        position: relative; overflow: hidden; text-decoration: none; color: inherit;
    }
    .sp-card__kicker { font-size: 11px; font-weight: 700; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); margin-bottom: 14px; }
    .sp-card__title { display: flex; align-items: center; gap: 10px; font-size: 17px; font-weight: 650; margin-bottom: 14px; }
    .sp-card__icon { width: 44px; height: 44px; border-radius: 14px; display: grid; place-items: center; background: var(--accent); color: var(--accent-ink); flex: none; }
    .sp-chip { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; padding: 5px 10px; border-radius: 999px; background: rgba(0, 0, 0, .06); letter-spacing: .02em; }
    .sp-pill {
        display: inline-flex; align-items: center; gap: 8px; padding: 12px 18px; border-radius: 999px; font-weight: 650; font-size: 14px;
        background: var(--paper); color: var(--ink); text-decoration: none; width: max-content;
    }
    .sp-pill--ghost { background: var(--field-bg); }

    .sp-card--note { background: linear-gradient(165deg, var(--paper) 30%, var(--paper-2)); display: flex; flex-direction: column; }
    .sp-note { font-family: var(--display); font-size: 19px; line-height: 1.5; font-weight: 400; letter-spacing: -.01em; flex: 1; }
    .sp-signoff { display: flex; align-items: center; gap: 12px; margin-top: 20px; }
    .sp-signoff__avatar { width: 42px; height: 42px; border-radius: 50%; display: grid; place-items: center; background: var(--accent); color: var(--accent-ink); font-weight: 800; }
    .sp-signoff strong { display: block; font-size: 14px; }
    .sp-signoff small { color: var(--muted); font-size: 12px; }
    .sp-card--review { background: #17171b; color: #fff; display: flex; flex-direction: column; gap: 14px; min-height: 230px; }
    .sp-card--review .sp-stars { margin-top: auto; }
    .sp-card--review::before {
        content: ""; position: absolute; width: 260px; height: 260px; right: -90px; top: -110px; border-radius: 50%;
        background: radial-gradient(circle, var(--accent) 0%, transparent 70%); opacity: .55;
    }
    .sp-card--review h3 { font-family: var(--display); font-size: 22px; font-weight: 400; line-height: 1.2; letter-spacing: -.02em; position: relative; }
    .sp-card--review small { color: rgba(255, 255, 255, .55); font-size: 12px; }
    .sp-stars { display: flex; gap: 3px; color: var(--accent); position: relative; }
    .sp-card--teaser { display: flex; align-items: center; gap: 14px; padding: 16px 18px; grid-column: 1 / -1; background: var(--accent-soft); box-shadow: none; }
    .sp-card--teaser small { display: block; font-size: 11px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: var(--accent-deep); }
    .sp-card--teaser strong { font-size: 15px; }
    .sp-teaser__go { margin-left: auto; }

    .sp-rules { list-style: none; margin: 0; padding: 0; counter-reset: rule; display: grid; gap: 4px; }
    .sp-rules li { counter-increment: rule; display: flex; gap: 12px; padding: 10px 0; font-size: 15px; }
    .sp-rules li::before {
        content: counter(rule, decimal-leading-zero); flex: none; font-size: 11px; font-weight: 800; width: 30px; height: 30px; border-radius: 10px;
        display: grid; place-items: center; background: var(--accent-soft); color: var(--accent-deep);
    }
    .sp-calls { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
    .sp-calls li { display: flex; align-items: center; justify-content: space-between; gap: 12px; background: var(--paper-2); border-radius: 18px; padding: 12px 12px 12px 16px; }
    .sp-calls strong { display: block; font-size: 14px; }
    .sp-calls small { color: var(--muted); font-size: 13px; }
    .sp-call { width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; background: var(--ink); color: #fff !important; flex: none; }
    .sp-card--emergency { background: var(--paper); }

    .sp-rail {
        display: flex; gap: 14px; overflow-x: auto; scroll-snap-type: x mandatory; scroll-padding: 0 2px;
        margin: -14px -16px; padding: 14px 16px 22px; scrollbar-width: none; -webkit-overflow-scrolling: touch; outline: none;
    }
    .sp-rail::-webkit-scrollbar { display: none; }
    .sp-rail > .sp-card { flex: 0 0 min(80%, 300px); scroll-snap-align: start; }
    .sp-card--place { padding: 0; display: flex; flex-direction: column; }
    .sp-place__art {
        height: 130px; display: flex; align-items: flex-end; justify-content: space-between; padding: 16px; color: #fff;
        background: linear-gradient(140deg, var(--cat, #5b6cff), var(--cat2, #1f2340));
    }
    .sp-place__art .sp-chip { background: rgba(255, 255, 255, .22); color: #fff; backdrop-filter: blur(6px); }
    .sp-cat--food { --cat: #ff7a45; --cat2: #a8321b; }
    .sp-cat--drink { --cat: #c86bfa; --cat2: #4a1d6e; }
    .sp-cat--sights { --cat: #3fb5ff; --cat2: #12406b; }
    .sp-cat--shop { --cat: #ff5f8f; --cat2: #7a1638; }
    .sp-cat--nature { --cat: #4cc38a; --cat2: #14543a; }
    .sp-cat--transport { --cat: #8a95a8; --cat2: #2b3240; }
    .sp-cat--health { --cat: #ff6b6b; --cat2: #7d1f1f; }
    .sp-cat--other { --cat: var(--accent); --cat2: var(--accent-deep); }
    .sp-place__body { padding: 18px; display: flex; flex-direction: column; gap: 6px; flex: 1; }
    .sp-place__body h3 { font-size: 17px; font-weight: 650; letter-spacing: -.01em; }
    .sp-place__body p { color: var(--muted); font-size: 14px; }
    .sp-place__foot { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: auto; padding-top: 10px; font-size: 13px; font-weight: 650; }
    .sp-place__foot a { display: inline-flex; align-items: center; gap: 4px; text-decoration: none; }
    .sp-meta { display: inline-flex; align-items: center; gap: 4px; color: var(--muted); font-weight: 500; }
    .sp-card--offer { background: var(--accent); color: var(--accent-ink); display: flex; flex-direction: column; gap: 10px; min-height: 220px; box-shadow: none; }
    .sp-card--offer .sp-card__icon { background: rgba(0, 0, 0, .1); color: inherit; }
    .sp-card--offer .sp-chip { position: absolute; top: 24px; right: 24px; background: rgba(255, 255, 255, .35); color: inherit; }
    .sp-card--offer h3 { font-family: var(--display); font-size: 24px; font-weight: 500; letter-spacing: -.025em; line-height: 1.1; margin-top: auto; }
    .sp-card--offer p { font-size: 14px; opacity: .8; }
    .sp-card--optin { background: var(--paper-2); display: flex; flex-direction: column; gap: 8px; box-shadow: none; }
    .sp-card--optin strong { font-size: 18px; letter-spacing: -.01em; }
    .sp-card--optin > span:not(.sp-pill) { color: var(--muted); font-size: 14px; }
    .sp-card--optin .sp-pill { margin-top: auto; background: var(--ink); color: #fff; }
    .sp-card--stay {
        min-height: 260px; display: flex; flex-direction: column; justify-content: flex-end; color: #fff; padding: 22px;
        background: #2b2926 center / cover no-repeat;
    }
    .sp-card--stay::before { content: ""; position: absolute; inset: 0; background: linear-gradient(to top, rgba(0, 0, 0, .78), rgba(0, 0, 0, .05) 65%); }
    .sp-card--stay-plain { background-image: linear-gradient(150deg, var(--accent-deep), #18181b); }
    .sp-stay__body { position: relative; display: flex; flex-direction: column; gap: 4px; align-items: flex-start; }
    .sp-stay__body strong { font-family: var(--display); font-size: 24px; font-weight: 400; letter-spacing: -.03em; line-height: 1.05; }
    .sp-stay__body small { font-size: 13px; opacity: .8; }
    .sp-stay__body .sp-chip { background: var(--accent); color: var(--accent-ink); margin-bottom: 6px; }
    .sp-stay__go {
        position: absolute; top: 16px; right: 16px; width: 40px; height: 40px; border-radius: 50%; display: grid; place-items: center;
        background: rgba(255, 255, 255, .2); backdrop-filter: blur(8px);
    }

    /* ---------- phones & tablets: one screen per tab ---------- */
    @media (max-width: 1023.98px) {
        .sp-side { position: relative; }
        .sp-photo { position: absolute; left: 0; right: 0; top: 0; height: 52vh; }
        .sp-photo::after { content: ""; position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(0, 0, 0, .45), rgba(0, 0, 0, .1) 40%, rgba(0, 0, 0, .55)); }
        .sp-brand { position: relative; z-index: 2; padding: 20px 20px 0; color: #fff; }
        .sp-hero { position: relative; z-index: 2; color: #fff; padding: 0 20px 70px; min-height: calc(52vh - 58px); display: flex; flex-direction: column; justify-content: flex-end; }
        .sp-connect { margin: -46px 12px 0; z-index: 3; }
        .sp-js .sp-app { padding-bottom: 96px; }
        .sp-board { position: static; }
        .sp-tabs {
            position: fixed; z-index: 40; left: 10px; right: 10px; bottom: max(10px, env(safe-area-inset-bottom));
            justify-content: space-around; padding: 6px; border-radius: 24px; gap: 2px;
            background: rgba(20, 20, 23, .88); -webkit-backdrop-filter: blur(16px); backdrop-filter: blur(16px);
            box-shadow: 0 18px 40px -14px rgba(0, 0, 0, .5);
        }
        .sp-tab { flex: 1 1 0; min-width: 0; flex-direction: column; gap: 3px; font-size: 10.5px; padding: 8px 2px; color: rgba(255, 255, 255, .62); border-radius: 18px; }
        .sp-tab .sp-icon { width: 21px; height: 21px; }
        .sp-tab[aria-selected=true] { background: var(--accent); color: var(--accent-ink); box-shadow: none; }
        .sp-panes { padding: 0 16px; }
        .sp-pane { padding-top: 26px; }
        .sp-js .sp-pane { min-height: calc(100vh - 120px); }
        .sp-pane__title { font-size: 32px; }
        /* Swipeable cards are tall on phones so each tab fills the screen. */
        .sp-rail > .sp-card { min-height: min(58vh, 460px); }
        .sp-card--place .sp-place__art { flex: 1; min-height: 130px; }
        .sp-card--place .sp-place__body { flex: none; }
    }

    /* ---------- wide screens: everything fits one viewport ---------- */
    @media (min-width: 1024px) {
        .sp-app { height: 100vh; height: 100dvh; overflow: hidden; display: grid; position: relative; }
        .sp-side { display: contents; }
        .sp-photo, .sp-brand, .sp-hero, .sp-connect, .sp-board { min-height: 0; min-width: 0; }
        .sp-connect { overflow: auto; scrollbar-width: thin; }
        .sp-board { display: flex; flex-direction: column; z-index: 3; }
        .sp-tab--wifi { display: none !important; }
        .sp-tabs { align-self: flex-start; gap: 4px; padding: 5px; border-radius: 999px; background: var(--tabs-bg); max-width: 100%; overflow-x: auto; scrollbar-width: none; }
        .sp-panes { flex: 1; min-height: 0; margin-top: 16px; position: relative; overflow: auto; }
        .sp-js .sp-panes { overflow: visible; }
        .sp-js .sp-pane { height: 100%; display: flex; flex-direction: column; margin: 0; }
        .sp-rail-ctl { display: flex; }
        .sp-grid { flex: 1; min-height: 0; grid-template-columns: 1.35fr 1fr; grid-auto-rows: minmax(0, 1fr); }
        .sp-grid--welcome:has(.sp-card--teaser) { grid-template-rows: minmax(0, 1fr) auto; grid-auto-rows: auto; }
        .sp-grid--one { grid-template-columns: 1fr; }
        .sp-grid > .sp-card { overflow: auto; scrollbar-width: thin; }
        .sp-card--rules .sp-rules { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); column-gap: 18px; }
        .sp-rail { flex: 1; min-height: 0; }
        .sp-rail > .sp-card { flex-basis: clamp(240px, 30%, 300px); }
        .sp-powered { position: absolute; right: 22px; bottom: 6px; padding: 0; z-index: 4; }
        .sp-title { font-size: clamp(44px, 5vw, 80px); }
    }

    /* ======================= 1. BENTO (default) =======================
       Photo hero with a huge light headline; a frosted tray of big cards rises over it. */
    @media (min-width: 1024px) {
        .splash--bento .sp-app {
            grid-template-columns: minmax(24px, 1fr) minmax(330px, 380px) minmax(0, 880px) minmax(24px, 1fr);
            grid-template-rows: 44vh minmax(0, 1fr) 30px;
        }
        .splash--bento .sp-photo { grid-area: 1 / 1 / 2 / 5; margin-bottom: -16vh; position: relative; }
        .splash--bento .sp-photo::after { content: ""; position: absolute; inset: 0; background: radial-gradient(70% 70% at 50% 45%, rgba(0, 0, 0, .42), rgba(0, 0, 0, .18)), linear-gradient(to bottom, rgba(0, 0, 0, .35), transparent 40%); }
        .splash--bento .sp-brand { grid-area: 1 / 2 / 2 / 4; align-self: start; margin-top: 22px; color: #fff; z-index: 2; }
        .splash--bento .sp-hero { grid-area: 1 / 2 / 2 / 4; z-index: 2; color: #fff; text-align: center; align-self: center; padding: 40px 0 10vh; }
        .splash--bento .sp-lede { margin-inline: auto; }
        .splash--bento .sp-app::before {
            content: ""; grid-area: 2 / 2 / 3 / 4; margin: -9vh -16px 0; border-radius: 40px; z-index: 1;
            background: rgba(250, 248, 244, .8); -webkit-backdrop-filter: blur(22px); backdrop-filter: blur(22px);
            box-shadow: 0 40px 90px -40px rgba(30, 20, 10, .45);
        }
        .splash--bento .sp-connect { grid-area: 2 / 2 / 3 / 3; margin-top: calc(-9vh + 16px); z-index: 3; }
        .splash--bento .sp-board { grid-area: 2 / 3 / 3 / 4; margin: calc(-9vh + 16px) 0 0 16px; }
        .splash--bento.sp--solo .sp-connect { grid-column: 2 / 4; justify-self: center; width: min(440px, 100%); margin-bottom: 16px; }
        .splash--bento.sp--solo .sp-app::before { display: none; }
    }

    /* ======================= 2. HORIZON =======================
       A tall white panel floats on a full-bleed landscape; cards drift along the bottom. */
    .splash--horizon { --r-card: 16px; --r-field: 0; --r-btn: 8px; --title-weight: 650; --title-track: -.03em; --field-bg: transparent; --paper-2: #f5f5f3; }
    .splash--horizon .sp-photo { position: fixed; inset: 0; height: auto; z-index: -1; }
    .splash--horizon .sp-photo::after { content: ""; position: absolute; inset: 0; background: linear-gradient(to top, rgba(0, 0, 0, .45), transparent 55%); }
    .splash--horizon .sp-eyebrow { display: inline-block; background: #f0efec; color: var(--ink); opacity: 1; padding: 6px 10px; border-radius: 4px; letter-spacing: .08em; font-size: 10px; }
    .splash--horizon .sp-title { color: var(--ink); }
    .splash--horizon .sp-hl { color: inherit; background: linear-gradient(transparent 62%, var(--accent) 62%, var(--accent) 88%, transparent 88%); }
    .splash--horizon .sp-lede { color: var(--muted); opacity: 1; font-size: 15px; }
    .splash--horizon input[type=text], .splash--horizon input[type=email], .splash--horizon .sp-tel, .splash--horizon select { box-shadow: inset 0 -1.5px 0 #d6d4cf; padding-left: 2px; }
    .splash--horizon input:focus, .splash--horizon select:focus { box-shadow: inset 0 -2px 0 var(--ink) !important; }
    .splash--horizon .sp-tel input[type=tel] { box-shadow: none !important; }
    .splash--horizon .sp-tel-prefix { padding-left: 2px; }
    .splash--horizon .sp-connect { box-shadow: none; }
    .splash--horizon .sp-card { background: rgba(255, 255, 255, .9); -webkit-backdrop-filter: blur(16px); backdrop-filter: blur(16px); }
    .splash--horizon .sp-card--review { background: rgba(17, 17, 20, .82); }
    .splash--horizon .sp-card--offer { background: var(--accent); }
    .splash--horizon .sp-card--stay { background-color: #2b2926; }
    .splash--horizon .sp-pane__title, .splash--horizon .sp-pane__kicker { color: #fff; text-shadow: 0 2px 14px rgba(0, 0, 0, .35); }
    @media (max-width: 1023.98px) {
        .splash--horizon .sp-side { margin: 30vh 10px 0; background: #fff; border-radius: 14px; padding-bottom: 4px; }
        .splash--horizon .sp-brand { color: var(--ink); padding: 24px 22px 0; }
        .splash--horizon .sp-hero { color: var(--ink); min-height: 0; padding: 22px 22px 4px; }
        .splash--horizon .sp-connect { margin: 0; padding: 18px 22px 22px; background: transparent; }
        .splash--horizon .sp-pane__title, .splash--horizon .sp-pane__kicker { color: #fff; }
        .splash--horizon .sp-powered { color: rgba(255, 255, 255, .8); position: relative; }
    }
    @media (min-width: 1024px) {
        .splash--horizon .sp-app { grid-template-columns: minmax(380px, 430px) minmax(0, 1fr); grid-template-rows: auto auto minmax(0, 1fr); padding: 26px; column-gap: 26px; }
        .splash--horizon .sp-brand { grid-area: 1 / 1; background: #fff; border-radius: 12px 12px 0 0; padding: 36px 40px 0; z-index: 1; }
        .splash--horizon .sp-hero { grid-area: 2 / 1; background: #fff; padding: 56px 40px 8px; z-index: 1; }
        .splash--horizon .sp-title { font-size: clamp(34px, 3vw, 46px); line-height: 1.05; }
        .splash--horizon .sp-connect { grid-area: 3 / 1; border-radius: 0 0 12px 12px; padding: 18px 40px 30px; z-index: 1; }
        .splash--horizon .sp-board { grid-area: 1 / 2 / 4 / 3; align-self: end; height: min(66vh, 560px); }
        .splash--horizon .sp-tabs { background: rgba(255, 255, 255, .28); -webkit-backdrop-filter: blur(14px); backdrop-filter: blur(14px); }
        .splash--horizon .sp-tab:not([aria-selected=true]) { color: #fff; }
        .splash--horizon .sp-tab[aria-selected=true] { color: var(--ink); background: #fff; }
        .splash--horizon .sp-rail-ctl button { background: rgba(255, 255, 255, .9); }
        .splash--horizon .sp-powered { color: rgba(255, 255, 255, .85); }
    }

    /* ======================= 3. CLOVER =======================
       Editorial split: white column with a coloured headline; photo right; an accent
       block of tabs beside a sliding strip of stories. */
    .splash--clover {
        --canvas: #fff; --r-card: 6px; --r-field: 4px; --r-btn: 2px; --title-weight: 600; --title-track: -.025em;
        --sans: "Avenir Next", Avenir, "Segoe UI", Nunito, Roboto, sans-serif; --display: var(--sans);
        --btn-bg: transparent; --btn-ink: var(--accent-deep); --field-bg: #f4f4f7; --paper-2: #f4f4f7;
    }
    .splash--clover .sp-title { color: var(--accent-deep); line-height: 1.02; }
    .splash--clover .sp-hl { color: inherit; }
    .splash--clover .sp-eyebrow { text-transform: none; letter-spacing: 0; font-size: 18px; font-weight: 500; opacity: .7; margin-bottom: 6px; }
    .splash--clover .sp-lede { font-size: 17px; }
    .splash--clover .sp-lede::first-line { font-weight: 700; font-style: italic; }
    .splash--clover .sp-submit { box-shadow: inset 0 0 0 2px var(--accent-deep); text-transform: uppercase; letter-spacing: .14em; font-size: 13px; }
    .splash--clover .sp-submit:hover:not(:disabled) { background: var(--accent-deep); color: #fff; }
    .splash--clover .sp-connect { box-shadow: none; }
    .splash--clover .sp-badge-icon, .splash--clover .sp-brand__mono { border-radius: 4px; }
    .splash--clover .sp-card { box-shadow: 0 14px 34px -20px rgba(20, 20, 60, .35); }
    .splash--clover .sp-pane__kicker { color: var(--accent-deep); }
    @media (max-width: 1023.98px) {
        .splash--clover .sp-photo { height: 32vh; }
        .splash--clover .sp-photo::after { background: linear-gradient(to bottom, rgba(0, 0, 0, .35), transparent 50%); }
        .splash--clover .sp-hero { color: var(--ink); min-height: 0; padding: calc(32vh - 30px) 22px 0; }
        .splash--clover .sp-connect { margin: 8px 6px 0; }
        .splash--clover .sp-tabs { border-radius: 6px; }
        .splash--clover .sp-tab { border-radius: 4px; }
    }
    @media (min-width: 1024px) {
        .splash--clover .sp-app { grid-template-columns: minmax(380px, 36%) minmax(0, 1fr); grid-template-rows: auto auto minmax(0, 1fr) minmax(250px, 40vh); }
        .splash--clover .sp-brand { grid-area: 1 / 1; padding: 38px 56px 0; }
        .splash--clover .sp-hero { grid-area: 2 / 1; padding: 4vh 56px 6px; }
        .splash--clover .sp-title { font-size: clamp(36px, 3.3vw, 54px); }
        .splash--clover .sp-lede { font-size: 15px; margin-top: 10px; }
        .splash--clover .sp-connect { grid-area: 3 / 1 / 5 / 2; border-radius: 0; padding: 14px 56px 40px; }
        .splash--clover .sp-photo { grid-area: 1 / 2 / 5 / 3; }
        .splash--clover .sp-board {
            grid-area: 4 / 2 / 5 / 3; display: grid; grid-template-columns: 230px minmax(0, 1fr); color: #fff;
            background: linear-gradient(to top, rgba(18, 20, 44, .94), rgba(18, 20, 44, .6)); -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px);
        }
        .splash--clover .sp-board::before { content: ""; position: absolute; left: 0; right: 0; bottom: 100%; height: 30vh; background: linear-gradient(to top, rgba(18, 20, 44, .6), transparent); pointer-events: none; }
        .splash--clover .sp-tabs { flex-direction: column; justify-content: center; align-self: stretch; border-radius: 0; background: var(--accent); padding: 22px; gap: 2px; overflow: auto; }
        .splash--clover .sp-tab { color: var(--accent-ink); opacity: .7; border-radius: 4px; font-size: 16px; font-weight: 600; padding: 10px 14px; }
        .splash--clover .sp-tab[aria-selected=true] { opacity: 1; background: rgba(255, 255, 255, .22); color: var(--accent-ink); box-shadow: none; }
        .splash--clover .sp-panes { margin: 0; padding: 24px 30px 22px; }
        .splash--clover .sp-pane__title { color: #fff; font-size: 24px; }
        .splash--clover .sp-pane__kicker { color: rgba(255, 255, 255, .6); }
        .splash--clover .sp-rail-ctl button { background: rgba(255, 255, 255, .14); color: #fff; box-shadow: none; }
        .splash--clover .sp-board .sp-card { box-shadow: none; }
        .splash--clover .sp-board .sp-card:not(.sp-card--offer):not(.sp-card--stay):not(.sp-card--review) { background: rgba(255, 255, 255, .08); color: #fff; }
        .splash--clover .sp-board .sp-card--place .sp-place__body p, .splash--clover .sp-board .sp-meta,
        .splash--clover .sp-board .sp-calls small, .splash--clover .sp-board .sp-signoff small, .splash--clover .sp-board .sp-card__kicker { color: rgba(255, 255, 255, .62); }
        .splash--clover .sp-board .sp-calls li, .splash--clover .sp-board .sp-card--optin { background: rgba(255, 255, 255, .07); }
        .splash--clover .sp-board .sp-call { background: var(--accent); color: var(--accent-ink) !important; }
        .splash--clover .sp-board .sp-card--teaser { background: rgba(255, 255, 255, .12); }
        .splash--clover .sp-board .sp-card--teaser small { color: var(--accent); }
        .splash--clover .sp-board .sp-rules li::before { background: rgba(255, 255, 255, .12); color: #fff; }
        .splash--clover .sp-board .sp-card--place .sp-place__art { height: 96px; }
        .splash--clover .sp-board .sp-card--stay, .splash--clover .sp-board .sp-card--offer, .splash--clover .sp-board .sp-card--review { min-height: 0; }
        .splash--clover .sp-board .sp-note { font-size: 16px; }
        .splash--clover .sp-powered { color: rgba(255, 255, 255, .7); }
        .splash--clover.sp--solo .sp-powered { color: #fff; }
    }

    /* ======================= 4. MONOGRAM =======================
       Monochrome stationery: bold black type, a giant outlined initial, dotted
       accents and a dark footer band that carries the tabs. */
    .splash--monogram {
        --canvas: #e7e7e7; --paper-2: #f4f4f4; --field-bg: #f2f2f2; --r-card: 10px; --r-field: 8px; --r-btn: 8px;
        --title-weight: 800; --title-track: -.035em; --accent-soft: #f1f1f1; --accent-deep: #111;
        --tab-on-bg: #fff; --tab-on-ink: #111;
    }
    .splash--monogram .sp-hl { color: inherit; }
    .splash--monogram .sp-card { box-shadow: none; background: var(--paper-2); }
    .splash--monogram .sp-card--note { background: var(--paper-2); }
    .splash--monogram .sp-card--review { background: #151515; }
    .splash--monogram .sp-card--offer { background: #151515; color: #fff; }
    .splash--monogram .sp-card--offer .sp-card__icon { background: var(--accent); color: var(--accent-ink); }
    .splash--monogram .sp-card--offer .sp-chip { background: rgba(255, 255, 255, .14); }
    .splash--monogram .sp-cat--food, .splash--monogram .sp-cat--drink, .splash--monogram .sp-cat--sights, .splash--monogram .sp-cat--shop,
    .splash--monogram .sp-cat--nature, .splash--monogram .sp-cat--transport, .splash--monogram .sp-cat--health, .splash--monogram .sp-cat--other { --cat: #3a3a3a; --cat2: #0e0e0e; }
    .splash--monogram .sp-place__art .sp-icon { color: var(--accent); }
    .splash--monogram .sp-badge-icon, .splash--monogram .sp-brand__mono, .splash--monogram .sp-done__icon, .splash--monogram .sp-signoff__avatar { background: #111; color: #fff; }
    .splash--monogram .sp-eyebrow { letter-spacing: .32em; font-size: 10px; color: var(--muted); opacity: 1; }
    .splash--monogram .sp-lede { color: var(--muted); font-size: 14px; }
    .splash--monogram .sp-mark {
        display: block; position: absolute; pointer-events: none; z-index: 0; font-family: Georgia, "Times New Roman", serif; font-weight: 700;
        color: transparent; -webkit-text-stroke: 2px rgba(0, 0, 0, .07); line-height: .8; user-select: none;
    }
    .splash--monogram::before, .splash--monogram::after {
        content: ""; position: fixed; z-index: 0; pointer-events: none; width: 240px; height: 200px;
        background-image: radial-gradient(rgba(0, 0, 0, .35) 1.5px, transparent 1.8px); background-size: 22px 22px;
    }
    .splash--monogram::before { top: 0; right: 4%; }
    .splash--monogram::after { bottom: 0; left: 3%; }
    @media (max-width: 1023.98px) {
        .splash--monogram { --canvas: #fff; }
        .splash--monogram .sp-photo { display: none; }
        .splash--monogram .sp-mark { font-size: 70vw; top: -2vw; right: -8vw; }
        .splash--monogram .sp-brand, .splash--monogram .sp-hero { color: var(--ink); }
        .splash--monogram .sp-hero { min-height: 0; padding: 18vh 22px 26px; }
        .splash--monogram .sp-connect { margin: 0 12px; box-shadow: 0 30px 60px -30px rgba(0, 0, 0, .3); }
        .splash--monogram .sp-tabs { background: #141414; border-radius: 14px; }
        .splash--monogram .sp-tab { border-radius: 10px; }
        .splash--monogram .sp-tab[aria-selected=true] { background: #fff; color: #111; }
    }
    @media (min-width: 1024px) {
        body.splash--monogram { background: var(--canvas) radial-gradient(60vw 60vw at 100% 110%, #d9d9d9 0 49.6%, transparent 50%) no-repeat; }
        .splash--monogram .sp-app {
            width: min(1240px, calc(100% - 64px)); height: calc(100vh - 64px); height: calc(100dvh - 64px); margin: 32px auto; z-index: 1;
            background: #fff; border-radius: 14px; box-shadow: 0 40px 90px -40px rgba(0, 0, 0, .35);
            grid-template-columns: minmax(360px, 410px) minmax(0, 1fr); grid-template-rows: auto auto minmax(0, 1fr) 96px;
        }
        .splash--monogram .sp-mark { grid-area: 1 / 1 / 4 / 2; position: relative; justify-self: end; align-self: end; font-size: min(52vh, 440px); margin: 0 -4% -6vh 0; -webkit-text-stroke-color: rgba(0, 0, 0, .05); }
        .splash--monogram .sp-photo {
            grid-area: 4 / 1 / 5 / 3; border-radius: 0 0 14px 14px; position: relative; filter: grayscale(1);
        }
        .splash--monogram .sp-photo::after { content: ""; position: absolute; inset: 0; border-radius: inherit; background: rgba(22, 22, 22, .88); }
        .splash--monogram .sp-brand { grid-area: 4 / 1; z-index: 2; color: #fff; padding: 0 44px; text-transform: uppercase; letter-spacing: .14em; font-family: Georgia, serif; font-weight: 400; font-size: 14px; }
        .splash--monogram .sp-brand__logo { filter: brightness(0) invert(1); max-height: 46px; }
        .splash--monogram .sp-brand__mono { background: transparent; box-shadow: inset 0 0 0 1.5px #fff; color: #fff; border-radius: 50%; font-family: Georgia, serif; }
        .splash--monogram .sp-hero { grid-area: 1 / 1 / 3 / 2; padding: 9vh 44px 10px; z-index: 1; }
        .splash--monogram .sp-title { font-size: clamp(36px, 3.4vw, 54px); line-height: 1.02; }
        .splash--monogram .sp-connect { grid-area: 3 / 1; box-shadow: none; padding: 10px 44px 26px; background: transparent; z-index: 1; }
        .splash--monogram .sp-board { display: contents; }
        .splash--monogram .sp-tabs { grid-area: 4 / 2; z-index: 2; align-self: center; justify-self: end; margin-right: 34px; background: rgba(255, 255, 255, .08); }
        .splash--monogram .sp-tab:not([aria-selected=true]) { color: rgba(255, 255, 255, .7); }
        .splash--monogram .sp-tab { text-transform: uppercase; letter-spacing: .12em; font-size: 11px; }
        .splash--monogram .sp-panes { grid-area: 1 / 2 / 4 / 3; margin: 0; padding: 34px 34px 24px 10px; z-index: 1; }
        .splash--monogram .sp-powered { color: rgba(255, 255, 255, .5); right: 34px; bottom: 6px; }
        .splash--monogram.sp--solo .sp-photo, .splash--monogram.sp--solo .sp-brand { grid-column: 1 / 3; }
    }

    /* ======================= 5. NOCTURNE =======================
       After-dark lounge: a dimmed photo, frosted glass cards and a glowing accent. */
    .splash--nocturne {
        --canvas: #0b0c10; --ink: #f4f2ee; --muted: rgba(244, 242, 238, .62); --paper: rgba(255, 255, 255, .07); --paper-2: rgba(255, 255, 255, .05);
        --field-bg: rgba(255, 255, 255, .08); --btn-bg: var(--accent); --btn-ink: var(--accent-ink); --danger: #ff8a80;
        --tabs-bg: rgba(255, 255, 255, .07); --tab-on-bg: var(--accent); --tab-on-ink: var(--accent-ink);
        --display: "Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif; --title-weight: 400; --title-track: -.02em;
        --shadow: inset 0 1px 0 rgba(255, 255, 255, .08), 0 30px 60px -30px rgba(0, 0, 0, .7);
        --accent-soft: rgba(255, 255, 255, .08);
    }
    @supports (color: color-mix(in srgb, red, blue)) {
        .splash--nocturne { --accent-deep: color-mix(in srgb, var(--accent) 70%, #fff); }
    }
    .splash--nocturne .sp-photo { position: fixed; inset: 0; height: auto; z-index: 0; filter: brightness(.42) saturate(1.15); transform: scale(1.04); }
    .splash--nocturne .sp-photo::after { display: block; content: ""; position: absolute; inset: 0; background: radial-gradient(80% 60% at 20% 20%, transparent, rgba(5, 6, 9, .75)); }
    .splash--nocturne .sp-app::after {
        content: ""; position: fixed; width: 50vmax; height: 50vmax; right: -18vmax; top: -22vmax; border-radius: 50%; z-index: 0; pointer-events: none;
        background: radial-gradient(circle, var(--accent) 0%, transparent 65%); opacity: .28; filter: blur(30px);
    }
    .splash--nocturne .sp-connect, .splash--nocturne .sp-card, .splash--nocturne .sp-tabs, .splash--nocturne .sp-rail-ctl button {
        -webkit-backdrop-filter: blur(22px) saturate(1.2); backdrop-filter: blur(22px) saturate(1.2);
    }
    .splash--nocturne .sp-hl { color: var(--accent); font-style: italic; text-shadow: 0 0 40px var(--accent); }
    .splash--nocturne .sp-eyebrow { color: var(--accent); opacity: 1; }
    .splash--nocturne input { color: var(--ink); }
    .splash--nocturne .sp-check input { accent-color: var(--accent); }
    .splash--nocturne input:focus { box-shadow: inset 0 0 0 2px var(--accent); }
    .splash--nocturne select { color: var(--ink); }
    .splash--nocturne select option { color: #111; }
    .splash--nocturne .sp-error { background: rgba(255, 90, 80, .14); }
    .splash--nocturne .sp-card--review { background: var(--accent); color: var(--accent-ink); }
    .splash--nocturne .sp-card--review::before { background: radial-gradient(circle, #fff 0%, transparent 70%); opacity: .35; }
    .splash--nocturne .sp-card--review .sp-stars { color: var(--accent-ink); }
    .splash--nocturne .sp-card--review small { color: inherit; opacity: .6; }
    .splash--nocturne .sp-card--review .sp-pill { background: var(--accent-ink); color: var(--accent); }
    .splash--nocturne .sp-call { background: var(--accent); color: var(--accent-ink) !important; }
    .splash--nocturne .sp-card--optin .sp-pill { background: var(--accent); color: var(--accent-ink); }
    .splash--nocturne .sp-chip { background: rgba(255, 255, 255, .1); }
    .splash--nocturne .sp-card--offer .sp-chip { background: rgba(0, 0, 0, .14); }
    .splash--nocturne .sp-pill--ghost { color: var(--ink); }
    .splash--nocturne .sp-place__art { opacity: .92; }
    @media (max-width: 1023.98px) {
        .splash--nocturne .sp-photo::after { background: linear-gradient(to bottom, rgba(5, 6, 9, .3), rgba(5, 6, 9, .85)); }
        .splash--nocturne .sp-hero { min-height: 42vh; padding-bottom: 28px; }
        .splash--nocturne .sp-connect { margin-top: 0; }
        .splash--nocturne .sp-tabs { background: rgba(255, 255, 255, .1); }
        .splash--nocturne .sp-tab:not([aria-selected=true]) { color: rgba(255, 255, 255, .7); }
    }
    @media (min-width: 1024px) {
        .splash--nocturne .sp-app { grid-template-columns: minmax(0, 1fr) minmax(360px, 410px); grid-template-rows: auto auto minmax(0, 1fr); padding: 30px 40px 34px; column-gap: 36px; }
        .splash--nocturne .sp-brand { grid-area: 1 / 1; z-index: 2; color: var(--ink); }
        .splash--nocturne .sp-hero { grid-area: 2 / 1; z-index: 2; color: var(--ink); padding: 7vh 0 4vh; }
        .splash--nocturne .sp-title { font-size: clamp(46px, 5.4vw, 92px); line-height: .95; }
        .splash--nocturne .sp-connect { grid-area: 1 / 2 / 4 / 3; z-index: 2; padding: 32px; }
        .splash--nocturne .sp-board { grid-area: 3 / 1; }
        .splash--nocturne.sp--solo .sp-hero { grid-row: 2 / 4; align-self: end; }
        .splash--nocturne .sp-tab:not([aria-selected=true]) { color: var(--muted); }
    }

    @media (prefers-reduced-motion: reduce) { .sp * { transition: none !important; scroll-behavior: auto !important; } }
</style>

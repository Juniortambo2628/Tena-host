{{-- Shared, self-contained styles for the captive portal pages (no JS bundle or
     external fonts: pre-login devices can't reach the internet yet). --}}
<style>
    :root { --yellow: #FFD300; --ink: #1E1E1E; --muted: #5B6170; --bg: #F7F7F8; --line: #E5E7EB; --danger: #B91C1C; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background: var(--bg); color: var(--ink); min-height: 100vh;
        display: flex; align-items: center; justify-content: center; padding: 20px;
    }
    .card {
        background: #fff; width: 100%; max-width: 420px; border-radius: 24px;
        box-shadow: 0 10px 40px rgba(30, 30, 30, .10); padding: 30px 24px 22px; text-align: center;
    }
    .logo, .done {
        width: 64px; height: 64px; margin: 0 auto 16px; border-radius: 18px;
        background: var(--yellow); color: var(--ink); font-size: 30px; font-weight: 800;
        display: flex; align-items: center; justify-content: center;
    }
    .done { border-radius: 50%; }
    .logo-img { display: block; max-width: 140px; max-height: 64px; margin: 0 auto 16px; object-fit: contain; }
    h1 { font-size: 22px; margin: 0 0 6px; }
    .sub { color: var(--muted); font-size: 15px; margin: 0 0 20px; line-height: 1.5; }
    form { text-align: left; }
    .field { display: block; margin-bottom: 14px; }
    .field > span:first-child { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
    .field em { font-weight: 400; font-style: normal; color: var(--muted); }
    input[type=text], input[type=email], input[type=tel], select {
        width: 100%; font-size: 16px; padding: 13px 14px; border: 1px solid var(--line);
        border-radius: 12px; background: #fff; color: var(--ink); -webkit-appearance: none;
    }
    input:focus { outline: none; border-color: var(--ink); }
    input[aria-invalid=true] { border-color: var(--danger); }
    .tel { display: flex; border: 1px solid var(--line); border-radius: 12px; overflow: hidden; }
    .tel input { border: 0; border-radius: 0; }
    .tel:has(input[aria-invalid=true]) { border-color: var(--danger); }
    .birthday { display: grid; grid-template-columns: 1fr 2fr; gap: 8px; }
    .tel-prefix { display: flex; align-items: center; padding: 0 12px; background: var(--bg); font-weight: 600; color: var(--muted); border-right: 1px solid var(--line); }
    .check { display: flex; gap: 10px; align-items: flex-start; font-size: 13px; line-height: 1.45; color: var(--muted); margin: 6px 0 12px; }
    .check input { width: 20px; height: 20px; margin: 0; flex: none; accent-color: var(--ink); }
    .check a { color: var(--ink); }
    .field-error { display: block; color: var(--danger); font-size: 12px; margin: 4px 0 8px; }
    .error { background: #FEF2F2; color: var(--danger); border: 1px solid #FECACA; border-radius: 12px; padding: 12px 14px; font-size: 14px; margin: 0 0 16px; text-align: left; }
    button {
        width: 100%; border: 0; border-radius: 14px; background: var(--ink); color: var(--yellow);
        font-size: 17px; font-weight: 700; padding: 15px 20px; margin-top: 6px; cursor: pointer; -webkit-appearance: none;
    }
    button:disabled { opacity: .6; cursor: default; }
    .fine { color: var(--muted); font-size: 12px; margin: 16px 0 0; text-align: center; }
    p { line-height: 1.5; }
    .splash .card { position: relative; overflow: hidden; }
    .splash h1 { overflow-wrap: anywhere; }
    .splash-cover { width: calc(100% + 48px); max-height: 220px; object-fit: cover; display: block; margin: -30px -24px 24px; }
    .splash-eyebrow { text-transform: uppercase; letter-spacing: .12em; font-size: 10px; font-weight: 700; color: var(--muted); margin: 0 0 18px; }
    .splash--classic .card { border-top: 6px solid var(--yellow); }
    .splash--retreat { background: #e9ede6; }
    .splash--retreat .card { max-width: 480px; border-radius: 6px; background: #fffdf7; }
    .splash--retreat h1 { font-family: Georgia, serif; font-size: 30px; font-weight: 400; }
    .splash--retreat .splash-cover { max-height: 260px; }
    .splash--retreat button { border-radius: 4px; }
    .splash--bold { background: #181b22; }
    .splash-backdrop { position: fixed; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: .3; }
    .splash--bold .card { max-width: 460px; border: 3px solid var(--yellow); box-shadow: 12px 12px 0 rgba(0,0,0,.25); }
    .splash--bold h1 { font-size: 32px; letter-spacing: -.04em; }
    .splash--bold .logo { border-radius: 50%; }
    .preview-notice { position: relative; z-index: 1; background: #edf4ff; color: #1e40af; padding: 12px; border-radius: 8px; font-size: 12px; margin: 0 0 36px; }
    @media (max-width: 480px) { .splash { padding: 12px; align-items: flex-start; } .splash .card { margin: 12px auto; } }
</style>

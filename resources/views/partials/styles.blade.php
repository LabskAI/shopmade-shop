{{-- Shop styles. Colors and fonts come from the site's theme tokens (front/partials/styles.blade.php names them); the fallbacks are Shopmade's own. --}}
<style>
.layout-shop main { padding: 0; }
.sm-shop { --ink: var(--color-ink, #201e1d); --paper: var(--color-bg, #f4f2ef); --accent: var(--color-accent, #d9472b); --accent-ink: var(--color-accent, #b23a22); container: page / inline-size; }
.sm-sr { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
.sm-h2 { margin: 0; font-family: var(--font-display, inherit); font-weight: 800; font-size: clamp(36px, 6cqw, 72px); line-height: 0.98; letter-spacing: -0.035em; text-wrap: balance; }
.sm-intro-p { margin: 0; max-width: 60ch; font-size: 19px; line-height: 1.5; opacity: 0.8; text-wrap: pretty; }
.sm-btn { display: inline-flex; align-items: center; justify-content: center; height: 52px; padding: 0 26px; font-size: 17px; font-weight: 600; text-decoration: none; white-space: nowrap; }
.sm-btn-sm { height: 44px; padding: 0 18px; font-size: 15px; }
.sm-shop .sm-btn-line { background: transparent; color: var(--paper); border: 1px solid var(--paper); }
.sm-shop .sm-btn-line:hover { background: var(--paper); color: var(--ink); }

/* Title band (ink) */
.sm-shop-band { padding: 72px 0 56px; background: var(--ink); color: var(--paper); }
.sm-shop-band-row { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px 40px; }
.sm-shop-band-text { display: flex; flex-direction: column; align-items: flex-start; gap: 16px; min-width: 0; }
.sm-shop-crumbs { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: #d9d6d3; }
.sm-shop-crumbs::before { content: ''; width: 10px; height: 10px; background: var(--accent); }
.sm-shop .sm-shop-crumbs a { color: #d9d6d3; text-decoration: none; }
.sm-shop .sm-shop-crumbs a:hover { color: var(--paper); text-decoration: underline; text-underline-offset: 4px; }
.sm-shop-crumbs [aria-current] { color: #b8b3af; max-width: 40ch; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sm-shop .sm-shop-title { font-size: clamp(40px, 5cqw, 72px); overflow-wrap: anywhere; }
.sm-shop .sm-shop-lede { max-width: 56ch; }
.sm-shop-cart-link { flex: 0 0 auto; gap: 10px; }
.sm-shop-cart-count { display: inline-flex; align-items: center; justify-content: center; min-width: 26px; height: 26px; padding: 0 6px; background: var(--accent-ink); color: #fff; font-size: 14px; font-weight: 700; font-variant-numeric: tabular-nums; }


/* Paper surface */
.sm-shop-paper { padding: 56px 0 112px; background: var(--paper); color: var(--ink); }
.sm-shop .sm-shop-paper a { color: var(--ink); }
.sm-shop .sm-shop-paper a:focus-visible,
.sm-shop .sm-shop-paper button:focus-visible,
.sm-shop .sm-shop-paper input:focus-visible,
.sm-shop .sm-shop-paper select:focus-visible,
.sm-shop .sm-shop-paper textarea:focus-visible { outline: 2px solid var(--ink); outline-offset: 3px; }

.sm-shop-h3 { margin: 0; padding: 0; font-family: var(--font-display, inherit); font-weight: 800; font-size: 26px; line-height: 1.1; letter-spacing: -0.02em; }
.sm-shop-body { margin: 0; font-size: 17px; line-height: 1.6; max-width: 60ch; }
.sm-shop-body strong { font-weight: 700; }
.sm-shop-meta { margin: 0; font-size: 15px; color: #5f5a56; }
.sm-shop-num, .sm-shop-price { font-variant-numeric: tabular-nums; }
.sm-shop-price { font-family: var(--font-display, inherit); font-weight: 800; font-size: 22px; letter-spacing: -0.01em; }
.sm-shop-price-lg { margin: 0; font-size: 40px; line-height: 1; }
.sm-shop-tag { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: #5f5a56; }
.sm-shop-tag::before { content: ''; width: 8px; height: 8px; background: var(--accent); }

/* Notices */
.sm-shop-notice { margin: 0 0 32px; padding: 14px 18px; border-left: 4px solid var(--ink); background: #e6e4e1; font-size: 16px; }
.sm-shop-notice p { margin: 0; }
.sm-shop-notice-bad { border-left-color: var(--accent); background: #fbe6e2; }
.sm-shop-notice-title { font-weight: 700; }
.sm-shop-notice-bad .sm-shop-notice-title { color: var(--accent-ink); }

/* Buttons on paper */
.sm-shop .sm-shop-paper .sm-shop-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 52px; padding: 0 26px; border: 1px solid var(--ink); background: var(--ink); color: var(--paper); font: 600 17px/1 var(--font-body, inherit); text-decoration: none; white-space: nowrap; cursor: pointer; transition: background-color .15s cubic-bezier(.2,0,0,1), color .15s cubic-bezier(.2,0,0,1); }
.sm-shop .sm-shop-paper .sm-shop-btn:hover { background: #3a3634; border-color: #3a3634; color: var(--paper); }
.sm-shop .sm-shop-paper .sm-shop-btn:active { transform: translateY(1px); }
.sm-shop .sm-shop-paper .sm-shop-btn-line { background: transparent; color: var(--ink); }
.sm-shop .sm-shop-paper .sm-shop-btn-line:hover { background: var(--ink); border-color: var(--ink); color: var(--paper); }
.sm-shop .sm-shop-paper .sm-shop-btn-sm { min-height: 44px; padding: 0 16px; font-size: 15px; }
.sm-shop .sm-shop-paper .sm-shop-btn-block { width: 100%; }
.sm-shop .sm-shop-link-btn { min-height: 44px; padding: 0 4px; border: 0; background: none; color: var(--accent-ink); font: 600 15px var(--font-body, inherit); text-decoration: underline; text-underline-offset: 4px; cursor: pointer; }
.sm-shop .sm-shop-link-btn:hover { text-decoration-thickness: 2px; }
.sm-shop .sm-shop-text-link { align-self: center; display: inline-flex; align-items: center; min-height: 44px; font-size: 15px; font-weight: 600; text-decoration: underline; text-underline-offset: 4px; }
.sm-shop-actions { display: flex; flex-wrap: wrap; gap: 12px; }

/* Fields */
.sm-shop-field { display: flex; flex-direction: column; gap: 8px; }
.sm-shop-label { font-size: 15px; font-weight: 600; }
.sm-shop-optional { font-weight: 400; color: #5f5a56; }
.sm-shop .sm-shop-input { width: 100%; min-height: 52px; padding: 12px 14px; border: 1px solid #8f8a86; border-radius: 0; background: #fff; color: var(--ink); font: 400 17px/1.4 var(--font-body, inherit); box-sizing: border-box; }
.sm-shop textarea.sm-shop-input { resize: vertical; }
.sm-shop .sm-shop-input:hover { border-color: var(--ink); }
.sm-shop .sm-shop-input.is-invalid { border-color: var(--accent-ink); border-width: 2px; }
.sm-shop .sm-shop-select { appearance: none; padding-right: 44px; background-image: linear-gradient(45deg, transparent 50%, #201e1d 50%), linear-gradient(135deg, #201e1d 50%, transparent 50%); background-position: calc(100% - 22px) 50%, calc(100% - 16px) 50%; background-size: 6px 6px; background-repeat: no-repeat; }
.sm-shop-error { margin: 0; font-size: 15px; font-weight: 600; color: var(--accent-ink); }

/* Product grid */
.sm-shop-grid { margin: 0; padding: 0; list-style: none; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; }
.sm-shop .sm-shop-card { display: flex; flex-direction: column; height: 100%; border: 1px solid #d9d6d3; background: #fff; color: var(--ink); text-decoration: none; transition: border-color .15s cubic-bezier(.2,0,0,1); }
.sm-shop .sm-shop-card:hover { border-color: var(--ink); }
.sm-shop-card-body { display: flex; flex-direction: column; gap: 10px; flex: 1; padding: 22px 22px 20px; }
.sm-shop-card-name { font-family: var(--font-display, inherit); font-weight: 800; font-size: 26px; line-height: 1.1; letter-spacing: -0.02em; }
.sm-shop-card-desc { font-size: 16px; line-height: 1.5; color: #4a4541; }
.sm-shop-card-foot { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-top: auto; padding-top: 14px; border-top: 1px solid #e6e4e1; }
.sm-shop-card-cta { font-size: 15px; font-weight: 600; }
.sm-shop .sm-shop-card:hover .sm-shop-card-cta { color: var(--accent-ink); text-decoration: underline; text-underline-offset: 4px; }

/* Card head (ink strip with the product type) and photo */
.sm-shop-card-head { display: flex; align-items: center; gap: 10px; padding: 14px 22px; background: var(--ink); color: #d9d6d3; font-size: 12px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; }
.sm-shop-card-head::before { content: ''; width: 8px; height: 8px; background: var(--accent); }
.sm-shop-media { display: block; width: 100%; height: auto; aspect-ratio: 4 / 3; object-fit: cover; background: #e6e4e1; }

/* Product page */
.sm-shop-product { display: grid; grid-template-columns: minmax(0, 1fr); gap: 32px; align-items: start; }
.sm-shop-product-about { display: flex; flex-direction: column; align-items: flex-start; gap: 18px; }
.sm-shop-lead { margin: 0; max-width: 34ch; font-family: var(--font-display, inherit); font-weight: 600; font-size: clamp(24px, 2.6cqw, 34px); line-height: 1.25; letter-spacing: -0.015em; text-wrap: pretty; }
.sm-shop-product-info { align-items: stretch; }
.sm-shop-add { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px; padding-top: 20px; border-top: 1px solid #e6e4e1; }
.sm-shop-add .sm-shop-btn { flex: 1; }
.sm-shop-field-qty { width: 104px; }

/* Cart and checkout: content + summary */
.sm-shop-split { display: grid; grid-template-columns: minmax(0, 1fr); gap: 32px; align-items: start; }
.sm-shop-lines { margin: 0; padding: 0; list-style: none; border-top: 1px solid var(--ink); }
.sm-shop-line { display: grid; grid-template-columns: minmax(0, 1fr) auto auto auto; align-items: center; gap: 12px 24px; padding: 20px 0; border-bottom: 1px solid #d9d6d3; }
.sm-shop-line-name { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.sm-shop .sm-shop-line-name a { font-family: var(--font-display, inherit); font-weight: 800; font-size: 22px; line-height: 1.15; letter-spacing: -0.015em; text-decoration: none; }
.sm-shop .sm-shop-line-name a:hover { text-decoration: underline; text-underline-offset: 4px; }
.sm-shop-line-qty { display: flex; align-items: center; gap: 8px; }
.sm-shop .sm-shop-line-qty .sm-shop-input { width: 80px; min-height: 44px; padding: 8px 10px; }
.sm-shop-line-total { margin: 0; min-width: 96px; text-align: right; font-family: var(--font-display, inherit); font-weight: 800; font-size: 20px; font-variant-numeric: tabular-nums; }

.sm-shop-summary { display: flex; flex-direction: column; gap: 18px; padding: 28px; background: #fff; border: 1px solid #d9d6d3; }
.sm-shop-sum-lines { margin: 0; padding: 0; list-style: none; }
.sm-shop-sum-lines li { display: flex; justify-content: space-between; gap: 16px; padding: 12px 0; border-bottom: 1px solid #e6e4e1; font-size: 16px; }
.sm-shop-total { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; padding-top: 4px; font-size: 17px; font-weight: 600; }
.sm-shop-total .sm-shop-price { font-size: 30px; }

.sm-shop-form { display: flex; flex-direction: column; gap: 24px; }
.sm-shop-panel { display: flex; flex-direction: column; gap: 20px; margin: 0; padding: 28px; border: 1px solid #d9d6d3; background: #fff; min-width: 0; }
.sm-shop-panel legend { float: left; width: 100%; margin-bottom: 4px; }
.sm-shop-panel legend + * { clear: both; }
.sm-shop-form-foot { display: flex; flex-wrap: wrap; align-items: center; gap: 12px 20px; }

/* Thank-you and order page */
.sm-shop-narrow { display: flex; flex-direction: column; gap: 24px; max-width: 760px; }
.sm-shop .sm-shop-order-url { font-size: 16px; font-weight: 600; overflow-wrap: anywhere; text-decoration: underline; text-underline-offset: 4px; }
.sm-shop-dl { display: flex; flex-direction: column; gap: 14px; margin: 0; }
.sm-shop-dl dt { font-size: 13px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: #5f5a56; }
.sm-shop-dl dd { margin: 4px 0 0; font-size: 17px; white-space: pre-line; }
.sm-shop-status { align-self: flex-start; margin: 0; padding: 6px 12px; border: 1px solid var(--ink); font-size: 13px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.sm-shop-status-paid, .sm-shop-status-fulfilled { background: var(--ink); color: var(--paper); }
.sm-shop-status-cancelled { border-color: var(--accent-ink); color: var(--accent-ink); }

/* Empty states */
.sm-shop-empty { display: flex; flex-direction: column; align-items: flex-start; gap: 12px; padding: 48px 0; }
.sm-shop-empty p { margin: 0 0 8px; font-size: 17px; color: #4a4541; }

/* Wide pages */
@container page (min-width: 1024px) {
    .sm-shop-product { grid-template-columns: minmax(0, 1fr) 380px; gap: 56px; }
    .sm-shop-product.has-media { grid-template-columns: minmax(0, 7fr) minmax(0, 5fr); gap: 64px; }
    .sm-shop-split { grid-template-columns: minmax(0, 1fr) 380px; gap: 56px; }
    .sm-shop-summary { position: sticky; top: 104px; }
}

/* Narrow pages */
@container page (max-width: 960px) {
    .sm-shop-band { padding: 48px 0 40px; }
    .sm-shop-band-row { flex-direction: column; align-items: flex-start; }
    .sm-shop-paper { padding: 32px 0 72px; }
    .sm-shop-grid { grid-template-columns: minmax(0, 1fr); gap: 16px; }
    .sm-shop-panel, .sm-shop-summary { padding: 20px; }
    .sm-shop-price-lg { font-size: 34px; }
}
@container page (max-width: 640px) {
    .sm-shop-line { grid-template-columns: minmax(0, 1fr) auto; grid-template-areas: 'name total' 'qty remove'; }
    .sm-shop-line-name { grid-area: name; }
    .sm-shop-line-total { grid-area: total; min-width: 0; }
    .sm-shop-line-qty { grid-area: qty; }
    .sm-shop-line-remove { grid-area: remove; justify-self: end; }
}

@media (prefers-reduced-motion: reduce) {
    .sm-shop .sm-shop-btn, .sm-shop .sm-shop-card { transition: none; }
}
</style>

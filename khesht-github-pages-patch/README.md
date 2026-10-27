# Khesht / خشت

Headless real-estate site: Vite multi-page frontend (HTML, CSS, JavaScript) and a WordPress CMS plugin (`khesht-core`).

Persian is the launch language (`dir="rtl"`). English is prepared via `/en/` URL space, `lang` attributes, and i18n dictionaries — not filled in v1.

## Architecture

- Public site: real HTML pages (MPA). Vite bundles CSS/JS.
- CMS: WordPress on a separate origin. The theme must not render the public UI.
- API: WordPress REST plus `khesht/v1` custom routes. No GraphQL in v1.
- Forms and SMS: browser → WordPress → SMS adapter. Keys never go to the frontend.
- Market quotes: server-side adapter + cache. Frontend reads `/khesht/v1/market-quotes`.

Local Vite serves the same `/khesht/v1/*` contract from mock JSON so the UI can be developed without WordPress.

## Layout

```
frontend/          Vite MPA, design system, mock API, content renderer
wordpress/wp-content/plugins/khesht-core/   CPTs, REST, forms, adapters, webhook
```

## Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

Build static shells and generate property/article HTML from the API or mock data:

```bash
npm run build
npm run render
```

## WordPress

Copy `wordpress/wp-content/plugins/khesht-core` into a WordPress install. Activate **Khesht Core**. Point `KHESHT_FRONTEND_ORIGIN` at the public site. Use the mock SMS and market adapters until vendors are chosen.

## Missing information (placeholders in the UI)

Do not treat placeholder NAP, fonts, or vendor names as final:

- Logo, favicon, OG image, brand webfonts and license
- Canonical domain (`.com` vs `.ir`)
- Legal name, address, public phone, SMS destination, email
- Instagram, Telegram, Bale URLs
- Cities actually covered; price currency (Rial vs Toman) and “approximate” rules
- Full property field list beyond card fields
- Team photos/copy, about copy, service descriptions
- Admin roles; SMS, map, and market-price vendors
- Hosting, CDN, backups

v1 has **no user accounts**.


## Revision 3 notes (Oct 2026)

- **Palette** (CSS variables at the top of `frontend/src/styles/main.css`): cream `#F5F1E8`, forest `#244536`, earth brown `#956B4B`, stone beige `#D6C2A6`, warm charcoal `#292C28`. Font: Vazirmatn everywhere. Text selection colour is unchanged (`#E5945B`) — edit the `::selection` rule if you want it on-palette.
- **Logo:** `frontend/public/media/logo.png` (transparent cut-out of the supplied logo). Used in header and footer; a click on it goes to the home page ("خانه" is no longer in the menu).
- **Photos:** supplied photos are optimised in `frontend/public/media/` as `hero-*.jpg` (home slider = `hero-home-1`, `hero-shahrak`, `hero-sakht`). `about-*`, `gallery-*`, `sakht-*`, `shahrak-*`, `prop-*`, `mag-*`, `consult` are crops of them used as placeholders; overwrite the same filenames with real photos. `team-*.jpg` are neutral placeholders.
- **3D / motion** (`src/js/fx.js`): page-turn hero slider, pointer parallax on heroes, scroll-depth parallax on photos, tilt cards with glare, magnetic buttons, soft 3D reveals, dropdown flip. Respects `prefers-reduced-motion`. Floating bricks were removed.
- **Home:** hero slider, short description of the work, "آنچه خشت انجام می‌دهد", useful articles, consultation. Introduction of Khesht and the team live on `/about/`.
- **Contact / QR page:** `/card/`.
- **Live prices (Navasan):** production — set `KHESHT_MARKET_ADAPTER=navasan` and `KHESHT_MARKET_API_KEY=...` for the WordPress plugin; local dev — `NAVASAN_API_KEY=...` in `frontend/.env`. Verify item keys (`18ayar`, `sekkeh`, `usd_sell`, `eur`) against your Navasan panel (override with `NAVASAN_ITEMS` / `KHESHT_NAVASAN_ITEMS`).
- The plugin seeds the six property types and deal types on activation; `KHESHT_SMS_TO` example is the company phone.

## Revision 4 notes (Oct 2026)
- Backend: see `wordpress/PANEL-GUIDE.md` (install, content map, request inbox). New: custom DB table for consultation/collaboration requests + admin inbox, Kavenegar SMS adapter, projects CPT, meta boxes, settings page (contact info, SMS number, hero images), `/projects` and `/search` REST routes. `docker-compose.yml` gives a local WordPress (untested here).
- Frontend: header search overlay (press `/` or Ctrl+K), "تماس با ما" in the header, floating consultation card above the footer, magazine category filter, `/sakht/projects/` ("موارد بیشتر"), per-page hero texts, English copyright line, selection colour `#F5F1E8` (inverted on light surfaces).
- WP-managed content is loaded by `src/js/site-content.js`, `projects.js`, `magazine.js`; the static HTML remains as fallback when the API is empty.

## Revision 6 notes
- Detail pages are client-rendered from the API by id: `/amlak/property/?id=`, `/project/?id=`, `/mag/article/?id=` (old `property.html` / `article.html` templates and the `render` script were removed).
- Prices: only dollar, euro, 18k gold and coin cards (Navasan free widget).
- New admin: **گالری** menu, property/project meta fields (video tour, specs, features), `/projects/{id}`, `/gallery`, paginated `/magazine?page=&category=`.

## Revision 7 notes
- New: `/amlak/economic/` (property type `economic`), per-category phone numbers, 2 new spec rows, "تجربه مشتریان" (customers + reviews + admin CRM), CEO page `/ceo/` with its own blog (category `ceo`), 6th service "مشارکت".
- Plugin: DB version 2 (tables `khesht_customers`, `khesht_reviews`) is created automatically; re-activate the plugin once.

## Live preview for the client (GitHub Pages)
The workflow `.github/workflows/deploy.yml` builds `frontend/` in **demo mode** (`VITE_DEMO=1`: sample data from `frontend/mock/data.json` runs in the browser, forms are not stored) and publishes it at `https://<user>.github.io/<repo>/`.

1. Push to GitHub (`git add . && git commit -m "..." && git push`).
2. Repo **Settings → Pages → Build and deployment → Source: GitHub Actions** (not "Deploy from a branch").
3. Open the **Actions** tab; when "Deploy preview to GitHub Pages" is green, the link is shown in the job (and under Settings → Pages).

The real WordPress backend is not used by this preview. For production, build without `VITE_DEMO` and with `VITE_API_BASE` pointing to the public WordPress address.

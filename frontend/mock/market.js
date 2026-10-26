// Server-side quotes for local dev (mirrors the WordPress Navasan adapter).
// The API key lives in .env as NAVASAN_API_KEY (no VITE_ prefix => never bundled).
// Docs: https://www.navasan.tech/webserviceguide/  ->  GET /latest/?api_key=KEY
const DEFAULT_ITEMS = {
  gold: { key: "18ayar", label: "طلای ۱۸ عیار (هر گرم)" },
  coin: { key: "sekkeh", label: "سکه امامی" },
  usd: { key: "usd_sell", label: "دلار" },
  eur: { key: "eur", label: "یورو" },
};

let cache = null;

export async function fetchNavasanQuotes(env, fetchImpl = fetch) {
  const apiKey = env.NAVASAN_API_KEY;
  if (!apiKey) return null;
  if (cache && Date.now() - cache.at < 60_000) return cache.data;

  const base = (env.NAVASAN_BASE || "http://api.navasan.tech").replace(/\/$/, "");
  let items = DEFAULT_ITEMS;
  if (env.NAVASAN_ITEMS) {
    try {
      items = { ...DEFAULT_ITEMS, ...JSON.parse(env.NAVASAN_ITEMS) };
    } catch {
      /* keep defaults */
    }
  }
  const res = await fetchImpl(`${base}/latest/?api_key=${encodeURIComponent(apiKey)}`);
  if (!res.ok) throw new Error(`navasan ${res.status}`);
  const raw = await res.json();

  let newest = 0;
  const out = Object.entries(items).map(([id, def]) => {
    const row = raw[def.key];
    if (row?.timestamp) newest = Math.max(newest, Number(row.timestamp));
    return {
      id,
      label: def.label,
      value: row ? Number(String(row.value).replace(/,/g, "")) : null,
      unit: "تومان",
      change: row ? Number(row.change) : 0,
    };
  });
  const data = {
    updated_at: newest ? new Date(newest * 1000).toISOString() : new Date().toISOString(),
    source: "navasan.tech",
    items: out,
  };
  cache = { at: Date.now(), data };
  return data;
}

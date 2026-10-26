/* Live gold / coin / currency prices.
   Default source: Navasan's FREE widget script (no API key). It calls a global `navasanret(data)` with a JSON string
   holding an HTML table. We never inject that HTML: we read the rows as text and draw our own cards/table.
   VITE_PRICES_SOURCE=api switches to the WordPress /market-quotes endpoint (needs a Navasan API key on the server). */
import { getMarketQuotes } from "../api/market.js";

const SOURCE = import.meta.env.VITE_PRICES_SOURCE || "widget";
const WIDGET = "https://www.navasan.tech/wp-navasan.php?";
const CARD_ITEMS = "usd&eur&18ayar&sekkeh";

const ICONS = { gold: "gold", coin: "coin", usd: "dollar", eur: "euro" };
const LABELS = { usd: "دلار", eur: "یورو", gold: "طلای ۱۸ عیار (هر گرم)", coin: "سکه امامی" };
const DIGIT = /[0-9۰-۹٠-٩]/;
const esc = (v) =>
  String(v ?? "").replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[c]);

/* ---------- Navasan widget loader (JSONP-style) ---------- */
function loadWidget(query) {
  return new Promise((resolve, reject) => {
    const script = document.createElement("script");
    let timer;
    const cleanup = () => {
      clearTimeout(timer);
      script.remove();
      delete window.navasanret;
    };
    window.navasanret = (data) => {
      if (location.search.includes("navasan-debug")) console.log("[navasan raw]", data);
      cleanup();
      resolve(data);
    };
    script.onerror = () => {
      cleanup();
      reject(new Error("navasan widget failed to load"));
    };
    timer = setTimeout(() => {
      cleanup();
      reject(new Error("navasan widget timeout"));
    }, 15000);
    script.src = WIDGET + query;
    document.head.appendChild(script);
  });
}

// The widget returns a JSON-encoded HTML string; keep only the text of each table row.
function parseRows(data) {
  let html = data;
  try {
    const parsed = JSON.parse(data);
    if (typeof parsed === "string") html = parsed;
  } catch {
    /* data was already plain HTML */
  }
  const doc = new DOMParser().parseFromString(String(html), "text/html");
  return [...doc.querySelectorAll("tr")]
    .map((tr) => [...tr.children].map((c) => c.textContent.replace(/\s+/g, " ").trim()))
    .filter((cells) => cells.some(Boolean));
}

function identify(label) {
  if (/یورو/.test(label)) return "eur";
  if (/سکه/.test(label)) return "coin";
  if (/طلا|عیار/.test(label)) return "gold";
  if (/دلار/.test(label)) return "usd";
  return null;
}

function quotesFromRows(rows) {
  const found = {};
  for (const cells of rows) {
    const id = identify(cells[0] || "");
    if (!id || found[id]) continue;
    const nums = cells.slice(1).filter((t) => DIGIT.test(t));
    if (!nums.length) continue;
    found[id] = { id, label: LABELS[id], value: nums[0], change: nums[1] || "" };
  }
  return found;
}

function changeHtml(text) {
  if (!text || /^[0\s۰.,٫٬-]*$/.test(text.replace(/[-−+]/g, ""))) return "";
  const down = /^[-−]|▼|↓/.test(text.trim());
  return `<em class="${down ? "down" : "up"}">${down ? "▼" : "▲"} ${esc(text.replace(/[-−+▲▼↑↓]/g, "").trim())}</em>`;
}

/* ---------- rendering ---------- */
function renderCards(root, quotes) {
  const order = (root.dataset.order || "usd,eur,gold,coin").split(",").filter(Boolean);
  root.innerHTML = order
    .map((id) => {
      const q = quotes[id];
      return `
      <article class="quote tilt" data-tilt-max="7">
        <svg class="icon"><use href="#i-${ICONS[id] || "chart"}"/></svg>
        <span>${esc(LABELS[id] || id)}</span>
        <strong>${q ? esc(q.value) : "—"}</strong>
        <small>تومان ${q ? changeHtml(q.change) : ""}</small>
      </article>`;
    })
    .join("");
  import("./fx.js").then((m) => m.initFx(root));
}

function setMeta(text) {
  const meta = document.querySelector("[data-quotes-meta]");
  if (meta) meta.textContent = text;
}

const nowText = () =>
  new Intl.DateTimeFormat("fa-IR", { timeStyle: "short", timeZone: "Asia/Tehran" }).format(new Date());

/* ---------- sources ---------- */
async function refreshWidget(cardsRoot) {
  const quotes = quotesFromRows(parseRows(await loadWidget(CARD_ITEMS)));
  renderCards(cardsRoot, quotes);
  setMeta(
    Object.keys(quotes).length
      ? `آخرین دریافت: ${nowText()} · منبع: نوسان (navasan.tech)`
      : "قیمت‌ها در حال حاضر در دسترس نیست."
  );
}

async function refreshApi(cardsRoot) {
  const data = await getMarketQuotes();
  const quotes = {};
  data.items.forEach((item) => {
    if (item.value !== null && item.value !== undefined)
      quotes[item.id] = {
        id: item.id,
        label: LABELS[item.id] || item.label,
        value: new Intl.NumberFormat("fa-IR").format(Number(item.value)),
        change: item.change ? String(item.change) : "",
      };
  });
  renderCards(cardsRoot, quotes);
  setMeta(
    data.source === "mock" || data.source === "error"
      ? "قیمت زنده هنوز متصل نشده است."
      : `منبع: ${data.source}`
  );
}

export function initMarketQuotes() {
  const cardsRoot = document.querySelector("[data-market-quotes]");
  if (!cardsRoot) return;
  const load = () =>
    (SOURCE === "api" ? refreshApi(cardsRoot) : refreshWidget(cardsRoot)).catch(() => {
      setMeta("دریافت قیمت‌ها در حال حاضر ممکن نیست.");
    });
  load();
  setInterval(load, 5 * 60 * 1000);
}

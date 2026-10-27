/* Sub-path hosting (e.g. https://user.github.io/khesht/): prefix site-absolute links and images with the Vite base.
   Static HTML assets are already handled by Vite; this covers links and images created by JavaScript. */
const BASE = import.meta.env.BASE_URL || "/";
const prefix = BASE.replace(/\/$/, "");
const SELECTOR = 'a[href^="/"], img[src^="/"], source[src^="/"], audio[src^="/"], video[src^="/"], iframe[src^="/"]';

function fix(el) {
  const attr = el.tagName === "A" ? "href" : "src";
  const v = el.getAttribute(attr);
  if (!v || !v.startsWith("/") || v.startsWith("//")) return;
  if (v === prefix || v.startsWith(prefix + "/")) return;
  el.setAttribute(attr, prefix + v);
}

function scan(root) {
  if (root.nodeType !== 1 && root.nodeType !== 9) return;
  if (root.matches?.(SELECTOR)) fix(root);
  root.querySelectorAll?.(SELECTOR).forEach(fix);
}

export function initBase() {
  if (!prefix) return; // hosted at the domain root: nothing to do
  scan(document);
  new MutationObserver((records) => {
    for (const r of records) {
      if (r.type === "attributes") fix(r.target);
      else r.addedNodes.forEach(scan);
    }
  }).observe(document.documentElement, {
    subtree: true,
    childList: true,
    attributes: true,
    attributeFilter: ["href", "src"],
  });
}

export function initDemoBanner() {
  if (import.meta.env.VITE_DEMO !== "1") return;
  const note = document.createElement("div");
  note.className = "demo-note";
  note.textContent = "نسخه‌ی نمایشی برای بررسی — اطلاعات نمونه؛ فرم‌ها ذخیره نمی‌شوند";
  document.body.appendChild(note);
}

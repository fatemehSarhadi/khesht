import { searchSite } from "../api/content.js";

const PAGES = [
  { title: "املاک", url: "/amlak/", icon: "building", kw: "خرید فروش ملک آپارتمان خانه ویلا فایل" },
  { title: "فایل‌های اقتصادی", url: "/amlak/economic/", icon: "chart", kw: "اقتصادی سرمایه گذاری مشارکت فرصت" },
  { title: "مهندس سعید نوروزی", url: "/ceo/", icon: "user", kw: "مدیر عامل نوروزی وبلاگ" },
  { title: "املاک مسکونی", url: "/amlak/?type=residential", icon: "building", kw: "آپارتمان خانه مسکونی" },
  { title: "زمین", url: "/amlak/?type=land", icon: "land", kw: "زمین قطعه" },
  { title: "باغ و باغچه", url: "/amlak/?type=garden", icon: "tree", kw: "باغ ویلا باغچه" },
  { title: "تجاری و اداری", url: "/amlak/?type=commercial", icon: "store", kw: "مغازه دفتر اداری تجاری" },
  { title: "صنعتی", url: "/amlak/?type=industrial", icon: "factory", kw: "سوله کارگاه صنعتی" },
  { title: "کشاورزی و دامپروری", url: "/amlak/?type=agri", icon: "wheat", kw: "کشاورزی دامداری مرغداری" },
  { title: "ساخت‌وساز", url: "/sakht/", icon: "hammer", kw: "ساخت اجرا معماری بازسازی" },
  { title: "پروژه‌ها و نمونه‌کارها", url: "/sakht/projects/", icon: "image", kw: "نمونه کار پروژه" },
  { title: "شهرک‌سازی و پروژه‌ها", url: "/shahrak/", icon: "city", kw: "شهرک پروژه اجراشده در حال اجرا" },
  { title: "مجله و تحلیل بازار", url: "/mag/", icon: "book", kw: "مقاله آموزش تحلیل پادکست" },
  { title: "قیمت طلا، سکه و ارز", url: "/bazar/", icon: "chart", kw: "قیمت دلار یورو طلا سکه بازار" },
  { title: "درباره ما", url: "/about/", icon: "info", kw: "معرفی تیم سوابق گالری" },
  { title: "تماس با ما", url: "/card/", icon: "phone", kw: "آدرس تلفن شماره نشانی اینستاگرام تلگرام بله" },
  { title: "همکاری با خشت", url: "/hamkari/", icon: "handshake", kw: "همکاری مشارکت" },
];

const norm = (v) =>
  String(v || "")
    .replace(/ي/g, "ی")
    .replace(/ك/g, "ک")
    .replace(/\u200c/g, " ")
    .toLowerCase()
    .trim();
const esc = (v) =>
  String(v ?? "").replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[c]);

const row = (item, icon, tag) => `
  <a class="search__item" href="${esc(item.url)}" data-search-item>
    <span class="search__icon"><svg class="icon"><use href="#i-${icon}"/></svg></span>
    <span class="search__text"><strong>${esc(item.title)}</strong>${item.excerpt ? `<small>${esc(item.excerpt)}</small>` : ""}</span>
    <span class="search__tag">${tag}</span>
  </a>`;

function group(title, html) {
  return html ? `<section class="search__group"><h3>${title}</h3>${html}</section>` : "";
}

export function initSearch() {
  const root = document.querySelector("[data-search]");
  const input = document.querySelector("[data-search-input]");
  const results = document.querySelector("[data-search-results]");
  const hint = document.querySelector("[data-search-hint]");
  if (!root || !input || !results) return;
  let timer;
  let seq = 0;
  let lastFocus = null;

  const open = () => {
    lastFocus = document.activeElement;
    root.classList.add("is-open");
    root.setAttribute("aria-hidden", "false");
    document.documentElement.style.overflow = "hidden";
    setTimeout(() => input.focus(), 60);
  };
  const close = () => {
    root.classList.remove("is-open");
    root.setAttribute("aria-hidden", "true");
    document.documentElement.style.overflow = "";
    lastFocus?.focus?.();
  };

  document.querySelectorAll("[data-open-search]").forEach((b) => b.addEventListener("click", open));
  root.addEventListener("click", (e) => {
    if (e.target === root || e.target.closest("[data-close-search]")) close();
  });
  document.addEventListener("keydown", (e) => {
    const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName || "");
    if ((e.key === "/" && !typing) || ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "k")) {
      e.preventDefault();
      open();
    } else if (e.key === "Escape" && root.classList.contains("is-open")) {
      close();
    } else if (root.classList.contains("is-open") && (e.key === "ArrowDown" || e.key === "ArrowUp")) {
      const items = [...results.querySelectorAll("[data-search-item]")];
      if (!items.length) return;
      e.preventDefault();
      const i = items.indexOf(document.activeElement);
      const next = e.key === "ArrowDown" ? (i + 1) % items.length : (i - 1 + items.length) % items.length;
      items[i === -1 && e.key === "ArrowUp" ? items.length - 1 : next].focus();
    }
  });
  document.querySelector("[data-search-form]")?.addEventListener("submit", (e) => {
    e.preventDefault();
    results.querySelector("[data-search-item]")?.click();
  });

  const render = (q, remote) => {
    const needle = norm(q);
    const pages = PAGES.filter((p) => norm(p.title + " " + p.kw).includes(needle)).slice(0, 5);
    const html =
      group("صفحه‌ها", pages.map((p) => row(p, p.icon, "صفحه")).join("")) +
      group("املاک", (remote?.properties || []).map((p) => row({ ...p, excerpt: p.location }, "building", "ملک")).join("")) +
      group("نمونه‌کارها و پروژه‌ها", (remote?.projects || []).map((p) => row({ ...p, excerpt: p.location }, "hammer", "پروژه")).join("")) +
      group("مجله", (remote?.articles || []).map((a) => row(a, "book", a.category?.name || "مقاله")).join(""));
    results.innerHTML =
      html || `<p class="search__empty">نتیجه‌ای برای «${esc(q)}» پیدا نشد. می‌توانید با کارشناسان ما تماس بگیرید.</p>`;
  };

  input.addEventListener("input", () => {
    const q = input.value.trim();
    clearTimeout(timer);
    if (q.length < 2) {
      results.innerHTML = "";
      hint.hidden = false;
      return;
    }
    hint.hidden = true;
    render(q, null);
    const id = ++seq;
    timer = setTimeout(async () => {
      try {
        const remote = await searchSite(q);
        if (id === seq) render(q, remote);
      } catch {
        /* keep static page results */
      }
    }, 220);
  });
}

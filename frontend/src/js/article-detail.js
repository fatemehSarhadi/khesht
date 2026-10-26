import { getArticle, getMagazine } from "../api/content.js";
import { articleCardHtml } from "./magazine.js";
import { initFx } from "./fx.js";
import { esc, icon, queryKey, setPageMeta, setHero, notFound, shareHtml, bindShare } from "./detail-ui.js";

const fa = new Intl.NumberFormat("fa-IR");
const dateFa = new Intl.DateTimeFormat("fa-IR", { dateStyle: "long" });

function mediaHtml(url) {
  if (!url) return "";
  if (/\.(mp3|m4a|ogg|wav)(\?|$)/i.test(url)) return `<div class="article__media"><audio controls preload="none" src="${esc(url)}"></audio></div>`;
  if (/\.(mp4|webm)(\?|$)/i.test(url)) return `<div class="article__media"><video controls preload="none" src="${esc(url)}"></video></div>`;
  return `<p class="article__media"><a class="btn btn--video" href="${esc(url)}" target="_blank" rel="noopener">${icon("play")}مشاهده / شنیدن</a></p>`;
}

function render(root, a) {
  const d = a.date && !Number.isNaN(new Date(a.date)) ? dateFa.format(new Date(a.date)) : "";
  const meta = [
    a.category ? `<a class="badge badge--brown" href="/mag/?cat=${esc(a.category.slug)}">${esc(a.category.name)}</a>` : "",
    d ? `<span>${d}</span>` : "",
    a.reading_minutes ? `<span>زمان مطالعه: ${fa.format(a.reading_minutes)} دقیقه</span>` : "",
    a.author ? `<span>${esc(a.author)}</span>` : "",
  ].filter(Boolean).join("");
  root.innerHTML = `
    <article class="article">
      <div class="article__meta">${meta}</div>
      ${a.image ? `<figure class="article__cover"><img src="${esc(a.image)}" alt="${esc(a.title)}" /></figure>` : ""}
      ${mediaHtml(a.media_url)}
      <div class="prose">${a.body || ""}</div>
      ${shareHtml()}
    </article>
    <section class="detail-related" data-related hidden>
      <h2 class="detail__h">مطالب مرتبط</h2>
      <div class="grid grid--3" data-related-grid></div>
    </section>`;
  bindShare(root, a.title);
  initFx(root);
}

async function loadRelated(root, a) {
  try {
    const data = await getMagazine({ category: a.category?.slug, per_page: 4 });
    const items = data.items.filter((x) => String(x.id) !== String(a.id)).slice(0, 3);
    if (!items.length) return;
    root.querySelector("[data-related-grid]").innerHTML = items.map(articleCardHtml).join("");
    root.querySelector("[data-related]").hidden = false;
    initFx(root);
  } catch {
    /* optional */
  }
}

export async function initArticleDetail() {
  const root = document.querySelector("[data-article-detail]");
  if (!root) return;
  const key = queryKey();
  if (!key) return notFound(root, "/mag/", "بازگشت به مجله");
  try {
    const a = await getArticle(key);
    if (!a?.title) throw new Error("not found");
    setPageMeta(a.title, a.excerpt);
    setHero(a.title, a.category?.name || "");
    render(root, a);
    loadRelated(root, a);
  } catch {
    notFound(root, "/mag/", "بازگشت به مجله");
  }
}

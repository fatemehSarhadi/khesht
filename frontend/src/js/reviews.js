/* تجربه مشتریان: reviews list + customer sign-up/login (password arrives by SMS) + review form. */
import {
  getSession, setSession, clearSession, registerCustomer, loginCustomer, forgotPassword, getMe, getReviews, postReview,
} from "../api/customers.js";
import { accountErrorMessage } from "./form-errors.js";
import { initFx } from "./fx.js";

const fa = new Intl.NumberFormat("fa-IR");
const dateFa = new Intl.DateTimeFormat("fa-IR", { dateStyle: "medium" });
const esc = (v) =>
  String(v ?? "").replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[c]);
const stars = (n) => `<span class="stars" aria-label="${n} از ۵">${"★".repeat(n)}<i>${"★".repeat(5 - n)}</i></span>`;

function reviewCard(r) {
  const d = r.date && !Number.isNaN(new Date(r.date)) ? dateFa.format(new Date(r.date)) : "";
  return `
    <article class="review tilt" data-reveal>
      ${stars(r.rating)}
      <p class="review__body">${esc(r.body)}</p>
      <footer><strong>${esc(r.name)}</strong><span>${d}</span></footer>
      ${r.reply ? `<div class="review__reply"><b>پاسخ خشت:</b> ${esc(r.reply)}</div>` : ""}
    </article>`;
}

/* ---------- auth modal ---------- */
let modal;
function ensureModal() {
  if (modal) return modal;
  modal = document.createElement("div");
  modal.className = "modal";
  modal.innerHTML = `
    <div class="modal__panel" role="dialog" aria-modal="true" aria-label="ورود و ثبت‌نام">
      <div class="tabs" role="tablist">
        <button type="button" class="tab is-active" data-tab="register">ثبت‌نام</button>
        <button type="button" class="tab" data-tab="login">ورود</button>
      </div>

      <form class="form" data-form="register">
        <p class="lede" style="font-size:.95rem">نام و شماره‌ی موبایل خود را وارد کنید. رمز ورود برای شما <strong>پیامک</strong> می‌شود.</p>
        <input type="text" name="website" tabindex="-1" autocomplete="off" hidden />
        <label>نام و نام خانوادگی<input name="name" required maxlength="80" autocomplete="name" /></label>
        <label>شماره‌ی موبایل<input name="phone" required inputmode="tel" maxlength="15" autocomplete="tel" dir="ltr" placeholder="09123456789" /></label>
        <p data-error></p>
        <button class="btn" type="submit">دریافت رمز با پیامک</button>
      </form>

      <form class="form" data-form="login" hidden>
        <label>شماره‌ی موبایل<input name="phone" required inputmode="tel" maxlength="15" autocomplete="tel" dir="ltr" /></label>
        <label>رمز ورود (از پیامک)<input name="password" required maxlength="20" autocomplete="current-password" dir="ltr" /></label>
        <p data-error></p>
        <button class="btn" type="submit">ورود</button>
        <button class="link-btn" type="button" data-tab="forgot">رمز را فراموش کرده‌ام</button>
      </form>

      <form class="form" data-form="forgot" hidden>
        <p class="lede" style="font-size:.95rem">شماره‌ی ثبت‌نام‌شده را وارد کنید؛ رمز جدید پیامک می‌شود.</p>
        <label>شماره‌ی موبایل<input name="phone" required inputmode="tel" maxlength="15" dir="ltr" /></label>
        <p data-error></p>
        <button class="btn" type="submit">ارسال رمز جدید</button>
        <button class="link-btn" type="button" data-tab="login">بازگشت به ورود</button>
      </form>
      <p class="modal__note" data-note hidden></p>
      <button class="btn btn--outline btn--sm" type="button" data-close style="margin-top:14px">بستن</button>
    </div>`;
  document.body.appendChild(modal);

  const forms = {
    register: modal.querySelector('[data-form="register"]'),
    login: modal.querySelector('[data-form="login"]'),
    forgot: modal.querySelector('[data-form="forgot"]'),
  };
  const note = modal.querySelector("[data-note]");
  const show = (tab) => {
    Object.entries(forms).forEach(([k, f]) => (f.hidden = k !== tab));
    modal.querySelectorAll(".tabs .tab").forEach((t) => t.classList.toggle("is-active", t.dataset.tab === tab));
    modal.querySelectorAll("[data-error]").forEach((e) => (e.textContent = ""));
  };
  modal.show = show;
  modal.setNote = (text) => {
    note.textContent = text || "";
    note.hidden = !text;
  };
  modal.addEventListener("click", (e) => {
    if (e.target === modal || e.target.closest("[data-close]")) modal.classList.remove("is-open");
    const tab = e.target.closest("[data-tab]");
    if (tab) {
      show(tab.dataset.tab);
      modal.setNote("");
    }
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") modal.classList.remove("is-open");
  });

  const submit = (name, handler) =>
    forms[name].addEventListener("submit", async (e) => {
      e.preventDefault();
      const f = new FormData(forms[name]);
      if (f.get("website")) return;
      const err = forms[name].querySelector("[data-error]");
      err.textContent = "";
      try {
        await handler(Object.fromEntries(f.entries()));
      } catch (error) {
        err.textContent = accountErrorMessage(error);
      }
    });

  submit("register", async (v) => {
    const res = await registerCustomer({ name: v.name, phone: v.phone, website: "" });
    forms.login.querySelector('[name="phone"]').value = v.phone;
    show("login");
    modal.setNote(
      (res.sms_sent === false
        ? "ثبت‌نام انجام شد، اما ارسال پیامک ناموفق بود. از «فراموشی رمز» استفاده کنید."
        : "رمز ورود برای شما پیامک شد. آن را اینجا وارد کنید.") +
        (res.dev_password ? ` (حالت آزمایشی — رمز: ${res.dev_password})` : "")
    );
  });
  submit("login", async (v) => {
    const res = await loginCustomer({ phone: v.phone, password: v.password });
    setSession(res.token, res.customer?.name || "");
    modal.classList.remove("is-open");
    document.dispatchEvent(new CustomEvent("khesht:session"));
  });
  submit("forgot", async (v) => {
    const res = await forgotPassword(v.phone);
    forms.login.querySelector('[name="phone"]').value = v.phone;
    show("login");
    modal.setNote("اگر این شماره ثبت‌نام کرده باشد، رمز جدید پیامک شد." + (res.dev_password ? ` (حالت آزمایشی — رمز: ${res.dev_password})` : ""));
  });
  return modal;
}

function openModal(tab = "register") {
  const m = ensureModal();
  m.show(tab);
  m.setNote("");
  m.classList.add("is-open");
  m.querySelector(`[data-form="${tab}"] input:not([hidden])`)?.focus();
}

/* ---------- section ---------- */
export function initReviews() {
  const root = document.querySelector("[data-reviews]");
  if (!root) return;
  const list = root.querySelector("[data-reviews-list]");
  const summary = root.querySelector("[data-reviews-summary]");
  const authBox = root.querySelector("[data-reviews-auth]");
  const formBox = root.querySelector("[data-review-form]");
  const more = root.querySelector("[data-reviews-more]");
  const empty = root.querySelector("[data-reviews-empty]");
  let page = 1;

  async function load(append) {
    try {
      const data = await getReviews({ page, per_page: 6 });
      const html = data.items.map(reviewCard).join("");
      if (append) list.insertAdjacentHTML("beforeend", html);
      else list.innerHTML = html;
      empty.hidden = data.total > 0;
      summary.innerHTML = data.total
        ? `${stars(Math.round(data.average))}<strong>${fa.format(data.average)}</strong> از ۵ · ${fa.format(data.total)} نظر`
        : "";
      more.hidden = page >= data.total_pages;
      initFx(list);
    } catch {
      empty.hidden = false;
    }
  }

  function renderAuth() {
    const s = getSession();
    if (!s) {
      authBox.innerHTML = `
        <button class="btn" type="button" data-auth="register">ثبت‌نام و ثبت نظر</button>
        <button class="btn btn--outline" type="button" data-auth="login">ورود</button>`;
      formBox.innerHTML = "";
      return;
    }
    authBox.innerHTML = `<span class="review-hello">سلام، <strong>${esc(s.name)}</strong></span><button class="link-btn" type="button" data-logout>خروج</button>`;
    formBox.innerHTML = `
      <form class="form review-form" data-form-review>
        <h3>تجربه‌ی خود را بنویسید</h3>
        <div class="rating-input" role="radiogroup" aria-label="امتیاز">
          ${[5, 4, 3, 2, 1].map((n) => `<input type="radio" name="rating" id="rt${n}" value="${n}" ${n === 5 ? "checked" : ""}><label for="rt${n}" title="${n} ستاره">★</label>`).join("")}
        </div>
        <label>نظر شما<textarea name="body" rows="4" required minlength="10" maxlength="1000" placeholder="از همکاری با خشت چه تجربه‌ای داشتید؟"></textarea></label>
        <p data-error></p>
        <button class="btn" type="submit">ثبت نظر</button>
      </form>
      <div class="confirm-card" data-review-confirm></div>`;
    const form = formBox.querySelector("[data-form-review]");
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const f = new FormData(form);
      const err = form.querySelector("[data-error]");
      err.textContent = "";
      try {
        const res = await postReview({ rating: Number(f.get("rating")), body: f.get("body") });
        form.hidden = true;
        const c = formBox.querySelector("[data-review-confirm]");
        c.textContent =
          res.status === "approved"
            ? "نظر شما ثبت و منتشر شد. سپاسگزاریم!"
            : "نظر شما ثبت شد و پس از بررسی منتشر می‌شود. سپاسگزاریم!";
        c.classList.add("is-visible");
        if (res.status === "approved") {
          page = 1;
          load(false);
        }
      } catch (error) {
        const status = Number(/ (\d{3})$/.exec(error?.message || "")?.[1]) || 0;
        if (status === 401) {
          clearSession();
          renderAuth();
        }
        err.textContent = accountErrorMessage(error);
      }
    });
  }

  root.addEventListener("click", (e) => {
    const a = e.target.closest("[data-auth]");
    if (a) openModal(a.dataset.auth);
    if (e.target.closest("[data-logout]")) {
      clearSession();
      renderAuth();
    }
  });
  more.addEventListener("click", () => {
    page += 1;
    load(true);
  });
  document.addEventListener("khesht:session", renderAuth);

  renderAuth();
  load(false);
  // drop a stale token
  if (getSession()) {
    getMe().catch((error) => {
      if (/ 401$/.test(error?.message || "")) {
        clearSession();
        renderAuth();
      }
    });
  }
}

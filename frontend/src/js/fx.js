/* 3D / animation layer: reveal, tilt, hero slider, pointer + scroll depth, scroll UI */
const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
const finePointer = window.matchMedia("(pointer: fine)").matches;

let revealObserver;
function getObserver() {
  if (revealObserver || !("IntersectionObserver" in window)) return revealObserver;
  revealObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-in");
          revealObserver.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12, rootMargin: "0px 0px -6% 0px" }
  );
  return revealObserver;
}

function initReveal(root) {
  const els = root.querySelectorAll("[data-reveal]:not([data-fx])");
  const obs = getObserver();
  els.forEach((el) => {
    el.dataset.fx = "1";
    if (!el.style.getPropertyValue("--i")) {
      const sibs = [...(el.parentElement?.children || [])].filter((n) =>
        n.hasAttribute("data-reveal")
      );
      el.style.setProperty("--i", String(Math.min(sibs.indexOf(el), 6)));
    }
    if (reduce || !obs) el.classList.add("is-in");
    else obs.observe(el);
  });
}

function initTilt(root) {
  if (reduce || !finePointer) return;
  root.querySelectorAll(".tilt:not([data-tilt])").forEach((el) => {
    el.dataset.tilt = "1";
    const max = Number(el.dataset.tiltMax || 9);
    const glare = document.createElement("span");
    glare.className = "tilt-glare";
    el.appendChild(glare);
    el.addEventListener("pointerenter", () => el.classList.add("is-tilting"));
    el.addEventListener("pointermove", (event) => {
      const r = el.getBoundingClientRect();
      const px = (event.clientX - r.left) / r.width;
      const py = (event.clientY - r.top) / r.height;
      el.style.setProperty("--ry", `${((px - 0.5) * max * 2).toFixed(2)}deg`);
      el.style.setProperty("--rx", `${((0.5 - py) * max * 2).toFixed(2)}deg`);
      el.style.setProperty("--gx", `${(px * 100).toFixed(0)}%`);
      el.style.setProperty("--gy", `${(py * 100).toFixed(0)}%`);
    });
    el.addEventListener("pointerleave", () => {
      el.classList.remove("is-tilting");
      el.style.setProperty("--rx", "0deg");
      el.style.setProperty("--ry", "0deg");
    });
  });
}

function initHeroPointer(root) {
  if (reduce || !finePointer) return;
  root.querySelectorAll(".hero:not([data-ptr])").forEach((hero) => {
    hero.dataset.ptr = "1";
    hero.addEventListener("pointermove", (event) => {
      const r = hero.getBoundingClientRect();
      hero.style.setProperty("--mx", ((event.clientX - r.left) / r.width - 0.5).toFixed(3));
      hero.style.setProperty("--my", ((event.clientY - r.top) / r.height - 0.5).toFixed(3));
    });
    hero.addEventListener("pointerleave", () => {
      hero.style.setProperty("--mx", "0");
      hero.style.setProperty("--my", "0");
    });
  });
}

function initMagnetic(root) {
  if (reduce || !finePointer) return;
  root.querySelectorAll(".btn:not([data-mag])").forEach((btn) => {
    btn.dataset.mag = "1";
    btn.addEventListener("pointermove", (event) => {
      const r = btn.getBoundingClientRect();
      btn.style.setProperty("--tx", `${((event.clientX - r.left - r.width / 2) * 0.18).toFixed(1)}px`);
      btn.style.setProperty("--ty", `${((event.clientY - r.top - r.height / 2) * 0.28).toFixed(1)}px`);
    });
    btn.addEventListener("pointerleave", () => {
      btn.style.setProperty("--tx", "0px");
      btn.style.setProperty("--ty", "0px");
    });
  });
}

// depth: images inside .photo / .cta-block__media drift slower than the page
const parallaxEls = new Set();
function initParallax(root) {
  root.querySelectorAll(".photo, .cta-block__media").forEach((el) => parallaxEls.add(el));
  updateParallax();
}
function updateParallax() {
  if (reduce) return;
  const vh = window.innerHeight;
  parallaxEls.forEach((el) => {
    const r = el.getBoundingClientRect();
    if (r.bottom < -100 || r.top > vh + 100) return;
    const t = (r.top + r.height / 2 - vh / 2) / vh;
    el.style.setProperty("--py", `${(t * -42).toFixed(1)}px`);
  });
}

function initSliders(root) {
  root.querySelectorAll("[data-slider]:not([data-fx])").forEach((slider) => {
    slider.dataset.fx = "1";
    const slides = [...slider.querySelectorAll(".slider__slide")];
    if (slides.length < 2) {
      slides[0]?.classList.add("is-active");
      return;
    }
    const ui = slider.closest(".hero")?.querySelector(".slider__ui");
    let index = 0;
    let timer;
    const dots = [];
    slides[0].classList.add("is-active");

    const go = (next) => {
      if (next === index) return;
      const from = slides[index];
      const to = slides[next];
      slides.forEach((s) => s.classList.remove("is-active", "is-leaving"));
      from.classList.add("is-leaving");
      to.classList.add("is-active");
      dots[index]?.classList.remove("is-active");
      dots[next]?.classList.add("is-active");
      index = next;
      setTimeout(() => from.classList.remove("is-leaving"), 1250);
    };
    const step = (dir) => go((index + dir + slides.length) % slides.length);
    const start = () => {
      if (reduce) return;
      stop();
      timer = setInterval(() => step(1), 6500);
    };
    const stop = () => clearInterval(timer);

    if (ui) {
      const mkNav = (label, icon, dir) => {
        const b = document.createElement("button");
        b.type = "button";
        b.className = "slider__nav";
        b.setAttribute("aria-label", label);
        b.innerHTML = `<svg class="icon"><use href="#i-${icon}"/></svg>`;
        b.addEventListener("click", () => {
          step(dir);
          start();
        });
        return b;
      };
      ui.appendChild(mkNav("اسلاید قبلی", "arrow-right", -1));
      slides.forEach((_, i) => {
        const dot = document.createElement("button");
        dot.type = "button";
        dot.className = "slider__dot" + (i === 0 ? " is-active" : "");
        dot.setAttribute("aria-label", `اسلاید ${i + 1}`);
        dot.addEventListener("click", () => {
          go(i);
          start();
        });
        dots.push(dot);
        ui.appendChild(dot);
      });
      ui.appendChild(mkNav("اسلاید بعدی", "arrow-left", 1));
    }
    const host = slider.closest(".hero") || slider;
    host.addEventListener("pointerenter", stop);
    host.addEventListener("pointerleave", start);
    document.addEventListener("visibilitychange", () =>
      document.hidden ? stop() : start()
    );
    start();
  });
}

function initScrollUi() {
  const bar = document.querySelector(".scroll-progress");
  const top = document.querySelector(".to-top");
  let ticking = false;
  const update = () => {
    const max = document.documentElement.scrollHeight - window.innerHeight;
    const p = max > 0 ? window.scrollY / max : 0;
    if (bar) bar.style.transform = `scaleX(${p.toFixed(4)})`;
    top?.classList.toggle("is-visible", window.scrollY > 700);
    document.querySelector(".site-header")?.classList.toggle("is-scrolled", window.scrollY > 40);
    updateParallax();
    ticking = false;
  };
  window.addEventListener(
    "scroll",
    () => {
      if (!ticking) {
        ticking = true;
        requestAnimationFrame(update);
      }
    },
    { passive: true }
  );
  top?.addEventListener("click", () =>
    window.scrollTo({ top: 0, behavior: reduce ? "auto" : "smooth" })
  );
  update();
}

export function initFx(root = document) {
  initReveal(root);
  initTilt(root);
  initHeroPointer(root);
  initMagnetic(root);
  initParallax(root);
  initSliders(root);
}

export function initScroll() {
  initScrollUi();
}

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

export function formatPrice(amount, approximate) {
  const n = new Intl.NumberFormat("fa-IR").format(amount);
  return `${approximate ? "حدود " : ""}${n} تومان`;
}

export function formatArea(m2) {
  return `${new Intl.NumberFormat("fa-IR").format(m2)} متر مربع`;
}

// Fallback placeholder photos until real photos are uploaded in WordPress.
export function propertyImage(item) {
  if (item.image) return item.image;
  return `/media/prop-${(Number(item.id) % 6) + 1}.jpg`;
}

export function propertyCardHtml(item) {
  const badge = item.featured
    ? `<span class="card__badge"><svg class="icon"><use href="#i-award"/></svg>ویژه</span>`
    : "";
  const title = escapeHtml(item.title);
  return `
    <a class="card tilt" data-reveal href="${escapeHtml(item.url)}">
      <div class="card__media">
        ${badge}
        <img src="${escapeHtml(propertyImage(item))}" alt="${title}" loading="lazy" />
      </div>
      <div class="card__body">
        <h3>${title}</h3>
        <p class="card__meta">
          <span><svg class="icon"><use href="#i-pin"/></svg>${escapeHtml(item.location)}</span>
          <span><svg class="icon"><use href="#i-area"/></svg>${formatArea(item.area_m2)}</span>
          <span><svg class="icon"><use href="#i-price"/></svg>${formatPrice(item.price_amount, item.price_is_approximate)}</span>
        </p>
        <span class="card__more">مشاهده جزئیات <svg class="icon"><use href="#i-arrow-left"/></svg></span>
      </div>
    </a>
  `;
}

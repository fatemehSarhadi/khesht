// Browser-safe mock API core (no Node imports). Used by the Vite dev server and by the GitHub Pages demo build.
function childTypeSlugs(tax, slug) {
  const items = tax.property_type;
  const match = items.find((t) => t.slug === slug);
  if (!match) return [slug];
  const children = items.filter((t) => t.parent === slug).map((t) => t.slug);
  return [slug, ...children];
}

export function filterProperties(data, query) {
  const typeSlugs = query.type
    ? childTypeSlugs(data.taxonomies, query.type)
    : null;
  const page = Math.max(1, Number(query.page) || 1);
  const perPage = Math.min(24, Math.max(1, Number(query.per_page) || 12));

  let items = data.properties.filter((p) => {
    if (typeSlugs && !p.property_type.some((t) => typeSlugs.includes(t))) {
      return false;
    }
    if (query.deal && !p.transaction_type.includes(query.deal)) return false;
    if (query.city && p.city !== query.city) return false;
    if (query.region && p.region !== query.region) return false;
    if (query.neighborhood && p.neighborhood !== query.neighborhood) {
      return false;
    }
    if (query.featured === "1" && !p.featured) return false;
    const pmin = query.price_min ? Number(query.price_min) : null;
    const pmax = query.price_max ? Number(query.price_max) : null;
    const amin = query.area_min ? Number(query.area_min) : null;
    const amax = query.area_max ? Number(query.area_max) : null;
    if (pmin != null && p.price_amount < pmin) return false;
    if (pmax != null && p.price_amount > pmax) return false;
    if (amin != null && p.area_m2 < amin) return false;
    if (amax != null && p.area_m2 > amax) return false;
    return true;
  });

  const total = items.length;
  const start = (page - 1) * perPage;
  items = items.slice(start, start + perPage);

  return {
    items: items.map(toPropertyCard),
    total,
    page,
    per_page: perPage,
    total_pages: Math.max(1, Math.ceil(total / perPage)),
  };
}

export function toPropertyCard(p) {
  return {
    id: p.id,
    slug: p.slug,
    title: p.title,
    image: p.image,
    location: p.location.label,
    area_m2: p.area_m2,
    price_amount: p.price_amount,
    price_is_approximate: p.price_is_approximate,
    currency: p.currency,
    featured: p.featured,
    code: p.code,
    url: `/amlak/property/?id=${p.id}`,
  };
}

const devCustomers = new Map();
const devNewReviews = [];
const devPassword = () => Array.from({ length: 6 }, () => "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"[Math.floor(Math.random() * 32)]).join("");
const devPhone = (raw) => {
  const p = String(raw || "").replace(/[\s-]/g, "").replace(/^\+98/, "0").replace(/^98(?=9\d{9}$)/, "0");
  return /^09\d{9}$/.test(p) ? p : "";
};

export function handleKheshtApi(pathname, searchParams, method, body, headers = {}, data = {}) {
  const q = Object.fromEntries(searchParams.entries());
  const tokenCustomer = () => {
    const t = String(headers["x-khesht-token"] || "");
    const m = t.match(/^dev-(\d{9,11})$/);
    return m ? devCustomers.get(m[1]) : null;
  };
  const err = (status, code, message) => ({ status, json: { code, message, data: { status } } });

  /* ----- customers + reviews (in-memory dev simulation of the WordPress CRM) ----- */
  if (method === "POST" && pathname === "/khesht/v1/customers/register") {
    const name = String(body?.name || "").trim();
    const phone = devPhone(body?.phone);
    if (name.length < 3) return err(400, "khesht_validation", "name");
    if (!phone) return err(400, "khesht_invalid_phone", "phone");
    if (devCustomers.has(phone)) return err(409, "khesht_exists", "exists");
    const password = devPassword();
    devCustomers.set(phone, { name, phone, password });
    console.log(`[khesht-mock] SMS to ${phone}: رمز ورود ${password}`);
    return { status: 201, json: { ok: true, sms_sent: true, dev_password: password } };
  }
  if (method === "POST" && pathname === "/khesht/v1/customers/forgot") {
    const phone = devPhone(body?.phone);
    if (!phone) return err(400, "khesht_invalid_phone", "phone");
    const c = devCustomers.get(phone);
    if (c) {
      c.password = devPassword();
      console.log(`[khesht-mock] SMS to ${phone}: رمز ورود ${c.password}`);
    }
    return { status: 200, json: { ok: true, dev_password: c ? c.password : undefined } };
  }
  if (method === "POST" && pathname === "/khesht/v1/customers/login") {
    const phone = devPhone(body?.phone);
    const c = devCustomers.get(phone);
    if (!c || c.password !== String(body?.password || "").trim().toUpperCase())
      return err(401, "khesht_bad_login", "bad login");
    return { status: 200, json: { token: `dev-${phone}`, customer: { name: c.name } } };
  }
  if (method === "GET" && pathname === "/khesht/v1/customers/me") {
    const c = tokenCustomer();
    return c ? { status: 200, json: { customer: { name: c.name } } } : err(401, "khesht_unauthorized", "unauthorized");
  }
  if (method === "GET" && pathname === "/khesht/v1/reviews") {
    const all = [...devNewReviews.filter((r) => r.status === "approved"), ...(data.reviews || [])];
    const per = Math.min(24, Math.max(1, Number(q.per_page) || 6));
    const page = Math.max(1, Number(q.page) || 1);
    const avg = all.length ? Math.round((all.reduce((n, r) => n + r.rating, 0) / all.length) * 10) / 10 : 0;
    return {
      status: 200,
      json: { items: all.slice((page - 1) * per, page * per), total: all.length, average: avg, total_pages: Math.max(1, Math.ceil(all.length / per)), page },
    };
  }
  if (method === "POST" && pathname === "/khesht/v1/reviews") {
    const c = tokenCustomer();
    if (!c) return err(401, "khesht_unauthorized", "unauthorized");
    const rating = Number(body?.rating);
    const text = String(body?.body || "").trim();
    if (!(rating >= 1 && rating <= 5) || text.length < 10) return err(400, "khesht_validation", "validation");
    devNewReviews.push({ id: 1000 + devNewReviews.length, name: c.name, rating, body: text, reply: "", date: new Date().toISOString().slice(0, 10), status: "pending" });
    return { status: 201, json: { ok: true, status: "pending" } };
  }

  if (method === "GET" && pathname === "/khesht/v1/site") {
    return { status: 200, json: { site: data.site, heroes: data.heroes } };
  }
  if (method === "GET" && pathname === "/khesht/v1/team") {
    return { status: 200, json: { items: data.team } };
  }
  if (method === "GET" && pathname === "/khesht/v1/services") {
    return { status: 200, json: { items: data.services } };
  }
  if (method === "GET" && pathname === "/khesht/v1/partners") {
    return { status: 200, json: { items: data.partners } };
  }
  if (method === "GET" && pathname === "/khesht/v1/area-prices") {
    return { status: 200, json: { items: data.areaPrices } };
  }
  if (method === "GET" && pathname === "/khesht/v1/market-quotes") {
    return { status: 200, json: data.marketQuotes };
  }
  if (method === "GET" && pathname === "/khesht/v1/market-insights") {
    return { status: 200, json: { items: data.marketInsights } };
  }
  if (method === "GET" && pathname === "/khesht/v1/taxonomies") {
    return { status: 200, json: data.taxonomies };
  }
  if (method === "GET" && pathname === "/khesht/v1/properties") {
    return { status: 200, json: filterProperties(data, q) };
  }

  const propMatch = pathname.match(/^\/khesht\/v1\/properties\/([^/]+)$/);
  if (method === "GET" && propMatch) {
    const key = decodeURIComponent(propMatch[1]);
    const item = data.properties.find((p) => String(p.id) === key || p.slug === key);
    if (!item) return { status: 404, json: { error: "not_found" } };
    return { status: 200, json: item };
  }

  if (method === "GET" && pathname === "/khesht/v1/projects") {
    let items = data.projects || [];
    if (q.group) items = items.filter((x) => x.group === q.group);
    if (q.status) items = items.filter((x) => x.status === q.status);
    const limit = Number(q.limit) || 0;
    const total = items.length;
    if (limit) items = items.slice(0, limit);
    return { status: 200, json: { items, total } };
  }

  const projMatch = pathname.match(/^\/khesht\/v1\/projects\/([^/]+)$/);
  if (method === "GET" && projMatch) {
    const key = decodeURIComponent(projMatch[1]);
    const item = (data.projects || []).find((x) => String(x.id) === key || x.slug === key);
    if (!item) return { status: 404, json: { error: "not_found" } };
    return { status: 200, json: item };
  }

  if (method === "GET" && pathname === "/khesht/v1/gallery") {
    return { status: 200, json: { items: data.gallery || [] } };
  }

  if (method === "GET" && pathname === "/khesht/v1/search") {
    return { status: 200, json: searchAll(data, String(q.q || "")) };
  }

  if (method === "GET" && pathname === "/khesht/v1/magazine") {
    const per = Math.min(24, Math.max(1, Number(q.per_page) || 9));
    const page = Math.max(1, Number(q.page) || 1);
    const needle = norm(q.s || "").trim();
    const all = data.articles
      .filter((a) => (q.category ? a.category?.slug === q.category : a.category?.slug !== "ceo"))
      .filter((a) => !needle || norm(a.title + " " + a.excerpt).includes(needle));
    const items = all.slice((page - 1) * per, page * per).map((a) => ({
      id: a.id,
      slug: a.slug,
      title: a.title,
      excerpt: a.excerpt,
      date: a.date,
      author: a.author,
      category: a.category,
      image: a.image,
      reading_minutes: a.reading_minutes,
      media_url: a.media_url,
      url: `/mag/article/?id=${a.id}`,
    }));
    return {
      status: 200,
      json: { items, total: all.length, total_pages: Math.max(1, Math.ceil(all.length / per)), page },
    };
  }

  const magMatch = pathname.match(/^\/khesht\/v1\/magazine\/([^/]+)$/);
  if (method === "GET" && magMatch) {
    const key = decodeURIComponent(magMatch[1]);
    const item = data.articles.find((a) => String(a.id) === key || a.slug === key);
    if (!item) return { status: 404, json: { error: "not_found" } };
    return { status: 200, json: item };
  }

  if (method === "POST" && pathname === "/khesht/v1/consultation") {
    return submitForm(body, ["name", "phone", "subject", "description"]);
  }
  if (method === "POST" && pathname === "/khesht/v1/collaboration") {
    return submitForm(body, ["name", "phone", "subject", "description"]);
  }

  return null;
}

function submitForm(body, fields) {
  const missing = fields.filter((f) => !String(body?.[f] || "").trim());
  if (missing.length) {
    return { status: 400, json: { error: "validation", missing } };
  }
  const phone = String(body.phone).replace(/\s/g, "");
  if (!/^0?9\d{9}$/.test(phone) && !/^0\d{10}$/.test(phone)) {
    return { status: 400, json: { error: "invalid_phone" } };
  }
  return {
    status: 201,
    json: {
      ok: true,
      sms: "mock",
      message:
        "از ثبت درخواست شما بسیار متشکریم، کارشناسان ما در اسرع وقت با شما تماس خواهند گرفت.",
    },
  };
}

const norm = (v) =>
  String(v || "")
    .replace(/ي/g, "ی")
    .replace(/ك/g, "ک")
    .replace(/\u200c/g, " ")
    .toLowerCase();

export function searchAll(data, query) {
  const needle = norm(query).trim();
  if (needle.length < 2) return { properties: [], articles: [], projects: [] };
  const hit = (...fields) => fields.some((f) => norm(f).includes(needle));
  return {
    properties: data.properties
      .filter((p) => hit(p.title, p.location?.label, p.excerpt))
      .slice(0, 6)
      .map(toPropertyCard),
    articles: data.articles
      .filter((a) => hit(a.title, a.excerpt, a.category?.name))
      .slice(0, 6)
      .map((a) => ({ id: a.id, title: a.title, excerpt: a.excerpt, url: `/mag/article/?id=${a.id}`, category: a.category })),
    projects: (data.projects || [])
      .filter((x) => hit(x.title, x.excerpt, x.location))
      .slice(0, 6)
      .map((x) => ({ id: x.id, title: x.title, excerpt: x.excerpt, url: `/project/?id=${x.id}`, location: x.location })),
  };
}

export function parseJsonBody(raw) {
  try {
    return JSON.parse(raw || "{}");
  } catch {
    return {};
  }
}

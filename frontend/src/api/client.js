const DEFAULT_BASE = "";
const DEMO = import.meta.env.VITE_DEMO === "1";

async function demo(method, path, params, body, headers) {
  const { demoCall } = await import("./demo.js");
  const res = demoCall(method, path, params, body, headers) || { status: 404, json: {} };
  if (res.status >= 400) {
    const error = new Error(`${method} ${path} ${res.status}`);
    error.payload = res.json;
    throw error;
  }
  return res.json;
}

export function apiBase() {
  const env = import.meta.env.VITE_API_BASE || "";
  return env ? `${env.replace(/\/$/, "")}/wp-json` : DEFAULT_BASE;
}

export async function apiGet(path, params = {}, headers = {}) {
  if (DEMO) return demo("GET", path, params, null, headers);
  const url = new URL(`${apiBase()}${path}`, window.location.origin);
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== "" && value !== null) {
      url.searchParams.set(key, value);
    }
  });
  const res = await fetch(url.toString(), { headers });
  if (!res.ok) throw new Error(`GET ${path} ${res.status}`);
  return res.json();
}

export async function apiPost(path, body, headers = {}) {
  if (DEMO) return demo("POST", path, {}, body, headers);
  const res = await fetch(`${apiBase()}${path}`, {
    method: "POST",
    headers: { "Content-Type": "application/json", ...headers },
    body: JSON.stringify(body),
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) {
    const error = new Error(`POST ${path} ${res.status}`);
    error.payload = json;
    throw error;
  }
  return json;
}

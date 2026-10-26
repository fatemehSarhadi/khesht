const DEFAULT_BASE = "";

export function apiBase() {
  const env = import.meta.env.VITE_API_BASE || "";
  return env ? `${env.replace(/\/$/, "")}/wp-json` : DEFAULT_BASE;
}

export async function apiGet(path, params = {}, headers = {}) {
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

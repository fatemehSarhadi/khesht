// Demo mode (GitHub Pages preview): the sample data and mock API run inside the browser.
import data from "../../mock/data.json";
import { handleKheshtApi } from "../../mock/core.js";

export function demoCall(method, path, params = {}, body = null, headers = {}) {
  const sp = new URLSearchParams();
  Object.entries(params).forEach(([k, v]) => {
    if (v !== undefined && v !== "" && v !== null) sp.set(k, v);
  });
  const lower = {};
  Object.entries(headers).forEach(([k, v]) => (lower[k.toLowerCase()] = v));
  return handleKheshtApi(path, sp, method, body, lower, data);
}

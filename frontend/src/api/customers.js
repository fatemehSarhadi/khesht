import { apiGet, apiPost } from "./client.js";

const KEY = "khesht_session";

export function getSession() {
  try {
    return JSON.parse(localStorage.getItem(KEY) || "null");
  } catch {
    return null;
  }
}
export function setSession(token, name) {
  try {
    localStorage.setItem(KEY, JSON.stringify({ token, name }));
  } catch {
    /* storage unavailable */
  }
}
export function clearSession() {
  try {
    localStorage.removeItem(KEY);
  } catch {
    /* ignore */
  }
}
const auth = () => ({ "X-Khesht-Token": getSession()?.token || "" });

export const registerCustomer = (d) => apiPost("/khesht/v1/customers/register", d);
export const loginCustomer = (d) => apiPost("/khesht/v1/customers/login", d);
export const forgotPassword = (phone) => apiPost("/khesht/v1/customers/forgot", { phone });
export const getMe = () => apiGet("/khesht/v1/customers/me", {}, auth());
export const getReviews = (params) => apiGet("/khesht/v1/reviews", params);
export const postReview = (d) => apiPost("/khesht/v1/reviews", d, auth());

import { apiGet } from "./client.js";

export function listProperties(params) {
  return apiGet("/khesht/v1/properties", params);
}

export function getProperty(slug) {
  return apiGet(`/khesht/v1/properties/${encodeURIComponent(slug)}`);
}

import { apiPost } from "./client.js";

export function submitConsultation(body) {
  return apiPost("/khesht/v1/consultation", body);
}

export function submitCollaboration(body) {
  return apiPost("/khesht/v1/collaboration", body);
}

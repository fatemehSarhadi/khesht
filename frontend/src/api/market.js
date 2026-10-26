import { apiGet } from "./client.js";

export function getMarketQuotes() {
  return apiGet("/khesht/v1/market-quotes");
}

import { apiGet } from "./client.js";

export const getSite = () => apiGet("/khesht/v1/site");
export const getTeam = () => apiGet("/khesht/v1/team");
export const getServices = () => apiGet("/khesht/v1/services");
export const getPartners = () => apiGet("/khesht/v1/partners");
export const getMagazine = (params) => apiGet("/khesht/v1/magazine", params);
export const getArticle = (key) => apiGet(`/khesht/v1/magazine/${encodeURIComponent(key)}`);
export const getAreaPrices = () => apiGet("/khesht/v1/area-prices");
export const getInsights = () => apiGet("/khesht/v1/market-insights");
export const getTaxonomies = () => apiGet("/khesht/v1/taxonomies");
export const getProjects = (params) => apiGet("/khesht/v1/projects", params);
export const searchSite = (q) => apiGet("/khesht/v1/search", { q });
export const getProject = (key) => apiGet(`/khesht/v1/projects/${encodeURIComponent(key)}`);
export const getGallery = () => apiGet("/khesht/v1/gallery");

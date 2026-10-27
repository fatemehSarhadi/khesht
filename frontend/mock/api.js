import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { handleKheshtApi as coreHandler } from "./core.js";

export { filterProperties, toPropertyCard, searchAll, parseJsonBody } from "./core.js";

const mockDir = path.dirname(fileURLToPath(import.meta.url));

export function loadMockData() {
  return JSON.parse(fs.readFileSync(path.join(mockDir, "data.json"), "utf8"));
}

export function handleKheshtApi(pathname, searchParams, method, body, headers = {}) {
  return coreHandler(pathname, searchParams, method, body, headers, loadMockData());
}

import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const partialsDir = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "src/partials");
const INCLUDE = /\{\{include:([a-z0-9-]+)\}\}/gi;

function loadPartial(name) {
  return fs.readFileSync(path.join(partialsDir, `${name}.html`), "utf8");
}

function resolveIncludes(html) {
  // nested includes (e.g. footer -> social), max depth 4
  for (let i = 0; i < 4 && /\{\{include:/.test(html); i++) {
    html = html.replace(INCLUDE, (_, name) => loadPartial(name.trim()));
  }
  return html;
}

export function htmlPartials() {
  return {
    name: "khesht-html-partials",
    // "pre" runs before Vite parses the page, so the <script type="module"> that lives in the footer
    // partial is bundled in production builds (not only served in dev).
    transformIndexHtml: {
      order: "pre",
      handler: resolveIncludes,
    },
  };
}

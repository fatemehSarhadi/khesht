import fs from "node:fs";
import path from "node:path";

function loadPartial(name) {
  const file = path.resolve(process.cwd(), "src/partials", `${name}.html`);
  return fs.readFileSync(file, "utf8");
}

const re = /\{\{include:([a-z0-9-]+)\}\}/gi;

export function htmlPartials() {
  return {
    name: "khesht-html-partials",
    transformIndexHtml(html) {
      // resolve nested includes (e.g. footer -> social), max depth 4
      for (let i = 0; i < 4 && re.test(html); i++) {
        re.lastIndex = 0;
        html = html.replace(re, (_, name) => loadPartial(name.trim()));
      }
      re.lastIndex = 0;
      return html;
    },
  };
}

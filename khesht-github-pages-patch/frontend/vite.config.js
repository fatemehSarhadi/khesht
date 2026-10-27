import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { htmlPartials } from "./vite-plugin-html-partials.js";
import { kheshtDev } from "./vite-plugin-khesht-dev.js";

const root = path.dirname(fileURLToPath(import.meta.url));

function page(rel) {
  return path.resolve(root, rel);
}

export default {
  // GitHub Pages serves project sites from /<repo>/ — the workflow passes VITE_BASE=/<repo>/
  base: process.env.VITE_BASE || "/",
  plugins: [htmlPartials(), kheshtDev()],
  appType: "mpa",
  build: {
    rollupOptions: {
      input: {
        home: page("index.html"),
        amlak: page("amlak/index.html"),
        property: page("amlak/property/index.html"),
        project: page("project/index.html"),
        economic: page("amlak/economic/index.html"),
        ceo: page("ceo/index.html"),
        sakht: page("sakht/index.html"),
        sakhtProjects: page("sakht/projects/index.html"),
        shahrak: page("shahrak/index.html"),
        bazar: page("bazar/index.html"),
        mag: page("mag/index.html"),
        article: page("mag/article/index.html"),
        about: page("about/index.html"),
        card: page("card/index.html"),
        hamkari: page("hamkari/index.html"),
        en: page("en/index.html"),
      },
    },
  },
};

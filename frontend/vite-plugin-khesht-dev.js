import fs from "node:fs";
import { loadEnv } from "vite";
import { fetchNavasanQuotes } from "./mock/market.js";
import { handleKheshtApi, parseJsonBody } from "./mock/api.js";

function sendJson(res, status, json) {
  res.statusCode = status;
  res.setHeader("Content-Type", "application/json; charset=utf-8");
  res.end(JSON.stringify(json));
}

function readBody(req) {
  return new Promise((resolve) => {
    const chunks = [];
    req.on("data", (c) => chunks.push(c));
    req.on("end", () => resolve(Buffer.concat(chunks).toString("utf8")));
  });
}

export function kheshtDev() {
  let env = {};
  return {
    name: "khesht-dev",
    configResolved(config) {
      env = loadEnv(config.mode, config.root, "");
    },
    configureServer(server) {
      server.middlewares.use(async (req, res, next) => {
        const url = new URL(req.url, "http://localhost");
        const method = req.method || "GET";

        if (method === "GET" && url.pathname === "/khesht/v1/market-quotes") {
          try {
            const live = await fetchNavasanQuotes(env);
            if (live) {
              sendJson(res, 200, live);
              return;
            }
          } catch (error) {
            console.warn("[khesht] market quotes:", error.message);
          }
        }

        const api = handleKheshtApi(
          url.pathname,
          url.searchParams,
          method,
          method === "POST" ? parseJsonBody(await readBody(req)) : null,
          req.headers
        );
        if (api) {
          sendJson(res, api.status, api.json);
          return;
        }

        next();
      });
    },
  };
}

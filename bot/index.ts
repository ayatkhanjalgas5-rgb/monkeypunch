import express, { Application } from "express";
import { APP_URL, NODE_ENV, PORT, TELEGRAM_TOKEN, WEBHOOK_URL } from "./constants";
import { Telegraf } from "telegraf";

const app: Application = express();
const bot = new Telegraf(TELEGRAM_TOKEN);
const webhookPath = `/telegram/webhook/${TELEGRAM_TOKEN}`;
const healthPayload = { ok: true, service: "monkeypunch-bot", env: NODE_ENV };

app.disable("x-powered-by");
app.use(express.json({ limit: "1mb" }));
app.use(express.urlencoded({ extended: false }));
app.use(express.static("static"));

app.get("/", (_, res) => {
  res.json({ ...healthPayload, app_url: APP_URL });
});

app.get("/health", (_, res) => {
  res.json(healthPayload);
});

if (WEBHOOK_URL) {
  app.use(webhookPath, bot.webhookCallback(webhookPath));
}

bot.catch((error) => {
  console.error("Telegram bot error:", error);
});

bot.start(async (ctx) => {
  const startPayload =
    typeof ctx.startPayload === "string" ? ctx.startPayload : "";

  const appUrl = startPayload
    ? `${APP_URL}/?ref=${encodeURIComponent(startPayload)}`
    : `${APP_URL}/`;

  await ctx.reply("🐒 Welcome to MonkeyPunch!\n\nTap below to play:");

  await ctx.reply("Quick open:", {
    reply_markup: {
      inline_keyboard: [
        [
          {
            text: "🚀 Open MonkeyPunch",
            web_app: { url: appUrl },
          },
        ],
      ],
    },
  });

  if (startPayload) {
    console.log("Referral start payload:", startPayload);
  }
});

const server = app.listen(PORT, async () => {
  console.log(`Bot server listening on port ${PORT}`);

  try {
    if (WEBHOOK_URL) {
      await bot.telegram.setWebhook(`${WEBHOOK_URL.replace(/\/$/, "")}${webhookPath}`);
      console.log("Telegram webhook enabled");
      return;
    }

    await bot.telegram.deleteWebhook({ drop_pending_updates: false });
    await bot.launch();
    console.log("Telegram long polling enabled");
  } catch (error) {
    console.error("Bot startup failed:", error);
    process.exitCode = 1;
  }
});

const shutdown = async (signal: string) => {
  console.log(`Received ${signal}, shutting down bot...`);
  try {
    if (!WEBHOOK_URL) {
      await bot.stop(signal);
    }
  } catch (error) {
    console.error("Bot shutdown error:", error);
  }

  server.close(() => {
    process.exit(0);
  });
};

process.on("SIGINT", () => void shutdown("SIGINT"));
process.on("SIGTERM", () => void shutdown("SIGTERM"));

export default app;

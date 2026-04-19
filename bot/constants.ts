import dotenv from "dotenv";

dotenv.config();

function requireEnv(name: string): string {
  const value = process.env[name]?.trim();
  if (!value) {
    throw new Error(`Missing required environment variable: ${name}`);
  }
  return value;
}

export const NODE_ENV = process.env.NODE_ENV || "development";
export const PORT = Number(process.env.PORT || 3000);
export const APP_URL = requireEnv("APP_URL").replace(/\/$/, "");
export const TELEGRAM_TOKEN = requireEnv("TELEGRAM_TOKEN");
export const WEBHOOK_URL = process.env.WEBHOOK_URL?.trim() || "";

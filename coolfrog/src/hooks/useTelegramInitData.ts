import { useEffect, useMemo, useState } from "react";
import { TelegramWebApps } from "telegram-webapps-types";

type TelegramInitState = Partial<TelegramWebApps.WebAppInitData> & {
  raw: string;
};

const fakeData: TelegramInitState = {
  user: {
    id: 1,
    is_bot: false,
    first_name: "John",
    last_name: "Doe",
    usernames: "johndoe",
  } as TelegramWebApps.WebAppUser,
  start_param: "ref1",
  auth_date: Math.floor(Date.now() / 1000),
  raw: "",
};

function useTelegramInitData() {
  const [data, setData] = useState<TelegramInitState>({ raw: "" });

  useEffect(() => {
    const raw = window.Telegram?.WebApp?.initData || "";

    const source: Record<string, unknown> =
      import.meta.env.DEV && !raw
        ? { ...fakeData }
        : Object.fromEntries(new URLSearchParams(raw));

    const initData = { raw } as TelegramInitState & Record<string, unknown>;

    for (const key in source) {
      const value = source[key];

      try {
        initData[key] = typeof value === "string" ? JSON.parse(value) : value;
      } catch {
        initData[key] = value;
      }
    }

    if (import.meta.env.DEV && !raw) {
      initData.raw = new URLSearchParams({
        user: JSON.stringify(fakeData.user),
        start_param: fakeData.start_param || "",
        auth_date: String(fakeData.auth_date || ""),
      }).toString();
    }

    setData(initData);
  }, []);

  const isTelegramWebApp = useMemo(() => Boolean(data.user?.id), [data.user]);

  return {
    ...data,
    isTelegramWebApp,
  };
}

export default useTelegramInitData;

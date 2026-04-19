import { RouterProvider } from "react-router-dom";
import router from "./router";
import { useEffect, useRef, useState } from "react";
import SplashScreen from "./components/partials/SplashScreen";
import FirstTimeScreen from "./components/partials/FirstTimeScreen";
import { $http, clearBearerToken, setBearerToken, setTelegramInitData } from "./lib/http";
import { BoosterType, BoosterTypes, UserType } from "./types/UserType";
import { useUserStore } from "./store/user-store";
import { uesStore } from "./store";
import PlayOnYourMobile from "./pages/PlayOnYourMobile";
import { toast } from "react-toastify";
import useTelegramInitData from "./hooks/useTelegramInitData";
import axios from "axios";

const webApp = window.Telegram?.WebApp;
const isDesktop = false;

function App() {
  const tg = useTelegramInitData();
  const [showSplashScreen, setShowSplashScreen] = useState(true);
  const [isFirstLoad, setIsFirstLoad] = useState(false);
  const [fatalError, setFatalError] = useState<string | null>(null);
  const hasInitializedRef = useRef(false);

  useEffect(() => {
    webApp?.setHeaderColor("#000");
    webApp?.setBackgroundColor("#000");
    webApp?.expand();
  }, []);

  useEffect(() => {
    if (hasInitializedRef.current) return;

    if (!tg.user) {
      setShowSplashScreen(false);
      return;
    }

    hasInitializedRef.current = true;
    setTelegramInitData(tg.raw || "");

    const requestAuthToken = async () => {
      const urlRef = new URLSearchParams(window.location.search).get("ref");

const startParam =
  tg.start_param ||
  window.Telegram?.WebApp?.initDataUnsafe?.start_param ||
  urlRef ||
  null;

      const cleanRef =
        startParam && /^\d+$/.test(String(startParam))
          ? Number(startParam)
          : null;

      const { data } = await $http.post<{
        token: string;
        first_login: boolean;
        telegram_verified?: boolean;
        auth_mode?: string;
      }>("/auth/telegram-user", {
        telegram_id: tg.user?.id,
        first_name: tg.user?.first_name,
        last_name: tg.user?.last_name,
        username:
          (tg.user as { username?: string; usernames?: string } | undefined)?.username ??
          (tg.user as { usernames?: string } | undefined)?.usernames ??
          null,
        referred_by: cleanRef,
        init_data: tg.raw,
      });

      setBearerToken(data.token);
      setIsFirstLoad(data.first_login);

      if (data.auth_mode === "local_insecure") {
        toast.info("Local auth mode enabled");
      }
    };

    const syncUser = async () => {
      return await $http.$get<{
        user: UserType;
        boosters: Record<BoosterTypes, BoosterType>;
        total_daily_rewards: number;
        daily_booster: { uses_today: number; next_available_at: string | null };
        max_level: number;
        levels: UserType["level"][];
        level_up: { max_energy: number; earn_per_tap: number };
        referral: {
          base: { welcome: number; levelUp: Record<string, number> };
          premium: { welcome: number; levelUp: Record<string, number> };
        };
        total_referals: number;
      }>("/clicker/sync");
    };

    const bootstrap = async () => {
      try {
        if (!localStorage.getItem("token")) {
          await requestAuthToken();
        }

        try {
          const data = await syncUser();
          useUserStore.setState({ ...data.user });
          uesStore.setState({
            totalDailyRewards: data.total_daily_rewards ?? 0,
            boosters: data.boosters,
            dailyResetEnergy: data.daily_booster,
            maxLevel: data.max_level,
            levels: (data.levels as never) || [],
            levelUp: data.level_up,
            referral: data.referral,
            totalReferals: data.total_referals ?? 0,
          });
          return;
        } catch (syncError) {
          if (axios.isAxiosError(syncError) && syncError.response?.status === 401) {
            clearBearerToken();
            await requestAuthToken();
            const retryData = await syncUser();
            useUserStore.setState({ ...retryData.user });
            uesStore.setState({
              totalDailyRewards: retryData.total_daily_rewards ?? 0,
              boosters: retryData.boosters,
              dailyResetEnergy: retryData.daily_booster,
              maxLevel: retryData.max_level,
              levels: (retryData.levels as never) || [],
              levelUp: retryData.level_up,
              referral: retryData.referral,
              totalReferals: retryData.total_referals ?? 0,
            });
            return;
          }

          throw syncError;
        }
      } catch (error) {
        setFatalError(
          axios.isAxiosError(error)
            ? error.response?.data?.message || error.message
            : "App bootstrap failed."
        );
      } finally {
        setShowSplashScreen(false);
      }
    };

    bootstrap();
  }, [tg.raw, tg.start_param, tg.user]);

  if (!tg.user || isDesktop) return <PlayOnYourMobile />;

  if (showSplashScreen) {
    return <SplashScreen />;
  }

  if (fatalError) {
    return (
      <div className="flex min-h-screen flex-col items-center justify-center bg-black px-6 text-center text-white">
        <div className="max-w-md rounded-[28px] border border-white/10 bg-white/5 p-6">
          <p className="text-xs uppercase tracking-[0.3em] text-[#ffc266]">Production mode</p>
          <h1 className="mt-2 text-2xl font-black">Unable to load MonkeyPunch</h1>
          <p className="mt-3 text-sm text-white/70">{fatalError}</p>
          <button
            className="mt-5 rounded-full bg-[#ff8a1f] px-5 py-3 font-bold text-black"
            onClick={() => window.location.reload()}
          >
            Retry
          </button>
        </div>
      </div>
    );
  }

  if (isFirstLoad) {
    return <FirstTimeScreen startGame={() => setIsFirstLoad(false)} />;
  }

  return <RouterProvider router={router} />;
}

export default App;

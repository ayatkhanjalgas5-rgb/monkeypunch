import { Button } from "@/components/ui/button";
import Drawer from "@/components/ui/drawer";
import { $http } from "@/lib/http";
import { cn, compactNumber } from "@/lib/utils";
import { BoosterTypes } from "@/types/UserType";
import { useMutation } from "@tanstack/react-query";
import { useMemo, useState } from "react";
import { toast } from "react-toastify";
import { Loader2Icon } from "lucide-react";
import { useUserStore } from "@/store/user-store";
import Price from "@/components/Price";
import { uesStore } from "@/store";
import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";

dayjs.extend(relativeTime);

const boosterDetails: Record<
  BoosterTypes,
  {
    title: string;
    description: string;
    shortDescription?: string;
    image: string;
  }
> = {
  multi_tap: {
    title: "Multitap",
    description: "Increase the amount of coins you can earn per tap",
    shortDescription: "coins for tap",
    image: "/images/coin-2.png",
  },
  energy_limit: {
    title: "Energy limit",
    description: "Increase the amount of energy",
    shortDescription: "energy points",
    image: "/images/bolt.png",
  },
  full_energy: {
    title: "Full energy",
    description: "Recharge your energy to the maximum",
    image: "/images/extra-pewer.png",
  },
};

export default function Boost() {
  const [open, setOpen] = useState(false);
  const [activeBooster, setActiveBooster] = useState<BoosterTypes>("multi_tap");
  const { boosters, dailyResetEnergy, maxDailyResetEnergy } = uesStore();
  const { balance } = useUserStore();

  const canUseDailyResetEnergy = useMemo(
    () => dayjs().isAfter(dailyResetEnergy.next_available_at),
    [dailyResetEnergy.next_available_at]
  );

  const insufficientBalance = useMemo(() => {
    if (activeBooster === "full_energy") return false;
    if (!boosters?.[activeBooster]?.cost) return false;
    return balance < boosters[activeBooster].cost;
  }, [balance, boosters, activeBooster]);

  const buyBoost = useMutation({
    mutationFn: (boost: BoosterTypes) =>
      boost !== "full_energy"
        ? $http.post("/clicker/buy-booster", { booster_type: boost })
        : $http.post("/clicker/use-daily-booster"),

    onSuccess: async (response) => {
      toast.success(response.data.message);
      setOpen(false);

      try {
        if (activeBooster !== "full_energy") {
          const syncResponse = await $http.get("/clicker/sync");
          const syncData = syncResponse.data;

          useUserStore.setState((state) => ({
            ...state,
            balance: syncData.user?.balance ?? state.balance,
            earn_per_tap: syncData.user?.earn_per_tap ?? state.earn_per_tap,
            max_energy: syncData.user?.max_energy ?? state.max_energy,
            available_energy: syncData.user?.available_energy ?? state.available_energy,
            level: syncData.user?.level ?? state.level,
            multi_tap_level: syncData.user?.multi_tap_level ?? state.multi_tap_level,
            energy_limit_level: syncData.user?.energy_limit_level ?? state.energy_limit_level,
          }));

          uesStore.setState((state) => ({
            ...state,
            boosters: syncData.boosters ?? state.boosters,
            totalDailyRewards: syncData.total_daily_rewards ?? state.totalDailyRewards,
            totalReferals: syncData.total_referals ?? state.totalReferals,
            referral: syncData.referral ?? state.referral,
          }));
        } else {
          useUserStore.setState((state) => ({
            ...state,
            available_energy: response.data.current_energy ?? state.available_energy,
          }));

          uesStore.setState((state) => ({
            ...state,
            dailyResetEnergy: {
              ...state.dailyResetEnergy,
              uses_today: response.data.daily_booster_uses,
              next_available_at: response.data.next_available_at,
            },
          }));
        }
      } catch (syncError) {
        console.error("Boost sync failed:", syncError);
        toast.error("Booster updated, but refresh data failed");
      }
    },

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    onError: (error: any) =>
      toast.error(error?.response?.data?.message || "Something went wrong"),
  });

  return (
    <div className="flex flex-col justify-end bg-[url('/images/bg.png')] bg-cover flex-1">
      <div className="min-h-[600px] w-full modal-body py-8 px-6">
        <h1 className="text-2xl font-bold text-center uppercase">
          Boost your game
        </h1>

        <p className="mt-8 text-sm font-bold text-center uppercase">
          Free Daily Booster
        </p>

        <div className="mt-4">
          <button
            className="flex items-center w-full gap-4 px-4 py-2 bg-white/10 rounded-xl"
            onClick={() => {
              setOpen(true);
              setActiveBooster("full_energy");
            }}
            disabled={!canUseDailyResetEnergy}
          >
            <img
              src="/images/extra-pewer.png"
              alt="extra-power"
              className="object-contain w-9 h-9 mix-blend-screen"
            />
            <div className="text-sm font-medium text-left">
              <p>Full energy</p>
              <p className={cn({ "text-white/80": !canUseDailyResetEnergy })}>
                {maxDailyResetEnergy - dailyResetEnergy.uses_today}/{maxDailyResetEnergy} available
              </p>
            </div>

            {!canUseDailyResetEnergy && (
              <div className="self-end h-full ml-auto text-sm text-white/80">
                <span>
                  {dayjs(dailyResetEnergy.next_available_at).fromNow(true)} left
                </span>
              </div>
            )}
          </button>
        </div>

        <p className="mt-8 text-sm font-bold text-center uppercase">Boosters</p>

        <div className="mt-4 space-y-2">
          <button
            className="flex items-center w-full gap-4 px-4 py-2 bg-white/10 rounded-xl"
            onClick={() => {
              setOpen(true);
              setActiveBooster("multi_tap");
            }}
          >
            <img
              src="/images/coin-2.png"
              alt="coin-2"
              className="object-contain w-9 h-9 mix-blend-screen"
            />
            <div className="text-sm font-medium text-left">
              <p>Multi tap</p>
              <div className="flex items-center space-x-1">
                <img
                  src="/images/coin.png"
                  alt="coin"
                  className="object-contain w-5 h-5"
                />
                <span className="font-bold">
                  {compactNumber(boosters.multi_tap.cost)}
                </span>
                <span className="text-sm">{boosters.multi_tap.level} LVL</span>
              </div>
            </div>
          </button>

          <button
            className="flex items-center w-full gap-4 px-4 py-2 bg-white/10 rounded-xl"
            onClick={() => {
              setOpen(true);
              setActiveBooster("energy_limit");
            }}
          >
            <img
              src="/images/bolt.png"
              alt="bolt"
              className="object-contain w-9 h-9 mix-blend-screen"
            />
            <div className="text-sm font-medium text-left">
              <p>Energy limit</p>
              <div className="flex items-center space-x-1">
                <img
                  src="/images/coin.png"
                  alt="coin"
                  className="object-contain w-5 h-5"
                />
                <span className="font-bold">
                  {compactNumber(boosters.energy_limit.cost)}
                </span>
                <span className="text-sm">{boosters.energy_limit.level} LVL</span>
              </div>
            </div>
          </button>
        </div>
      </div>

      <Drawer open={open} onOpenChange={setOpen}>
        <div className="relative space-y-6">
          <h4 className="text-2xl font-bold text-center">
            {boosterDetails[activeBooster].title}
          </h4>

          <img
            src={boosterDetails[activeBooster].image}
            alt={activeBooster}
            className="object-contain w-24 h-24 mx-auto"
          />

          <div className="text-sm font-medium text-center">
            <p>{boosterDetails[activeBooster].description}</p>

            {activeBooster !== "full_energy" && (
              <p className="mt-2">
                +{compactNumber(boosters[activeBooster].increase_by)}{" "}
                {boosterDetails[activeBooster].shortDescription} for level{" "}
                {boosters[activeBooster].level}
              </p>
            )}
          </div>

          <div className="flex items-center justify-center gap-1">
            <Price
              className="text-lg text-white"
              amount={
                activeBooster !== "full_energy"
                  ? boosters[activeBooster].cost.toLocaleString()
                  : "Free"
              }
            />
            {activeBooster !== "full_energy" && (
              <span className="text-gray-500">
                • {boosters[activeBooster].level} lvl
              </span>
            )}
          </div>

          <Button
            className="w-full"
            onClick={() => buyBoost.mutate(activeBooster)}
            disabled={buyBoost.isPending || insufficientBalance}
          >
            {buyBoost.isPending && (
              <Loader2Icon className="w-5 h-5 mr-2 animate-spin" />
            )}
            {insufficientBalance ? "Insufficient Balance" : "Go Ahead"}
          </Button>
        </div>
      </Drawer>
    </div>
  );
}

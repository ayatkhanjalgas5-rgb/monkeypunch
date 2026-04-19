import React, { useEffect, useRef, useState } from "react";
import { useClicksStore } from "../store/clicks-store";
import { useUserStore } from "../store/user-store";
import { Link } from "react-router-dom";
import { useDebounce } from "@uidotdev/usehooks";
import { $http } from "@/lib/http";
import levelConfig from "@/config/level-config";
import { Zap } from "lucide-react";

export default function UserTap(props: React.HTMLProps<HTMLDivElement>) {
  const userAnimateRef = useRef<HTMLDivElement | null>(null);
  const userTapButtonRef = useRef<HTMLButtonElement | null>(null);
  const [clicksCount, setClicksCount] = useState(0);
  const debounceClicksCount = useDebounce(clicksCount, 200);
  const { clicks, addClick, removeClick, clearOldClicks } = useClicksStore();
  const { UserTap, incraseEnergy, ...user } = useUserStore();

const tabMe = (e: React.MouseEvent) => {
  e.preventDefault();
  if (!UserTap()) return;

  clearOldClicks();
  setClicksCount((prev) => prev + 1);

  const clickId =
    typeof crypto !== "undefined" && "randomUUID" in crypto
      ? crypto.randomUUID()
      : `${Date.now()}-${Math.random()}`;

  addClick({
    id: clickId,
    value: user.earn_per_tap,
    style: {
      top: e.clientY,
      left: e.clientX + (Math.random() > 0.5 ? 5 : -5),
    },
  });

  setTimeout(() => {
    removeClick(clickId);
  }, 700);

  animateButton();
};
  const animateButton = () => {
    if (!userTapButtonRef.current) return;

    Telegram.WebApp.HapticFeedback.impactOccurred("medium");

    userTapButtonRef.current.classList.add("scale-95");
    setTimeout(() => {
      userTapButtonRef.current?.classList.remove("scale-95");
    }, 150);
  };

  useEffect(() => {
    if (debounceClicksCount === 0) return;

    const count = debounceClicksCount;
    setClicksCount(0);

    const sendTap = async () => {
      try {
        const response = await $http.post("/clicker/tap", {
          count,
        });

        const data = response.data as {
          balance?: number;
          available_energy?: number;
          level?: typeof user.level;
          earn_per_tap?: number;
          max_energy?: number;
        };

        useUserStore.setState((state) => ({
          balance: data.balance ?? state.balance,
          available_energy: data.available_energy ?? state.available_energy,
          level: data.level ?? state.level,
          earn_per_tap: data.earn_per_tap ?? state.earn_per_tap,
          max_energy: data.max_energy ?? state.max_energy,
        }));
      } catch (err) {
        console.error("Tap error:", err);
        setClicksCount((prev) => prev + count);
      }
    };

    sendTap();
  }, [debounceClicksCount, user.level]);

useEffect(() => {
  const interval = setInterval(() => {
    clearOldClicks();
  }, 300);

  return () => clearInterval(interval);
}, [clearOldClicks]);

  useEffect(() => {
    useClicksStore.setState({ clicks: [] });

    const interval = setInterval(() => {
      incraseEnergy(3);
    }, 3000);

    return () => clearInterval(interval);
  }, [incraseEnergy]);

  const energyPercent = user.max_energy
    ? Math.min((user.available_energy / user.max_energy) * 100, 100)
    : 0;

  return (
    <div {...props}>
      <div className="relative mt-6 flex justify-center">
        <div className="absolute inset-x-10 top-8 h-56 rounded-full bg-[#ff5f1b33] blur-3xl" />
        <button
          ref={userTapButtonRef}
          className="relative flex h-[290px] w-[290px] items-center justify-center rounded-full border border-[#ff8e3c66] bg-[radial-gradient(circle_at_top,_rgba(255,190,88,0.78),_rgba(133,27,19,0.96)_38%,_rgba(34,5,5,0.99)_70%)] p-4 shadow-[0_0_0_6px_rgba(255,120,40,0.18),0_20px_60px_rgba(0,0,0,0.38)] transition-all disabled:cursor-not-allowed disabled:opacity-80"
          disabled={user.available_energy < 1}
          onPointerUp={tabMe}
        >
          <div className="absolute inset-3 rounded-full border border-white/10" />
          <img
            src={levelConfig.frogs[user.level?.level || 1]}
            alt="level image"
            className="relative z-10 h-64 w-64 object-contain"
            style={{ filter: levelConfig.filter[user.level?.level || 1] }}
          />
        </button>
      </div>

      <div ref={userAnimateRef} className="user-tap-animate" />
      <div className="mt-5 rounded-[22px] border border-[#ff8f3a22] bg-[#3a0d0acc] px-4 py-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.06)]">
        <div className="flex items-center justify-between gap-3 text-sm">
          <div className="flex items-center gap-2 font-semibold">
            <Zap className="h-5 w-5 text-[#ffb11f]" />
            <span>
              {user.available_energy} / {user.max_energy}
            </span>
          </div>
          <Link
            to="/boost"
            className="rounded-full border border-[#ff9e4744] bg-[#7e1c12] px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] text-white"
          >
            Boost
          </Link>
        </div>
        <div className="mt-3 h-3 overflow-hidden rounded-full bg-white/10">
          <div
            className="h-full rounded-full bg-[linear-gradient(90deg,#ff8c22_0%,#ff9b10_100%)]"
            style={{ width: `${energyPercent}%` }}
          />
        </div>
      </div>

      <div className="user-tap-animate">
        {clicks.map((click) => (
          <div
            key={click.id}
            onAnimationEnd={() => removeClick(click.id)}
            style={click.style}
          >
            +{click.value}
          </div>
        ))}
      </div>
    </div>
  );
}

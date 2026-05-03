import React, { useEffect, useMemo, useRef, useState } from "react";
import { useClicksStore } from "../store/clicks-store";
import { useUserStore } from "../store/user-store";
import { Link } from "react-router-dom";
import { useDebounce } from "@uidotdev/usehooks";
import { $http } from "@/lib/http";
import levelConfig from "@/config/level-config";
import { Crown, Sparkles, Zap } from "lucide-react";

type RarityTier = {
  label: string;
  levelKey: number;
  glowClass: string;
  ringClass: string;
  badgeClass: string;
  particleClass: string;
};

export default function UserTap(props: React.HTMLProps<HTMLDivElement>) {
  const userAnimateRef = useRef<HTMLDivElement | null>(null);
  const userTapButtonRef = useRef<HTMLButtonElement | null>(null);
  const [clicksCount, setClicksCount] = useState(0);
  const debounceClicksCount = useDebounce(clicksCount, 200);
  const { clicks, addClick, removeClick, clearOldClicks } = useClicksStore();
  const { UserTap, incraseEnergy, ...user } = useUserStore();

  const currentLevel = Math.max(1, Number(user.level?.level || 1));

  const rarity = useMemo<RarityTier>(() => {
    if (currentLevel >= 9) {
      return {
        label: "Legendary",
        levelKey: 5,
        glowClass:
          "shadow-[0_0_0_8px_rgba(255,160,64,0.20),0_0_60px_rgba(255,120,40,0.42),0_0_120px_rgba(255,80,20,0.22)]",
        ringClass: "border-[#ffca6b66]",
        badgeClass:
          "border border-[#ffd36b66] bg-gradient-to-r from-[#7a1a0f] to-[#b93f11] text-[#ffe7a6]",
        particleClass: "bg-[#ffb347]/25",
      };
    }

    if (currentLevel >= 7) {
      return {
        label: "Mythic",
        levelKey: 4,
        glowClass:
          "shadow-[0_0_0_8px_rgba(255,120,40,0.18),0_0_52px_rgba(255,90,30,0.34),0_0_96px_rgba(255,60,20,0.16)]",
        ringClass: "border-[#ff9b5d55]",
        badgeClass:
          "border border-[#ff9f5d55] bg-gradient-to-r from-[#65120d] to-[#922615] text-[#ffd0b0]",
        particleClass: "bg-[#ff8a47]/20",
      };
    }

    if (currentLevel >= 5) {
      return {
        label: "Epic",
        levelKey: 3,
        glowClass:
          "shadow-[0_0_0_8px_rgba(255,115,35,0.16),0_0_42px_rgba(255,100,40,0.26)]",
        ringClass: "border-[#ff874955]",
        badgeClass:
          "border border-[#ff8f4d44] bg-gradient-to-r from-[#5b120d] to-[#7b1b12] text-[#ffc9a8]",
        particleClass: "bg-[#ff7c3f]/20",
      };
    }

    if (currentLevel >= 3) {
      return {
        label: "Rare",
        levelKey: 2,
        glowClass:
          "shadow-[0_0_0_7px_rgba(255,110,35,0.14),0_0_28px_rgba(255,92,40,0.18)]",
        ringClass: "border-[#ff7b4250]",
        badgeClass:
          "border border-[#ff7e4440] bg-gradient-to-r from-[#49110b] to-[#68160f] text-[#ffb89a]",
        particleClass: "bg-[#ff7233]/15",
      };
    }

    return {
      label: "Common",
      levelKey: 1,
      glowClass:
        "shadow-[0_0_0_6px_rgba(255,120,40,0.18),0_20px_60px_rgba(0,0,0,0.38)]",
      ringClass: "border-[#ff8e3c66]",
      badgeClass:
        "border border-[#ff8f3a33] bg-[#4a150f]/90 text-[#ffd2b2]",
      particleClass: "bg-[#ff8a47]/10",
    };
  }, [currentLevel]);

  const levelImage =
    levelConfig?.frogs?.[rarity.levelKey] || "/images/levels/Frog-5.png";

  const levelFilter =
    levelConfig?.filter?.[rarity.levelKey] ||
    "drop-shadow(0 0 64px rgba(255,140,60,0.45))";

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
  }, [debounceClicksCount]);

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
    <div className="relative mt-3 min-[430px]:mt-6 flex justify-center">
        <div
          className={`absolute inset-x-10 top-8 h-56 rounded-full blur-3xl ${rarity.particleClass} ${
            currentLevel >= 7 ? "animate-pulse" : ""
          }`}
        />
        <div
          className={`absolute left-1/2 top-10 h-52 w-52 -translate-x-1/2 rounded-full bg-[#ff6b1a20] blur-[80px] ${
            currentLevel >= 9 ? "animate-pulse" : ""
          }`}
        />

        <button
          ref={userTapButtonRef}
          className={`relative flex h-[245px] w-[245px] min-[430px]:h-[290px] min-[430px]:w-[290px] items-center justify-center rounded-full border bg-[radial-gradient(circle_at_top,_rgba(255,190,88,0.78),_rgba(133,27,19,0.96)_38%,_rgba(34,5,5,0.99)_70%)] p-4 transition-all disabled:cursor-not-allowed disabled:opacity-80 ${rarity.ringClass} ${rarity.glowClass}`}
          disabled={user.available_energy < 1}
          onPointerUp={tabMe}
        >
          <div className="absolute inset-3 rounded-full border border-white/10" />
          <div className="absolute inset-6 rounded-full border border-white/5" />

          <div className="absolute left-1/2 top-3 z-20 -translate-x-1/2">
            <div
              className={`flex items-center gap-1 rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.2em] ${rarity.badgeClass}`}
            >
              {currentLevel >= 9 ? (
                <Crown className="h-3.5 w-3.5" />
              ) : (
                <Sparkles className="h-3.5 w-3.5" />
              )}
              <span>{rarity.label}</span>
            </div>
          </div>

          <div className="absolute inset-0 rounded-full bg-[radial-gradient(circle,rgba(255,255,255,0.08)_0%,rgba(255,255,255,0.03)_35%,rgba(255,255,255,0)_70%)]" />

          <img
            src={levelImage}
            alt="level image"
            className={`relative z-10 object-contain transition-all duration-300 ${
            currentLevel >= 9
  ? "h-[225px] w-[225px] min-[430px]:h-[270px] min-[430px]:w-[270px]"
  : currentLevel >= 7
  ? "h-[222px] w-[222px] min-[430px]:h-[266px] min-[430px]:w-[266px]"
  : currentLevel >= 5
  ? "h-[218px] w-[218px] min-[430px]:h-[262px] min-[430px]:w-[262px]"
  : "h-[216px] w-[216px] min-[430px]:h-64 min-[430px]:w-64"
            }`}
            style={{ filter: levelFilter }}
            onError={(e) => {
              e.currentTarget.src = "/images/levels/Frog-5.png";
            }}
          />

          {currentLevel >= 7 && (
            <>
              <span className="absolute left-7 top-16 h-2.5 w-2.5 rounded-full bg-[#ffd38a] opacity-70 blur-[1px]" />
              <span className="absolute right-10 top-20 h-2 w-2 rounded-full bg-[#ffb35c] opacity-60 blur-[1px]" />
              <span className="absolute bottom-16 left-10 h-2 w-2 rounded-full bg-[#ff9447] opacity-60 blur-[1px]" />
            </>
          )}

          {currentLevel >= 9 && (
            <>
              <span className="absolute left-11 top-24 h-3 w-3 rounded-full bg-[#ffe0a0] opacity-80 blur-[1px] animate-pulse" />
              <span className="absolute right-12 top-28 h-3 w-3 rounded-full bg-[#ffb94d] opacity-70 blur-[1px] animate-pulse" />
              <span className="absolute bottom-20 right-14 h-2.5 w-2.5 rounded-full bg-[#ff8d3a] opacity-70 blur-[1px] animate-pulse" />
            </>
          )}
        </button>
      </div>

      <div ref={userAnimateRef} className="user-tap-animate" />

     <div className="mt-3 min-[430px]:mt-5 rounded-[22px]  border border-[#ff8f3a22] bg-[#3a0d0acc] px-4 py-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.06)]">
        <div className="flex items-center justify-between gap-3 text-sm">
          <div className="flex items-center gap-2 font-semibold">
            <Zap className="h-5 w-5 text-[#ffb11f]" />
            <span>
              {user.available_energy} / {user.max_energy}
            </span>
          </div>

          <Link
            to="/boost"
            className="rounded-full border border-[#ff9e4744] bg-[#7e1c12] px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] text-white transition hover:scale-[1.02]"
          >
            Boost
          </Link>
        </div>

        <div className="mt-3 h-3 overflow-hidden rounded-full bg-white/10">
          <div
            className="h-full rounded-full bg-[linear-gradient(90deg,#ff8c22_0%,#ff9b10_100%)] transition-all duration-300"
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

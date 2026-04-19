import { $http } from "@/lib/http";
import { useUserStore } from "@/store/user-store";
import axios from "axios";
import {
  ChevronLeft,
  Clock3,
  Coins,
  Crown,
  Flag,
  Flame,
  Heart,
  TimerReset,
  Trophy,
  Zap,
} from "lucide-react";
import { useEffect, useState } from "react";

type RoomTier = "bronze" | "silver" | "gold";

type BattleRoom = {
  id: number;
  status: "matched" | "countdown" | "active" | "finished" | "searching";
  mode?: "bot" | "pvp";
  is_bot?: boolean;
  room_tier?: RoomTier;
  stake_amount?: number;
  opponent_name: string;
  player_score: number;
  bot_score: number;
  my_score?: number;
  opponent_score?: number;
  duration_seconds: number;
  started_at: string | null;
  ends_at: string | null;
  finished_at: string | null;
  result: "win" | "lose" | "draw" | "cancelled" | null;
  reward: number;
  winner_reward?: number;
  loser_reward?: number;
  fee_amount?: number;
  support_spent: number;
  my_support_spent?: number;
  opponent_support_spent?: number;
  countdown_remaining?: number;
  remaining_seconds?: number;
};

type BattlePayload = {
  success: boolean;
  room?: BattleRoom;
  searching?: boolean;
  matched?: boolean;
  expires_at?: string;
  queue?: {
    room_tier: RoomTier;
    stake_amount: number;
  };
  user?: {
    balance: number;
    available_energy: number;
  };
  message?: string;
};

type ScreenMode = "short" | "medium" | "tall";

type FloatingHit = {
  id: number;
  value: number;
  critical?: boolean;
  combo?: number;
};

const roomOptions: Array<{
  tier: RoomTier;
  label: string;
  stake: number;
  subtitle: string;
}> = [
  { tier: "bronze", label: "Bronze Arena", stake: 500, subtitle: "Fast, low-risk duels" },
  { tier: "silver", label: "Silver Arena", stake: 1000, subtitle: "Balanced arena room" },
  { tier: "gold", label: "Gold Arena", stake: 5000, subtitle: "High pressure, high reward" },
];

const supportOptions = [
  { amount: 100, label: "+100", icon: Heart, accent: "Support" },
  { amount: 500, label: "+500", icon: Flame, accent: "Burst" },
  { amount: 2000, label: "+2000", icon: Crown, accent: "All in" },
] as const;

const formatTime = (seconds: number) => {
  const safe = Math.max(0, seconds);
  const mins = Math.floor(safe / 60);
  const secs = safe % 60;
  return `${String(mins).padStart(2, "0")}:${String(secs).padStart(2, "0")}`;
};

const resultTone = (result: BattleRoom["result"]) => {
  switch (result) {
    case "win":
      return "text-[#ffcf73]";
    case "lose":
      return "text-[#ff9f6b]";
    case "draw":
      return "text-[#ffd9a3]";
    default:
      return "text-white";
  }
};

const resultLabel = (result: BattleRoom["result"]) => {
  switch (result) {
    case "win":
      return "VICTORY!";
    case "lose":
      return "DEFEAT!";
    case "draw":
      return "DRAW!";
    case "cancelled":
      return "CANCELLED";
    default:
      return "FINISHED";
  }
};

const resultSubLabel = (result: BattleRoom["result"]) => {
  switch (result) {
    case "win":
      return "You Won!";
    case "lose":
      return "You Lost!";
    case "draw":
      return "Nobody won this duel.";
    case "cancelled":
      return "The duel ended early.";
    default:
      return "Battle finished.";
  }
};

const tierGlow: Record<RoomTier, string> = {
  bronze:
    "border-[#a64d1f]/80 bg-[linear-gradient(180deg,rgba(85,22,8,0.96),rgba(31,7,3,0.96))]",
  silver:
    "border-[#b65a22]/80 bg-[linear-gradient(180deg,rgba(88,25,9,0.97),rgba(28,8,4,0.97))]",
  gold: "border-[#db7a2f]/80 bg-[linear-gradient(180deg,rgba(95,29,9,0.98),rgba(31,9,4,0.98))]",
};

const arenaFrameGlow: Record<RoomTier, string> = {
  bronze: "shadow-[0_0_0_1px_rgba(194,99,45,0.16),0_0_24px_rgba(194,99,45,0.12)]",
  silver: "shadow-[0_0_0_1px_rgba(219,115,49,0.18),0_0_28px_rgba(219,115,49,0.14)]",
  gold: "shadow-[0_0_0_1px_rgba(255,160,71,0.22),0_0_34px_rgba(255,160,71,0.16)]",
};

const cls = (...parts: Array<string | false | null | undefined>) => parts.filter(Boolean).join(" ");

const getScreenMode = (): ScreenMode => {
  if (typeof window === "undefined") return "medium";
  const h = window.innerHeight;
  if (h <= 760) return "short";
  if (h <= 900) return "medium";
  return "tall";
};

export default function Battle() {
  const user = useUserStore();

  const [screenMode, setScreenMode] = useState<ScreenMode>(getScreenMode());
  const [selectedTier, setSelectedTier] = useState<RoomTier>("silver");
  const [room, setRoom] = useState<BattleRoom | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [searchExpiresAt, setSearchExpiresAt] = useState<string | null>(null);
  const [searchRemaining, setSearchRemaining] = useState(0);
  const [remaining, setRemaining] = useState(0);
  const [isPunching, setIsPunching] = useState(false);
  const [isSupporting, setIsSupporting] = useState<number | null>(null);
  const [floatingHits, setFloatingHits] = useState<FloatingHit[]>([]);
  const [shake, setShake] = useState(false);
  const [critFlash, setCritFlash] = useState(false);
  const [combo, setCombo] = useState(0);
  const [comboVisible, setComboVisible] = useState(false);
  const [lastTapAt, setLastTapAt] = useState(0);
  const [rageMode, setRageMode] = useState(false);
  const [resultBoom, setResultBoom] = useState(false);

  const isSearching = room?.status === "searching";
  const isCountdown = room?.status === "countdown";
  const isActive = room?.status === "active";
  const isFinished = room?.status === "finished";
  const isIdle = !room;
  const isShort = screenMode === "short";
  const isMedium = screenMode === "medium";

  const currentMyScore = room?.my_score ?? room?.player_score ?? 0;
  const currentOpponentScore = room?.opponent_score ?? room?.bot_score ?? 0;
  const currentStake =
    room?.stake_amount ?? roomOptions.find((option) => option.tier === selectedTier)?.stake ?? 1000;

  const progress = (() => {
    const total = currentMyScore + currentOpponentScore;
    if (total <= 0) return 50;
    return Math.max(8, Math.min(92, (currentMyScore / total) * 100));
  })();

  const myDefense = Math.max(0, (user.available_energy ?? 0) - (room?.my_support_spent ?? 0));
  const opponentDefense = Math.max(
    0,
    (user.available_energy ?? 0) - (room?.opponent_support_spent ?? 0)
  );

  const ui = {
    outerPx: isShort ? "px-2.5" : "px-3",
    sectionGap: isShort ? "mb-3" : isMedium ? "mb-3.5" : "mb-4",
    topPad: isShort ? "pt-[max(8px,env(safe-area-inset-top))]" : "pt-[max(12px,env(safe-area-inset-top))]",
    bottomPad: isShort
      ? "pb-[calc(108px+env(safe-area-inset-bottom))]"
      : "pb-[calc(120px+env(safe-area-inset-bottom))]",
    balanceRow: isShort ? "px-3 py-2.5" : "px-4 py-3",
    balanceLabel: isShort ? "text-[9px] tracking-[0.16em]" : "text-[10px] tracking-[0.18em]",
    balanceValue: isShort ? "text-[18px]" : isMedium ? "text-[20px]" : "text-[22px]",
    balanceTier: isShort ? "px-3 py-1.5 text-[11px]" : "px-4 py-2 text-[12px]",
    tierWrap: isShort ? "rounded-[20px] p-2" : "rounded-[24px] p-2.5",
    tierGap: isShort ? "gap-1.5" : "gap-2",
    tierCard: isShort ? "rounded-[15px] px-2 py-2.5" : "rounded-[18px] px-2.5 py-3",
    tierTitle: isShort ? "text-[12px]" : "text-[14px]",
    tierStake: isShort ? "mt-1.5 text-[13px]" : "mt-2 text-[15px]",
    tierSub: isShort ? "mt-1 text-[9px] leading-3" : "mt-1 text-[10px] leading-4",

    heroWrap: isShort ? "rounded-[20px]" : "rounded-[24px]",
    heroGrid: isShort ? "grid-cols-[0.6fr_1.4fr]" : "grid-cols-[0.7fr_1.3fr]",
    heroLeftMin: isShort ? "min-h-[150px]" : isMedium ? "min-h-[170px]" : "min-h-[190px]",
    heroLeftPad: isShort ? "p-2" : "p-3",
    heroMonkeyBox: isShort ? "h-[92px] rounded-[16px]" : isMedium ? "h-[104px] rounded-[18px]" : "h-[120px] rounded-[20px]",
    heroMonkeyEmoji: isShort ? "text-[40px]" : isMedium ? "text-[46px]" : "text-[54px]",
    heroMonkeySub: isShort ? "text-[8px] tracking-[0.16em]" : "text-[9px] tracking-[0.22em]",
    heroRightPad: isShort ? "p-3" : "p-4",
    heroLabel: isShort ? "text-[9px] tracking-[0.16em]" : "text-[10px] tracking-[0.22em]",
    heroTitle: isShort ? "text-[20px]" : isMedium ? "text-[23px]" : "text-[26px]",
    heroSub: isShort ? "text-[11px] leading-4" : "text-[13px] leading-5",
    heroRows: isShort ? "mt-3 space-y-1.5" : "mt-4 space-y-2",
    heroRowPad: isShort ? "px-2.5 py-2" : "px-3 py-2.5",
    heroRowLabel: isShort ? "text-[10px] tracking-[0.08em]" : "text-[11px] tracking-[0.12em]",
    heroRowValue: isShort ? "text-[12px]" : "text-[14px]",
    cta: isShort ? "mt-3 rounded-[14px] px-3 py-2.5 text-[13px]" : "mt-4 rounded-[18px] px-4 py-3 text-[15px]",

    infoGridGap: isShort ? "gap-2" : "gap-2.5",
    infoCard: isShort ? "rounded-[16px] p-3" : "rounded-[20px] p-4",
    infoTitle: isShort ? "text-[11px] tracking-[0.06em]" : "text-[12px] tracking-[0.08em]",
    infoBody: isShort ? "text-[11px] leading-4" : "text-[13px] leading-5",

    queueWrap: isShort ? "mt-3 rounded-[20px] p-3" : "mt-4 rounded-[24px] p-4",
    queueIcon: isShort ? "h-8 w-8" : "h-10 w-10",
    queueIconSvg: isShort ? "h-3.5 w-3.5" : "h-4 w-4",
    queueBadge: isShort ? "text-[10px] tracking-[0.18em]" : "text-[12px] tracking-[0.24em]",
    queueChip: isShort ? "px-3 py-1 text-[11px]" : "px-4 py-1.5 text-[13px]",
    queueVs: isShort ? "text-[34px]" : "text-[48px]",
    queueReady: isShort ? "px-3 py-1 text-[11px]" : "px-4 py-1.5 text-[13px]",
    queueTimerCircle: isShort ? "mt-2 h-12 w-12 text-[18px]" : "mt-3 h-16 w-16 text-[26px]",
    queueCards: isShort ? "rounded-[16px] px-2 py-3" : "rounded-[20px] px-3 py-4",
    queueAvatar: isShort ? "h-12 w-12 text-[26px]" : "h-16 w-16 text-[36px]",
    queueName: isShort ? "text-[12px]" : "text-[16px]",
    queueSubname: isShort ? "text-[10px]" : "text-[12px]",
    queueStakeChip: isShort ? "mt-2 px-2.5 py-1 text-[10px]" : "mt-3 px-3 py-1.5 text-[11px]",
    queueEnergyText: isShort ? "text-[12px]" : "text-[14px]",
    queueEnergyBar: isShort ? "h-2.5" : "h-3",
    queueRuleCard: isShort ? "rounded-[16px]" : "rounded-[18px]",
    queueRulePad: isShort ? "px-1.5 py-2.5" : "px-2 py-3",
    queueRuleTitle: isShort ? "text-[13px]" : "text-[15px]",
    queueRuleSub: isShort ? "text-[10px]" : "text-[11px]",

    battleWrap: isShort ? "mt-3 rounded-[20px] p-3" : "mt-4 rounded-[24px] p-4",
    battleChip: isShort ? "px-3 py-1 text-[10px]" : "px-4 py-1.5 text-[12px]",
    battleTimer: isShort ? "text-[28px]" : isMedium ? "text-[32px]" : "text-[38px]",
    battleVs: isShort ? "text-[40px]" : isMedium ? "text-[48px]" : "text-[56px]",
    battlePlayerCard: isShort ? "rounded-[16px] px-2 py-2.5" : "rounded-[20px] px-3 py-3",
    battlePlayerAvatar: isShort ? "h-12 w-12 text-[26px]" : "h-16 w-16 text-[36px]",
    battlePlayerName: isShort ? "text-[11px]" : "text-[15px]",
    battlePlayerScore: isShort ? "px-2.5 py-1 text-[10px]" : "px-3 py-1.5 text-[11px]",
    battleEnergyText: isShort ? "text-[11px]" : "text-[13px]",
    battleEnergyBar: isShort ? "h-2.5" : "h-3",

    activeRow: isShort ? "mb-2.5 grid grid-cols-[1fr_auto_1fr] gap-2 items-center" : "mb-3 grid grid-cols-[1fr_auto_1fr] gap-3 items-center",
    activeAttackPill: isShort ? "rounded-full px-2 py-2" : "rounded-full px-3 py-2.5",
    activeAttackLabel: isShort ? "text-[9px] tracking-[0.12em]" : "text-[10px] tracking-[0.14em]",
    activeAttackValue: isShort ? "mt-0.5 text-[14px]" : "mt-0.5 text-[16px]",
    tapButton: isShort
      ? "h-[82px] w-[82px] text-[16px] border-[2px]"
      : isMedium
        ? "h-[92px] w-[92px] text-[18px] border-[3px]"
        : "h-[102px] w-[102px] text-[20px] border-[3px]",
    boostWrap: isShort ? "rounded-[15px] p-2.5" : "rounded-[18px] p-3",
    boostTitle: isShort ? "mb-2 text-[10px] tracking-[0.08em]" : "mb-2.5 text-[12px] tracking-[0.12em]",
    boostGrid: isShort ? "gap-1.5" : "gap-2",
    boostBtn: isShort ? "rounded-[12px] px-2 py-2" : "rounded-[14px] px-2 py-2.5",
    boostLabel: isShort ? "text-[11px]" : "text-[13px]",
    boostAccent: isShort ? "text-[8px]" : "text-[9px]",

    resultWrap: isShort ? "mt-3 rounded-[20px] p-3" : "mt-4 rounded-[24px] p-4",
    resultBanner: isShort ? "rounded-[14px] px-4 py-2 text-[18px]" : "rounded-[16px] px-6 py-2.5 text-[24px]",
    resultSub: isShort ? "mb-3 text-[14px]" : "mb-4 text-[18px]",
    resultRewardBox: isShort ? "mb-3 rounded-[16px] px-3 py-2.5" : "mb-4 rounded-[18px] px-4 py-3",
    resultReward: isShort ? "text-[22px]" : "text-[28px]",
    resultGrid: isShort ? "mb-3 grid grid-cols-[0.85fr_1.15fr] gap-2" : "mb-4 grid grid-cols-[0.9fr_1.1fr] gap-3",
    resultLeftCard: isShort ? "rounded-[16px] p-2.5" : "rounded-[20px] p-3",
    resultArt: isShort ? "h-[100px] rounded-[16px] text-[40px]" : "h-[130px] rounded-[20px] text-[54px]",
    resultRightCard: isShort ? "rounded-[16px] p-2.5" : "rounded-[20px] p-3",
    resultInfoTitle: isShort ? "text-[11px]" : "text-[13px]",
    resultInfoRow: isShort ? "rounded-[12px] px-2.5 py-2" : "rounded-[14px] px-3 py-2.5",
    resultInfoText: isShort ? "text-[10px]" : "text-[12px]",
    resultEnergy: isShort ? "mb-4 text-[14px]" : "mb-5 text-[18px]",
    resultButtons: isShort ? "gap-2" : "gap-3",
    resultBtn: isShort ? "rounded-[12px] px-3 py-2.5 text-[12px]" : "rounded-[14px] px-4 py-3 text-[14px]",
  };

  const syncUserState = (payload?: BattlePayload["user"]) => {
    if (!payload) return;
    useUserStore.setState({
      balance: payload.balance,
      available_energy: payload.available_energy,
    });
  };

  const triggerShake = () => {
    setShake(true);
    window.setTimeout(() => setShake(false), 170);
  };

  const triggerCritFlash = () => {
    setCritFlash(true);
    window.setTimeout(() => setCritFlash(false), 260);
  };

  const spawnHit = (value: number, options?: { critical?: boolean; combo?: number }) => {
    const id = Date.now() + Math.floor(Math.random() * 1000);
    setFloatingHits((prev) => [
      ...prev,
      { id, value, critical: options?.critical, combo: options?.combo },
    ]);
    window.setTimeout(() => {
      setFloatingHits((prev) => prev.filter((item) => item.id !== id));
    }, 900);
  };

  const hydrateRoom = (payload?: BattlePayload) => {
    if (!payload) return;

    if (payload.searching) {
      const queueTier = payload.queue?.room_tier ?? selectedTier;
      setSelectedTier(queueTier);
      setRoom({
        id: 0,
        status: "searching",
        room_tier: queueTier,
        stake_amount: payload.queue?.stake_amount ?? 1000,
        opponent_name: "Searching...",
        player_score: 0,
        bot_score: 0,
        my_score: 0,
        opponent_score: 0,
        duration_seconds: 15,
        started_at: null,
        ends_at: null,
        finished_at: null,
        result: null,
        reward: 0,
        support_spent: 0,
      });
      setSearchExpiresAt(payload.expires_at || null);
      setRemaining(0);
      syncUserState(payload.user);
      setError(null);
      return;
    }

    if (payload.room?.room_tier) {
      setSelectedTier(payload.room.room_tier);
    }

    setRoom(payload.room || null);
    setSearchExpiresAt(null);
    syncUserState(payload.user);
    setError(null);
  };

  const refreshCurrent = async () => {
    try {
      const data = await $http.$get<BattlePayload>("/clicker/battle/current");
      hydrateRoom(data);
    } catch (err) {
      if (axios.isAxiosError(err)) {
        if (err.response?.status === 404) {
          setRoom(null);
          setSearchExpiresAt(null);
          setSearchRemaining(0);
          setRemaining(0);
          setError(null);
          return;
        }
        setError(err.response?.data?.message || err.message);
      }
    }
  };

  useEffect(() => {
    void refreshCurrent();
  }, []);

  useEffect(() => {
    const handleResize = () => setScreenMode(getScreenMode());
    handleResize();
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  useEffect(() => {
    if (!room) return;

    if (["searching", "countdown", "active"].includes(room.status)) {
      const poller = window.setInterval(() => {
        void refreshCurrent();
      }, room.status === "active" ? 900 : 1500);

      return () => window.clearInterval(poller);
    }
  }, [room?.id, room?.status]);

  useEffect(() => {
    if (!searchExpiresAt || room?.status !== "searching") {
      setSearchRemaining(0);
      return;
    }

    const tick = () => {
      const diff = Math.ceil((new Date(searchExpiresAt).getTime() - Date.now()) / 1000);
      setSearchRemaining(Math.max(0, diff));
    };

    tick();
    const timer = window.setInterval(tick, 1000);
    return () => window.clearInterval(timer);
  }, [searchExpiresAt, room?.status]);

  useEffect(() => {
    if (!room || !room.ends_at || !["countdown", "active"].includes(room.status)) {
      setRemaining(0);
      return;
    }

    const tick = () => {
      if (room.status === "countdown") {
        const countdownBase = room.started_at ?? room.ends_at!;
        const diff = Math.ceil((new Date(countdownBase).getTime() - Date.now()) / 1000);
        setRemaining(room.countdown_remaining ?? Math.max(0, diff));
        return;
      }

      const diff = Math.ceil((new Date(room.ends_at!).getTime() - Date.now()) / 1000);
      setRemaining(Math.max(0, diff));
    };

    tick();
    const timer = window.setInterval(tick, 1000);
    return () => window.clearInterval(timer);
  }, [room?.id, room?.status, room?.ends_at, room?.started_at, room?.countdown_remaining]);

  useEffect(() => {
    if (!room || room.status !== "active" || !room.ends_at || busy) return;
    if (Date.now() < new Date(room.ends_at).getTime()) return;
    void finishBattle();
  }, [busy, room?.id, room?.status, room?.ends_at]);

  useEffect(() => {
    if (!isActive) {
      setCombo(0);
      setComboVisible(false);
      setRageMode(false);
      setLastTapAt(0);
      return;
    }

    setRageMode(remaining <= 5 || combo >= 5);
  }, [isActive, remaining, combo]);

  useEffect(() => {
    if (!isFinished || !room?.result) {
      setResultBoom(false);
      return;
    }

    setResultBoom(true);
    const timer = window.setTimeout(() => setResultBoom(false), 900);
    return () => window.clearTimeout(timer);
  }, [isFinished, room?.result, room?.id]);

  const startBattle = async () => {
    setBusy(true);
    setError(null);
    setRoom(null);
    setSearchExpiresAt(null);
    setSearchRemaining(0);
    setRemaining(0);
    setCombo(0);
    setComboVisible(false);
    setRageMode(false);
    setFloatingHits([]);

    try {
      const { data } = await $http.post<BattlePayload>("/clicker/battle/start", {
        room_tier: selectedTier,
      });
      hydrateRoom(data);
    } catch (err) {
      setError(
        axios.isAxiosError(err)
          ? err.response?.data?.message || err.message
          : "Failed to start battle."
      );
    } finally {
      setBusy(false);
    }
  };

  const punch = async () => {
    if (!isActive || isPunching || (user.available_energy ?? 0) < 1) return;

    const now = Date.now();
    const rapidTap = now - lastTapAt <= 800;
    const nextCombo = rapidTap ? Math.min(combo + 1, 5) : 1;
    const comboBoost = nextCombo >= 5 ? 5 : nextCombo >= 3 ? 3 : nextCombo >= 2 ? 2 : 1;
    const critChance = rageMode ? 0.28 : comboBoost >= 3 ? 0.18 : 0.1;
    const isCritical = Math.random() < critChance;

    setLastTapAt(now);
    setCombo(nextCombo);
    setComboVisible(nextCombo >= 2);
    triggerShake();
    if (isCritical) triggerCritFlash();

    setIsPunching(true);
    setError(null);
    const previousScore = currentMyScore;

    try {
      const { data } = await $http.post<BattlePayload>("/clicker/battle/punch", {
        room_id: room?.id,
      });

      const nextRoom = data.room || null;
      const gained = Math.max(
        0,
        (nextRoom?.my_score ?? nextRoom?.player_score ?? previousScore) - previousScore
      );

      const visualBase = gained > 0 ? gained : Math.floor(Math.random() * 8) + 4;
      const visualValue = visualBase * (isCritical ? 2 : 1) * comboBoost;

      setRoom(nextRoom);
      spawnHit(visualValue, {
        critical: isCritical,
        combo: comboBoost > 1 ? comboBoost : undefined,
      });
      syncUserState(data.user);
    } catch (err) {
      setError(
        axios.isAxiosError(err) ? err.response?.data?.message || err.message : "Punch failed."
      );
    } finally {
      window.setTimeout(() => setIsPunching(false), 100);
      window.setTimeout(() => {
        if (Date.now() - now >= 700) {
          setComboVisible(false);
        }
      }, 950);
    }
  };

  const support = async (amount: number) => {
    if (!isActive || isSupporting !== null) return;

    setIsSupporting(amount);
    setError(null);

    try {
      const { data } = await $http.post<BattlePayload>("/clicker/battle/boost", {
        room_id: room?.id,
        amount,
      });
      setRoom(data.room || null);
      syncUserState(data.user);
    } catch (err) {
      setError(
        axios.isAxiosError(err) ? err.response?.data?.message || err.message : "Boost failed."
      );
    } finally {
      setIsSupporting(null);
    }
  };

  const finishBattle = async () => {
    if (!room) return;

    try {
      const { data } = await $http.post<BattlePayload>("/clicker/battle/finish", {
        room_id: room.id,
      });
      setRoom(data.room || null);
      syncUserState(data.user);
    } catch (err) {
      if (axios.isAxiosError(err) && err.response?.status !== 422) {
        setError(err.response?.data?.message || err.message);
      }
    }
  };

  const clearBattle = () => {
    setRoom(null);
    setError(null);
    setRemaining(0);
    setSearchRemaining(0);
    setSearchExpiresAt(null);
    setCombo(0);
    setComboVisible(false);
    setRageMode(false);
    setLastTapAt(0);
    setFloatingHits([]);
    setResultBoom(false);
  };

  return (
    <div
      className={cls(
        "min-h-[100dvh] w-full overflow-x-hidden overflow-y-auto bg-[#090303] text-white",
        shake && "animate-[screenShake_0.17s_linear]",
        ui.bottomPad,
        ui.topPad
      )}
    >
      <style>{`
        @keyframes floatHit {
          0% { transform: translateY(22px) scale(0.78); opacity: 0; }
          18% { transform: translateY(0) scale(1.18); opacity: 1; }
          100% { transform: translateY(-56px) scale(1); opacity: 0; }
        }
        @keyframes emberPulse {
          0%,100% { transform: scale(1); filter: brightness(1); }
          50% { transform: scale(1.03); filter: brightness(1.08); }
        }
        @keyframes screenShake {
          0% { transform: translateX(0); }
          25% { transform: translateX(-3px); }
          50% { transform: translateX(3px); }
          75% { transform: translateX(-2px); }
          100% { transform: translateX(0); }
        }
        @keyframes pulseTap {
          0%,100% { transform: scale(1); }
          50% { transform: scale(1.07); }
        }
        @keyframes critFlash {
          0% { opacity: 0; }
          35% { opacity: 1; }
          100% { opacity: 0; }
        }
        @keyframes rageGlow {
          0%,100% { box-shadow: 0 0 0 rgba(255,80,20,0.0), 0 0 24px rgba(255,120,35,0.08); }
          50% { box-shadow: 0 0 0 1px rgba(255,109,45,0.3), 0 0 34px rgba(255,90,25,0.26); }
        }
        @keyframes comboPop {
          0% { transform: scale(0.84) translateY(8px); opacity: 0; }
          30% { transform: scale(1.08) translateY(0); opacity: 1; }
          100% { transform: scale(1) translateY(0); opacity: 1; }
        }
        @keyframes boom {
          0% { transform: scale(0.55); opacity: 0; }
          35% { transform: scale(1.2); opacity: 1; }
          100% { transform: scale(1); opacity: 0; }
        }
      `}</style>

      <div className={cls("mx-auto w-full max-w-md", ui.outerPx)}>
        {error && (
          <div className="mb-4 rounded-[18px] border border-[#a34327] bg-[rgba(109,27,16,0.6)] px-4 py-3 text-sm text-[#ffd2c2]">
            {error}
          </div>
        )}

        {isIdle && (
          <>
            <div
              className={cls(
                ui.sectionGap,
                "flex items-center justify-between rounded-[16px] border border-[#8e3a18] bg-[linear-gradient(180deg,rgba(73,19,8,0.96),rgba(41,10,5,0.96))] shadow-[0_0_12px_rgba(255,110,40,0.07)]",
                ui.balanceRow
              )}
            >
              <div>
                <p className={cls("uppercase text-[#b88a73]", ui.balanceLabel)}>Balance</p>
                <p className={cls("mt-1 font-black tracking-tight text-white", ui.balanceValue)}>
                  {(user.balance ?? 0).toLocaleString()}
                </p>
              </div>

              <div
                className={cls(
                  "rounded-full border border-[#8e3a18] bg-[rgba(53,13,6,0.9)] font-bold text-[#ffb34d]",
                  ui.balanceTier
                )}
              >
                {selectedTier.toUpperCase()}
              </div>
            </div>

            <div
              className={cls(
                ui.sectionGap,
                "border border-[#9a401b] bg-[linear-gradient(180deg,rgba(58,13,7,0.98),rgba(23,6,3,0.98))] shadow-[0_0_24px_rgba(255,110,35,0.08)]",
                ui.tierWrap
              )}
            >
              <div className={cls("grid grid-cols-3", ui.tierGap)}>
                {roomOptions.map((option) => {
                  const active = selectedTier === option.tier;
                  return (
                    <button
                      key={option.tier}
                      onClick={() => setSelectedTier(option.tier)}
                      disabled={!!room && !isFinished && !isSearching}
                      className={cls(
                        "border text-left transition",
                        ui.tierCard,
                        active
                          ? cls(
                              tierGlow[option.tier],
                              arenaFrameGlow[option.tier],
                              "scale-[1.01]"
                            )
                          : "border-[#61301a] bg-[linear-gradient(180deg,rgba(42,10,5,0.96),rgba(24,6,3,0.96))]",
                        !!room && !isFinished && !isSearching && "opacity-60"
                      )}
                    >
                      <p className={cls("font-black text-white", ui.tierTitle)}>
                        {option.label.replace(" Arena", "")}
                      </p>
                      <p className={cls("font-black text-[#ffcf73]", ui.tierStake)}>
                        {option.stake.toLocaleString()}
                      </p>
                      <p className={cls("text-[#b88a73]", ui.tierSub)}>{option.subtitle}</p>
                    </button>
                  );
                })}
              </div>
            </div>

            <div
              className={cls(
                ui.sectionGap,
                "overflow-hidden border border-[#9a401b] bg-[linear-gradient(180deg,rgba(58,13,7,0.98),rgba(21,5,3,0.98))] shadow-[0_0_24px_rgba(255,120,40,0.08)]",
                ui.heroWrap
              )}
            >
              <div className={cls("grid", ui.heroGrid)}>
                <div
                  className={cls(
                    "relative overflow-hidden border-r border-[#6b2a13] bg-[radial-gradient(circle_at_50%_18%,rgba(255,161,74,0.18),rgba(49,11,6,0.96)_58%)]",
                    ui.heroLeftMin,
                    ui.heroLeftPad
                  )}
                >
                  <div className="absolute left-4 top-6 h-12 w-12 rounded-full bg-[radial-gradient(circle,rgba(255,173,86,0.5),transparent_65%)] blur-xl" />
                  <div className="absolute right-4 top-6 h-12 w-12 rounded-full bg-[radial-gradient(circle,rgba(255,173,86,0.5),transparent_65%)] blur-xl" />
                  <div className="absolute bottom-0 left-0 right-0 h-16 bg-[radial-gradient(circle_at_50%_100%,rgba(255,117,37,0.35),transparent_70%)]" />

                  <div className="mt-2 flex h-full flex-col items-center justify-center">
                    <div
                      className={cls(
                        "mb-2 flex w-full items-center justify-center border border-[#6f3017] bg-[linear-gradient(180deg,rgba(35,8,4,0.7),rgba(22,5,3,0.85))] shadow-[inset_0_0_20px_rgba(255,130,40,0.08)]",
                        ui.heroMonkeyBox
                      )}
                    >
                      <div className="text-center">
                        <div className={cls("leading-none", ui.heroMonkeyEmoji)}>🐵</div>
                        <p className={cls("mt-1 uppercase text-[#c58b69]", ui.heroMonkeySub)}>
                          Arena King
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                <div className={ui.heroRightPad}>
                  <p className={cls("uppercase text-[#ffb34d]", ui.heroLabel)}>Battle Arena</p>
                  <h2 className={cls("mt-2 font-black leading-[0.95] text-white", ui.heroTitle)}>
                    FIGHT & WIN
                  </h2>
                  <p className={cls("mt-2 text-[#c58b69]", ui.heroSub)}>
                    Tap duel for big rewards
                  </p>

                  <div className={ui.heroRows}>
                    <div
                      className={cls(
                        "flex items-center justify-between rounded-[14px] border border-[#643019] bg-[rgba(16,4,2,0.55)]",
                        ui.heroRowPad
                      )}
                    >
                      <div className="flex items-center gap-2">
                        <Coins className={cls(isShort ? "h-3.5 w-3.5" : "h-4 w-4", "text-[#ffb34d]")} />
                        <span className={cls("uppercase text-[#d5a17b]", ui.heroRowLabel)}>
                          Stake
                        </span>
                      </div>
                      <span className={cls("font-black text-[#ffcf73]", ui.heroRowValue)}>
                        {currentStake.toLocaleString()}
                      </span>
                    </div>

                    <div
                      className={cls(
                        "flex items-center justify-between rounded-[14px] border border-[#643019] bg-[rgba(16,4,2,0.55)]",
                        ui.heroRowPad
                      )}
                    >
                      <div className="flex items-center gap-2">
                        <Clock3 className={cls(isShort ? "h-3.5 w-3.5" : "h-4 w-4", "text-[#ffb34d]")} />
                        <span className={cls("uppercase text-[#d5a17b]", ui.heroRowLabel)}>
                          Duration
                        </span>
                      </div>
                      <span className={cls("font-black text-[#ffcf73]", ui.heroRowValue)}>
                        15 SECONDS
                      </span>
                    </div>

                    <div
                      className={cls(
                        "flex items-center justify-between rounded-[14px] border border-[#643019] bg-[rgba(16,4,2,0.55)]",
                        ui.heroRowPad
                      )}
                    >
                      <div className="flex items-center gap-2">
                        <TimerReset className={cls(isShort ? "h-3.5 w-3.5" : "h-4 w-4", "text-[#ffb34d]")} />
                        <span className={cls("uppercase text-[#d5a17b]", ui.heroRowLabel)}>
                          Fallback
                        </span>
                      </div>
                      <span className={cls("font-black text-[#ffcf73]", ui.heroRowValue)}>
                        BOT AFTER 8s
                      </span>
                    </div>
                  </div>

                  <button
                    onClick={() => void startBattle()}
                    disabled={busy}
                    className={cls(
                      "flex w-full items-center justify-center gap-2 bg-[linear-gradient(180deg,#ffbf53,#f57a2f)] font-black tracking-[0.04em] text-[#2c0d02] shadow-[0_10px_24px_rgba(255,145,53,0.28)] transition active:scale-[0.99] disabled:opacity-60",
                      ui.cta
                    )}
                  >
                    <span>{busy ? "LOADING..." : "FIND OPPONENT"}</span>
                    <span className="text-xl leading-none">→</span>
                  </button>
                </div>
              </div>
            </div>

            <div className={cls("grid grid-cols-2", ui.infoGridGap)}>
              <div
                className={cls(
                  "border border-[#8a3718] bg-[linear-gradient(180deg,rgba(54,12,7,0.98),rgba(22,5,3,0.98))] shadow-[0_0_18px_rgba(255,110,40,0.06)]",
                  ui.infoCard
                )}
              >
                <div className="mb-2 flex items-center gap-2">
                  <Zap className={cls(isShort ? "h-4 w-4" : "h-5 w-5", "text-[#ffb34d]")} />
                  <p className={cls("font-black text-[#ffb34d]", ui.infoTitle)}>BATTLE FLOW</p>
                </div>
                <p className={cls("text-[#f0d6c5]", ui.infoBody)}>
                  8s Queue → 3s Countdown → 15s Fight
                </p>
              </div>

              <div
                className={cls(
                  "border border-[#8a3718] bg-[linear-gradient(180deg,rgba(54,12,7,0.98),rgba(22,5,3,0.98))] shadow-[0_0_18px_rgba(255,110,40,0.06)]",
                  ui.infoCard
                )}
              >
                <div className="mb-2 flex items-center gap-2">
                  <Trophy className={cls(isShort ? "h-4 w-4" : "h-5 w-5", "text-[#ffb34d]")} />
                  <p className={cls("font-black text-[#ffb34d]", ui.infoTitle)}>REWARD MODEL</p>
                </div>
                <p className={cls("text-[#f0d6c5]", ui.infoBody)}>
                  Winner gets ~170%
                  <br />
                  Loser 10%
                </p>
              </div>
            </div>
          </>
        )}

        {isSearching && room && (
          <div
            className={cls(
              "border border-[#973d1a] bg-[linear-gradient(180deg,rgba(49,11,6,0.98),rgba(20,5,3,0.98))] shadow-[0_0_24px_rgba(255,115,35,0.08)]",
              ui.queueWrap
            )}
          >
            <div className="mb-4 flex items-center justify-between">
              <div
                className={cls(
                  "flex items-center justify-center rounded-full border border-[#8a3a18] bg-[rgba(54,12,7,0.98)]",
                  ui.queueIcon
                )}
              >
                <ChevronLeft className={cls(ui.queueIconSvg, "text-[#ffb34d]")} />
              </div>

              <div className="text-center">
                <p className={cls("font-black text-[#ffb34d]", ui.queueBadge)}>BATTLE ARENA</p>
                <div
                  className={cls(
                    "mt-2 rounded-full border border-[#7c3215] bg-[rgba(30,7,4,0.9)] font-semibold text-white",
                    ui.queueChip
                  )}
                >
                  15s Tap Duel
                </div>
              </div>

              <div
                className={cls(
                  "flex items-center justify-center rounded-full border border-[#8a3a18] bg-[rgba(54,12,7,0.98)]",
                  ui.queueIcon
                )}
              >
                <Flag className={cls(ui.queueIconSvg, "text-[#ffb34d]")} />
              </div>
            </div>

            <div className="mb-4 grid grid-cols-[1fr_auto_1fr] items-center gap-2">
              <div
                className={cls(
                  "border border-[#813417] bg-[linear-gradient(180deg,rgba(58,13,7,0.96),rgba(22,6,3,0.96))] text-center shadow-[0_0_16px_rgba(255,115,35,0.06)]",
                  ui.queueCards
                )}
              >
                <div
                  className={cls(
                    "mx-auto mb-2 flex items-center justify-center rounded-full border-[3px] border-[#f18d3d] bg-[radial-gradient(circle,rgba(255,162,82,0.16),rgba(39,8,4,0.98)_68%)] shadow-[0_0_16px_rgba(255,135,45,0.16)]",
                    ui.queueAvatar
                  )}
                >
                  🐵
                </div>
                <p className={cls("font-black text-white", ui.queueName)}>You</p>
                <p className={cls("mt-1 text-[#c58b69]", ui.queueSubname)}>
                  {user.first_name || "Monkeypunch"}
                </p>
                <div
                  className={cls(
                    "inline-flex items-center gap-1.5 rounded-full border border-[#7d3416] bg-[rgba(20,5,2,0.9)] font-bold text-[#ffd28a]",
                    ui.queueStakeChip
                  )}
                >
                  <Coins className="h-3.5 w-3.5 text-[#ffb34d]" />
                  <span>Stake: {currentStake.toLocaleString()}</span>
                </div>
              </div>

              <div className="text-center">
                <div
                  className={cls(
                    "font-black leading-none text-[#ffbb60] [text-shadow:0_0_18px_rgba(255,150,54,0.45)]",
                    ui.queueVs
                  )}
                  style={{ animation: "emberPulse 1.8s ease-in-out infinite" }}
                >
                  VS
                </div>
                <div
                  className={cls(
                    "mt-2 rounded-full border border-[#7a3114] bg-[rgba(26,6,3,0.96)] font-bold text-[#ffbb60]",
                    ui.queueReady
                  )}
                >
                  Get ready!
                </div>
                <div
                  className={cls(
                    "mx-auto flex items-center justify-center rounded-full border-[3px] border-[#f08a3b] bg-[radial-gradient(circle,rgba(255,160,68,0.15),rgba(35,8,4,0.98)_70%)] font-black text-[#ffd28a] shadow-[0_0_16px_rgba(255,140,45,0.16)]",
                    ui.queueTimerCircle
                  )}
                >
                  {searchRemaining || 0}s
                </div>
              </div>

              <div
                className={cls(
                  "border border-[#813417] bg-[linear-gradient(180deg,rgba(58,13,7,0.96),rgba(22,6,3,0.96))] text-center shadow-[0_0_16px_rgba(255,115,35,0.06)]",
                  ui.queueCards
                )}
              >
                <div
                  className={cls(
                    "mx-auto mb-2 flex items-center justify-center rounded-full border-[3px] border-[#f18d3d] bg-[radial-gradient(circle,rgba(255,162,82,0.16),rgba(39,8,4,0.98)_68%)] shadow-[0_0_16px_rgba(255,135,45,0.16)]",
                    ui.queueAvatar
                  )}
                >
                  🥊
                </div>
                <p className={cls("font-black text-white", ui.queueName)}>Opponent</p>
                <p className={cls("mt-1 text-[#c58b69]", ui.queueSubname)}>Searching...</p>
                <div
                  className={cls(
                    "inline-flex items-center gap-1.5 rounded-full border border-[#7d3416] bg-[rgba(20,5,2,0.9)] font-bold text-[#ffd28a]",
                    ui.queueStakeChip
                  )}
                >
                  <Coins className="h-3.5 w-3.5 text-[#ffb34d]" />
                  <span>Stake: {currentStake.toLocaleString()}</span>
                </div>
              </div>
            </div>

            <div className="mb-4 grid grid-cols-2 gap-2">
              <div>
                <div className={cls("mb-1.5 flex items-center gap-1.5 font-black text-white", ui.queueEnergyText)}>
                  <Zap className="h-4 w-4 text-[#ffb34d]" />
                  {(user.available_energy ?? 0).toLocaleString()}
                </div>
                <div className={cls("overflow-hidden rounded-full bg-[rgba(255,255,255,0.06)]", ui.queueEnergyBar)}>
                  <div className="h-full w-[72%] rounded-full bg-[linear-gradient(90deg,#ffbf54,#ff7d30)]" />
                </div>
              </div>

              <div>
                <div className={cls("mb-1.5 flex items-center justify-end gap-1.5 font-black text-white", ui.queueEnergyText)}>
                  <span>{(user.available_energy ?? 0).toLocaleString()}</span>
                  <Zap className="h-4 w-4 text-[#ffb34d]" />
                </div>
                <div className={cls("overflow-hidden rounded-full bg-[rgba(255,255,255,0.06)]", ui.queueEnergyBar)}>
                  <div className="ml-auto h-full w-[72%] rounded-full bg-[linear-gradient(90deg,#ffbf54,#ff7d30)]" />
                </div>
              </div>
            </div>

            <div
              className={cls(
                "mb-4 grid grid-cols-3 divide-x divide-[#5d2411] border border-[#8a3718] bg-[linear-gradient(180deg,rgba(54,12,7,0.98),rgba(21,5,3,0.98))]",
                ui.queueRuleCard
              )}
            >
              <div className={cls("text-center", ui.queueRulePad)}>
                <div className="mb-1.5 flex justify-center">
                  <Zap className={cls(isShort ? "h-4 w-4" : "h-5 w-5", "text-[#ffb34d]")} />
                </div>
                <p className={cls("font-black text-white", ui.queueRuleTitle)}>Tap</p>
                <p className={cls("text-[#c58b69]", ui.queueRuleSub)}>to attack</p>
              </div>
              <div className={cls("text-center", ui.queueRulePad)}>
                <div className="mb-1.5 flex justify-center">
                  <Clock3 className={cls(isShort ? "h-4 w-4" : "h-5 w-5", "text-[#ffb34d]")} />
                </div>
                <p className={cls("font-black text-white", ui.queueRuleTitle)}>15s</p>
                <p className={cls("text-[#c58b69]", ui.queueRuleSub)}>fight time</p>
              </div>
              <div className={cls("text-center", ui.queueRulePad)}>
                <div className="mb-1.5 flex justify-center">
                  <TimerReset className={cls(isShort ? "h-4 w-4" : "h-5 w-5", "text-[#ffb34d]")} />
                </div>
                <p className={cls("font-black text-white", ui.queueRuleTitle)}>Bot</p>
                <p className={cls("text-[#c58b69]", ui.queueRuleSub)}>after 8s</p>
              </div>
            </div>

            <div className={cls("grid grid-cols-2", ui.infoGridGap)}>
              <div
                className={cls(
                  "border border-[#8a3718] bg-[linear-gradient(180deg,rgba(54,12,7,0.98),rgba(21,5,3,0.98))]",
                  ui.infoCard
                )}
              >
                <div className="mb-2 flex items-center gap-2">
                  <Trophy className="h-4 w-4 text-[#ffb34d]" />
                  <p className={cls("font-black text-[#ffcf73]", isShort ? "text-[12px]" : "text-[13px]")}>
                    Tips to win
                  </p>
                </div>
                <div className={cls("space-y-1.5 text-[#f0d6c5]", isShort ? "text-[11px] leading-4" : "text-[12px] leading-5")}>
                  <p>• Tap fast & evenly</p>
                  <p>• Keep your rhythm</p>
                  <p>• Don&apos;t stop tapping!</p>
                </div>
              </div>

              <div
                className={cls(
                  "border border-[#8a3718] bg-[linear-gradient(180deg,rgba(54,12,7,0.98),rgba(21,5,3,0.98))]",
                  ui.infoCard
                )}
              >
                <div className="mb-3 flex items-center justify-between">
                  <p className={cls("font-black text-[#ffcf73]", isShort ? "text-[12px]" : "text-[13px]")}>
                    Your record
                  </p>
                  <div className="flex h-7 w-7 items-center justify-center rounded-full border border-[#6f3017] bg-[rgba(20,5,3,0.9)]">
                    <span className="text-xs text-white">⌄</span>
                  </div>
                </div>
                <div className="grid grid-cols-3 gap-2 text-center">
                  <div>
                    <p className={cls("font-black text-white", isShort ? "text-[15px]" : "text-[18px]")}>120</p>
                    <p className={cls("text-[#c58b69]", isShort ? "text-[9px]" : "text-[10px]")}>Battles</p>
                  </div>
                  <div>
                    <p className={cls("font-black text-white", isShort ? "text-[15px]" : "text-[18px]")}>87%</p>
                    <p className={cls("text-[#c58b69]", isShort ? "text-[9px]" : "text-[10px]")}>Win rate</p>
                  </div>
                  <div>
                    <p className={cls("font-black text-[#ffcf73]", isShort ? "text-[15px]" : "text-[18px]")}>+23,500</p>
                    <p className={cls("text-[#c58b69]", isShort ? "text-[9px]" : "text-[10px]")}>Earned</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        {(isCountdown || isActive) && room && (
          <div
            className={cls(
              "relative overflow-hidden border border-[#973d1a] bg-[linear-gradient(180deg,rgba(49,11,6,0.98),rgba(20,5,3,0.98))] shadow-[0_0_24px_rgba(255,115,35,0.08)]",
              rageMode && "animate-[rageGlow_0.9s_ease-in-out_infinite]",
              ui.battleWrap
            )}
          >
            {critFlash && (
              <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(255,220,120,0.18),rgba(255,90,20,0.08),transparent_70%)] animate-[critFlash_0.26s_ease-out]" />
            )}

            <div className={cls("text-center", isShort ? "mb-3" : "mb-4")}>
              <div
                className={cls(
                  "inline-flex rounded-full border border-[#7c3215] bg-[rgba(30,7,4,0.9)] font-bold tracking-[0.18em] text-[#ffcf73]",
                  ui.battleChip
                )}
              >
                {isCountdown ? "GET READY" : "LIVE BATTLE"}
              </div>
              <p className={cls("mt-3 font-black leading-none text-white", ui.battleTimer)}>
                {formatTime(remaining)}
              </p>

              {isActive && (comboVisible || rageMode) && (
                <div className="mt-2 flex items-center justify-center gap-2">
                  {comboVisible && combo >= 2 && (
                    <div className="rounded-full border border-[#ffb95f] bg-[rgba(255,170,72,0.12)] px-3 py-1 text-[11px] font-black text-[#ffcf73] animate-[comboPop_0.22s_ease-out]">
                      COMBO x{combo >= 5 ? 5 : combo >= 3 ? 3 : 2}
                    </div>
                  )}
                  {rageMode && (
                    <div className="rounded-full border border-[#ff6b2d] bg-[rgba(255,80,20,0.16)] px-3 py-1 text-[11px] font-black text-[#ff9f6b] animate-[comboPop_0.22s_ease-out]">
                      🔥 RAGE MODE
                    </div>
                  )}
                </div>
              )}
            </div>

            <div className={cls("grid grid-cols-[1fr_auto_1fr] items-center gap-2", isShort ? "mb-3" : "mb-4")}>
              <div
                className={cls(
                  "border border-[#813417] bg-[linear-gradient(180deg,rgba(58,13,7,0.96),rgba(22,6,3,0.96))] text-center",
                  ui.battlePlayerCard
                )}
              >
                <div
                  className={cls(
                    "mx-auto mb-2 flex items-center justify-center rounded-full border-[3px] border-[#f18d3d] bg-[radial-gradient(circle,rgba(255,162,82,0.16),rgba(39,8,4,0.98)_68%)] shadow-[0_0_16px_rgba(255,135,45,0.16)]",
                    ui.battlePlayerAvatar
                  )}
                >
                  🐵
                </div>
                <p className={cls("font-black text-white", ui.battlePlayerName)}>
                  {user.first_name || "Monkeypunch"}
                </p>
                <div
                  className={cls(
                    "mt-2 inline-flex items-center gap-1.5 rounded-full border border-[#7d3416] bg-[rgba(20,5,2,0.9)] font-bold text-[#ffd28a]",
                    ui.battlePlayerScore
                  )}
                >
                  <Trophy className="h-3.5 w-3.5 text-[#ffb34d]" />
                  <span>{currentMyScore.toLocaleString()}</span>
                </div>
              </div>

              <div className="text-center">
                <div
                  className={cls(
                    "font-black leading-none text-[#ffbb60] [text-shadow:0_0_18px_rgba(255,150,54,0.45)]",
                    ui.battleVs
                  )}
                  style={{ animation: "emberPulse 1.8s ease-in-out infinite" }}
                >
                  VS
                </div>
              </div>

              <div
                className={cls(
                  "border border-[#813417] bg-[linear-gradient(180deg,rgba(58,13,7,0.96),rgba(22,6,3,0.96))] text-center",
                  ui.battlePlayerCard
                )}
              >
                <div
                  className={cls(
                    "mx-auto mb-2 flex items-center justify-center rounded-full border-[3px] border-[#f18d3d] bg-[radial-gradient(circle,rgba(255,162,82,0.16),rgba(39,8,4,0.98)_68%)] shadow-[0_0_16px_rgba(255,135,45,0.16)]",
                    ui.battlePlayerAvatar
                  )}
                >
                  🥊
                </div>
                <p className={cls("font-black text-white", ui.battlePlayerName)}>
                  {room.opponent_name || "Opponent"}
                </p>
                <div
                  className={cls(
                    "mt-2 inline-flex items-center gap-1.5 rounded-full border border-[#7d3416] bg-[rgba(20,5,2,0.9)] font-bold text-[#ffd28a]",
                    ui.battlePlayerScore
                  )}
                >
                  <Trophy className="h-3.5 w-3.5 text-[#ffb34d]" />
                  <span>{currentOpponentScore.toLocaleString()}</span>
                </div>
              </div>
            </div>

            <div className={cls("grid grid-cols-2", isShort ? "mb-3 gap-2" : "mb-4 gap-3")}>
              <div>
                <div className={cls("mb-1.5 flex items-center justify-between font-black text-white", ui.battleEnergyText)}>
                  <div className="flex items-center gap-1.5">
                    <Zap className="h-4 w-4 text-[#ffb34d]" />
                    <span>{(user.available_energy ?? 0).toLocaleString()}</span>
                  </div>
                  {isActive && <span className="text-[#d7ad8f]">DEF {myDefense.toLocaleString()}</span>}
                </div>
                <div className={cls("overflow-hidden rounded-full bg-[rgba(255,255,255,0.06)]", ui.battleEnergyBar)}>
                  <div
                    className={cls(
                      "h-full rounded-full transition-all",
                      rageMode
                        ? "bg-[linear-gradient(90deg,#ff9152,#ff3c1f)]"
                        : "bg-[linear-gradient(90deg,#ffbf54,#ff7d30)]"
                    )}
                    style={{ width: `${Math.max(8, progress)}%` }}
                  />
                </div>
              </div>

              <div>
                <div className={cls("mb-1.5 flex items-center justify-between font-black text-white", ui.battleEnergyText)}>
                  {isActive && <span className="text-[#d7ad8f]">DEF {opponentDefense.toLocaleString()}</span>}
                  <div className="ml-auto flex items-center gap-1.5">
                    <span>{opponentDefense.toLocaleString()}</span>
                    <Zap className="h-4 w-4 text-[#ffb34d]" />
                  </div>
                </div>
                <div className={cls("overflow-hidden rounded-full bg-[rgba(255,255,255,0.06)]", ui.battleEnergyBar)}>
                  <div
                    className={cls(
                      "ml-auto h-full rounded-full transition-all",
                      rageMode
                        ? "bg-[linear-gradient(90deg,#ff9152,#ff3c1f)]"
                        : "bg-[linear-gradient(90deg,#ffbf54,#ff7d30)]"
                    )}
                    style={{ width: `${Math.max(8, 100 - progress)}%` }}
                  />
                </div>
              </div>
            </div>

            {isActive && (
              <>
                <div className={cls("pointer-events-none relative flex items-center justify-center", isShort ? "mb-2 h-6" : "mb-3 h-7")}>
                  {floatingHits.map((hit) => (
                    <div
                      key={hit.id}
                      className={cls(
                        "absolute rounded-full font-black shadow-[0_0_24px_rgba(255,171,74,0.5)]",
                        hit.critical
                          ? "border border-[#ffd46c]/40 bg-[rgba(255,222,130,0.16)] text-[#fff0a8]"
                          : "border border-[#ffb95f]/30 bg-[rgba(255,171,74,0.14)] text-[#ffcf73]",
                        isShort ? "px-2.5 py-0.5 text-[11px]" : "px-3 py-1 text-[13px]"
                      )}
                      style={{ animation: "floatHit 900ms ease-out forwards" }}
                    >
                      +{hit.value}
                      {hit.combo && hit.combo > 1 ? ` x${hit.combo}` : ""}
                      {hit.critical ? " CRIT" : ""}
                    </div>
                  ))}
                </div>

                <div className={ui.activeRow}>
                  <div
                    className={cls(
                      "border border-[#8a3718] bg-[linear-gradient(180deg,rgba(54,12,7,0.98),rgba(21,5,3,0.98))] text-center",
                      ui.activeAttackPill
                    )}
                  >
                    <p className={cls("font-black text-[#ffb34d]", ui.activeAttackLabel)}>ATTACK</p>
                    <p className={cls("font-black text-white", ui.activeAttackValue)}>
                      {(currentMyScore + (room?.my_support_spent ?? 0)).toLocaleString()}
                    </p>
                  </div>

                  <div className="flex justify-center">
                    <button
                      onClick={() => void punch()}
                      disabled={isPunching || (user.available_energy ?? 0) <= 0}
                      className={cls(
                        "flex items-center justify-center rounded-full border-[#ffb34d] font-black text-white shadow-[0_0_26px_rgba(255,150,54,0.2)] transition active:scale-[0.95] disabled:opacity-60 animate-[pulseTap_0.95s_ease-in-out_infinite]",
                        rageMode
                          ? "bg-[radial-gradient(circle,rgba(255,112,52,0.28),rgba(100,20,8,0.98)_68%)]"
                          : "bg-[radial-gradient(circle,rgba(255,164,69,0.22),rgba(76,18,8,0.98)_68%)]",
                        ui.tapButton
                      )}
                    >
                      {isPunching ? "HIT!" : "TAP!"}
                    </button>
                  </div>

                  <div
                    className={cls(
                      "border border-[#8a3718] bg-[linear-gradient(180deg,rgba(54,12,7,0.98),rgba(21,5,3,0.98))] text-center",
                      ui.activeAttackPill
                    )}
                  >
                    <p className={cls("font-black text-[#ffb34d]", ui.activeAttackLabel)}>ATTACK</p>
                    <p className={cls("font-black text-white", ui.activeAttackValue)}>
                      {(currentOpponentScore + (room?.opponent_support_spent ?? 0)).toLocaleString()}
                    </p>
                  </div>
                </div>

                <div
                  className={cls(
                    "border border-[#8a3718] bg-[linear-gradient(180deg,rgba(54,12,7,0.98),rgba(21,5,3,0.98))]",
                    ui.boostWrap
                  )}
                >
                  <p className={cls("text-center font-black text-[#ffb34d]", ui.boostTitle)}>
                    BATTLE BOOSTS
                  </p>

                  <div className={cls("grid grid-cols-3", ui.boostGrid)}>
                    {supportOptions.map((option) => {
                      const Icon = option.icon;
                      const loading = isSupporting === option.amount;

                      return (
                        <button
                          key={option.amount}
                          onClick={() => void support(option.amount)}
                          disabled={isSupporting !== null || (user.balance ?? 0) < option.amount}
                          className={cls(
                            "border border-[#6f3017] bg-[rgba(24,6,3,0.95)] text-center transition active:scale-[0.98] disabled:opacity-60",
                            ui.boostBtn
                          )}
                        >
                          <div className="mb-1 flex justify-center">
                            <Icon className={cls(isShort ? "h-4 w-4" : "h-4.5 w-4.5", "text-[#ffb34d]")} />
                          </div>
                          <p className={cls("font-black text-white", ui.boostLabel)}>
                            {loading ? "..." : option.label}
                          </p>
                          <p className={cls("mt-0.5 tracking-[0.08em] text-[#c58b69]", ui.boostAccent)}>
                            {option.accent}
                          </p>
                        </button>
                      );
                    })}
                  </div>
                </div>
              </>
            )}
          </div>
        )}

        {isFinished && room && (
          <div
            className={cls(
              "relative overflow-hidden border border-[#973d1a] bg-[linear-gradient(180deg,rgba(49,11,6,0.98),rgba(20,5,3,0.98))] shadow-[0_0_24px_rgba(255,115,35,0.08)]",
              ui.resultWrap
            )}
          >
            {resultBoom && (
              <div className="pointer-events-none absolute inset-0 flex items-center justify-center text-[42px] animate-[boom_0.9s_ease-out]">
                {room.result === "win" ? "👑💥👑" : room.result === "lose" ? "💥🥊💥" : "⚡💥⚡"}
              </div>
            )}

            <div className="mb-3 flex justify-center">
              <div
                className={cls(
                  "border border-[#8a3918] bg-[linear-gradient(180deg,rgba(104,34,17,0.96),rgba(61,16,8,0.96))] font-black text-[#ffcf73] shadow-[0_0_16px_rgba(255,130,40,0.14)]",
                  ui.resultBanner
                )}
              >
                {resultLabel(room.result)}
              </div>
            </div>

            <p className={cls("text-center font-black", resultTone(room.result), ui.resultSub)}>
              {resultSubLabel(room.result)}
            </p>

            <div
              className={cls(
                "border border-[#8a3718] bg-[rgba(72,18,9,0.65)] text-center",
                ui.resultRewardBox
              )}
            >
              <p className={cls("font-black tracking-tight text-[#ffcf73]", ui.resultReward)}>
                {room.result === "lose" ? "-" : "+"}
                {Math.abs(Math.floor(room.reward || 0)).toLocaleString()} COINS
              </p>
            </div>

            <div className={ui.resultGrid}>
              <div
                className={cls(
                  "border border-[#8a3718] bg-[linear-gradient(180deg,rgba(54,12,7,0.98),rgba(21,5,3,0.98))] text-center",
                  ui.resultLeftCard
                )}
              >
                <div
                  className={cls(
                    "mx-auto flex items-center justify-center border border-[#6f3017] bg-[radial-gradient(circle,rgba(255,160,74,0.12),rgba(28,6,3,0.98)_72%)]",
                    ui.resultArt
                  )}
                >
                  {room.result === "win" ? "👑" : "🥊"}
                </div>
              </div>

              <div
                className={cls(
                  "border border-[#8a3718] bg-[linear-gradient(180deg,rgba(54,12,7,0.98),rgba(21,5,3,0.98))]",
                  ui.resultRightCard
                )}
              >
                <div className="mb-2 flex items-center gap-2">
                  <Trophy className={cls(isShort ? "h-3.5 w-3.5" : "h-4 w-4", "text-[#ffb34d]")} />
                  <p className={cls("font-black text-[#ffcf73]", ui.resultInfoTitle)}>Opponent</p>
                </div>

                <div className={cls("space-y-2", isShort && "space-y-1.5")}>
                  <div
                    className={cls(
                      "border border-[#6f3017] bg-[rgba(18,4,2,0.9)]",
                      ui.resultInfoRow
                    )}
                  >
                    <p className={cls("text-white", isShort ? "text-[11px]" : "text-[13px]")}>
                      {room.opponent_name || "Monkeypunch"}
                    </p>
                  </div>

                  <div
                    className={cls(
                      "border border-[#6f3017] bg-[rgba(18,4,2,0.9)]",
                      ui.resultInfoRow
                    )}
                  >
                    <p className={cls("text-[#c58b69]", ui.resultInfoText)}>
                      Stake: <span className="font-black text-white">{currentStake.toLocaleString()}</span>
                    </p>
                  </div>

                  <div
                    className={cls(
                      "border border-[#6f3017] bg-[rgba(18,4,2,0.9)]",
                      ui.resultInfoRow
                    )}
                  >
                    <p className={cls("text-[#c58b69]", ui.resultInfoText)}>
                      {room.result === "win" ? (
                        <>
                          Earned:{" "}
                          <span className="font-black text-[#ffcf73]">
                            {Math.abs(Math.floor(room.reward || 0)).toLocaleString()}
                          </span>
                        </>
                      ) : (
                        <>
                          Lost:{" "}
                          <span className="font-black text-[#ff9f6b]">
                            {Math.abs(Math.floor(room.reward || 0)).toLocaleString()}
                          </span>
                        </>
                      )}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div className={cls("text-center font-black tracking-[0.06em] text-white", ui.resultEnergy)}>
              {(user.available_energy ?? 0).toLocaleString()} /{" "}
              {((user as { max_energy?: number }).max_energy ?? 40835).toLocaleString()}
            </div>

            <div className={cls("grid grid-cols-2", ui.resultButtons)}>
              <button
                onClick={() => {
                  clearBattle();
                  void startBattle();
                }}
                className={cls(
                  "bg-[linear-gradient(180deg,#d9953c,#b86a21)] font-black text-white shadow-[0_8px_18px_rgba(217,149,60,0.2)]",
                  ui.resultBtn
                )}
              >
                BATTLE AGAIN
              </button>

              <button
                onClick={clearBattle}
                className={cls(
                  "bg-[linear-gradient(180deg,#b54f29,#903818)] font-black text-white shadow-[0_8px_18px_rgba(181,79,41,0.16)]",
                  ui.resultBtn
                )}
              >
                EXIT
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
import UserTap from "../components/UserTap";
import { useUserStore } from "../store/user-store";
import { Link } from "react-router-dom";
import { uesStore } from "@/store";
import { ShieldPlus } from "lucide-react";

export default function Home() {
  const user = useUserStore();
  const { maxLevel } = uesStore();

  const levelFloor = user.level?.from_balance || 0;
  const levelCap = user.level?.to_balance || 1;
  const levelSpan = Math.max(levelCap - levelFloor, 1);
  const progress = Math.min(
    Math.max((((user.balance || 0) - levelFloor) / levelSpan) * 100, 0),
    100
  );
  const remaining = Math.max(levelCap - (user.balance || 0), 0);

  return (
    <div className="flex-1 px-3 pb-20 pt-2 min-[390px]:px-4 min-[430px]:pb-24 min-[430px]:pt-4">
      <div className="flex items-center justify-between gap-2 min-[430px]:gap-3">
        <div className="top-chip flex min-w-0 items-center gap-2 rounded-full px-3 py-1.5 min-[430px]:gap-3 min-[430px]:py-2">
          <img
            src="/images/avatar.png"
            alt="user-avatar"
            className="h-9 w-9 rounded-full object-cover ring-2 ring-[#ff9b4744] min-[430px]:h-10 min-[430px]:w-10"
          />
          <div className="min-w-0">
            <p className="truncate text-[13px] font-semibold text-white min-[430px]:text-sm">
              {user?.first_name || "Player"} {user?.last_name || ""}
            </p>
            <p className="truncate text-[10px] text-white/55 min-[430px]:text-[11px]">
              {remaining > 0
                ? `${remaining.toLocaleString()} to next level`
                : "Level up available"}
            </p>
          </div>
        </div>

        <Link
          to="/earn"
          className="glass-card flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-full min-[430px]:h-14 min-[430px]:w-14"
        >
          <ShieldPlus className="h-4.5 w-4.5 text-[#ffb11f] min-[430px]:h-5 min-[430px]:w-5" />
          <span className="text-[9px] font-bold text-[#ffb11f] min-[430px]:text-[10px]">
            EARN
          </span>
        </Link>
      </div>

      <div className="mt-2 flex items-center gap-2 min-[430px]:mt-4 min-[430px]:gap-3">
        <div className="flex-1 rounded-2xl border border-[#ff8e3c55] bg-[#2a0d08]/70 px-3 py-2 min-[430px]:px-4 min-[430px]:py-3">
          <p className="text-[9px] uppercase tracking-[0.18em] text-white/50 min-[430px]:text-[10px] min-[430px]:tracking-[0.2em]">
            Balance
          </p>
          <p className="mt-1 truncate text-xl font-black text-white min-[430px]:text-2xl">
            {Math.floor(user.balance || 0).toLocaleString()}
          </p>
        </div>

        <div className="rounded-2xl border border-[#ff8e3c55] bg-[#2a0d08]/70 px-3 py-2 text-right min-[430px]:px-4 min-[430px]:py-3">
          <p className="text-[9px] uppercase tracking-[0.18em] text-white/50 min-[430px]:text-[10px] min-[430px]:tracking-[0.2em]">
            Per tap
          </p>
          <p className="mt-1 text-xl font-black text-[#ffc266] min-[430px]:text-2xl">
            {user.earn_per_tap || 0}
          </p>
        </div>
      </div>

      <UserTap />

      <div className="mt-3 rounded-[24px] glass-card p-3 min-[430px]:mt-5 min-[430px]:rounded-[28px] min-[430px]:p-4">
        <div className="flex items-center justify-between text-[13px] min-[430px]:text-sm">
          <div>
            <p className="font-semibold text-white">
              {user.level?.name || "Bronze"}
            </p>
            <p className="text-[11px] text-white/50 min-[430px]:text-xs">
              Level progress
            </p>
          </div>
          <p className="font-bold text-white">
            {user.level?.level || 1}/{maxLevel || 1}
          </p>
        </div>

        <div className="mt-3 h-3 overflow-hidden rounded-full bg-white/10 min-[430px]:mt-4 min-[430px]:h-4">
          <div
            className="h-full rounded-full bg-[linear-gradient(90deg,#ff9a1f_0%,#ff9b10_100%)]"
            style={{ width: `${progress}%` }}
          />
        </div>

        <div className="mt-1 flex items-center justify-between text-[11px] text-white/55 min-[430px]:mt-2 min-[430px]:text-xs">
          <span>{Math.floor(user.balance || 0).toLocaleString()}</span>
          <span>{levelCap.toLocaleString()}</span>
        </div>
      </div>
    </div>
  );
}

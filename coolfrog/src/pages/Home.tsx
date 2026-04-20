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
  const progress = Math.min(Math.max((((user.balance || 0) - levelFloor) / levelSpan) * 100, 0), 100);
  const remaining = Math.max(levelCap - (user.balance || 0), 0);

  return (
    <div className="flex-1 px-4 pb-24 pt-4">
      <div className="flex items-center justify-between gap-3">
        <div className="top-chip flex items-center gap-3 rounded-full px-3 py-2">
          <img
            src="/images/avatar.png"
            alt="user-avatar"
            className="h-10 w-10 rounded-full object-cover ring-2 ring-[#ff9b4744]"
          />
          <div>
            <p className="text-sm font-semibold text-white">
              {user?.first_name || "Player"} {user?.last_name || ""}
            </p>
            <p className="text-[11px] text-white/55">
              {remaining > 0 ? `${remaining.toLocaleString()} to next level` : "Level up available"}
            </p>
          </div>
        </div>

        <Link
          to="/earn"
          className="glass-card flex h-14 w-14 flex-col items-center justify-center rounded-full"
        >
          <ShieldPlus className="h-5 w-5 text-[#ffb11f]" />
          <span className="text-[10px] font-bold text-[#ffb11f]">EARN</span>
        </Link>
      </div>

     <div className="mt-4 flex items-center gap-3">
  <div className="flex-1 rounded-2xl border border-[#ff8e3c55] bg-[#2a0d08]/70 px-4 py-3">
    <p className="text-[10px] uppercase tracking-[0.2em] text-white/50">Balance</p>
    <p className="mt-1 text-2xl font-black text-white">
      {Math.floor(user.balance || 0).toLocaleString()}
    </p>
  </div>

  <div className="rounded-2xl border border-[#ff8e3c55] bg-[#2a0d08]/70 px-4 py-3 text-right">
    <p className="text-[10px] uppercase tracking-[0.2em] text-white/50">Per tap</p>
    <p className="mt-1 text-2xl font-black text-[#ffc266]">
      {user.earn_per_tap || 0}
    </p>
  </div>
</div>

      <UserTap />

      <div className="mt-5 rounded-[28px] glass-card p-4">
        <div className="flex items-center justify-between text-sm">
          <div>
            <p className="font-semibold text-white">{user.level?.name || "Bronze"}</p>
            <p className="text-xs text-white/50">Level progress</p>
          </div>
          <p className="font-bold text-white">
            {user.level?.level || 1}/{maxLevel || 1}
          </p>
        </div>
        <div className="mt-4 h-4 overflow-hidden rounded-full bg-white/10">
          <div
            className="h-full rounded-full bg-[linear-gradient(90deg,#ff9a1f_0%,#ff9b10_100%)]"
            style={{ width: `${progress}%` }}
          />
        </div>
        <div className="mt-2 flex items-center justify-between text-xs text-white/55">
          <span>{Math.floor(user.balance || 0).toLocaleString()}</span>
          <span>{levelCap.toLocaleString()}</span>
        </div>
      </div>
    </div>
  );
}

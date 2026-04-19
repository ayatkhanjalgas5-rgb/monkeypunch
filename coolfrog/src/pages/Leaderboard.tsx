import { useEffect, useMemo, useState } from "react";
import { $http } from "@/lib/http";
import axios from "axios";
import levelConfig from "@/config/level-config";

type LeaderboardUser = {
  id: number;
  display_name: string | null;
  weekly_score: number;
  level_id: number | null;
};

type LeaderboardResponse = {
  items: LeaderboardUser[];
  me: {
    id: number;
    display_name: string;
    weekly_score: number;
    level_id: number | null;
    diamonds_balance: number;
  } | null;
  my_rank: number | null;
  week: {
    ends_at: string;
  };
  rewards: { rank: number; diamonds: number }[];
};

export default function Leaderboard() {
  const [endsAt, setEndsAt] = useState<string | null>(null);
  const [users, setUsers] = useState<LeaderboardUser[]>([]);
  const [me, setMe] = useState<LeaderboardResponse["me"]>(null);
  const [myRank, setMyRank] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [timeLeft, setTimeLeft] = useState("--");

  useEffect(() => {
    const load = async () => {
      try {
        const res = await $http.get<LeaderboardResponse>("/clicker/weekly-leaderboard");
        setUsers(res.data.items || []);
        setMe(res.data.me || null);
        setMyRank(res.data.my_rank ?? null);
        setEndsAt(res.data.week?.ends_at || null);
      } catch (error: unknown) {
        if (axios.isAxiosError(error)) {
          console.error(error.response?.data || error.message);
        } else {
          console.error(error);
        }
      } finally {
        setLoading(false);
      }
    };

    load();
  }, []);

  useEffect(() => {
    if (!endsAt) return;

    const updateTimer = () => {
      const end = new Date(endsAt).getTime();
      const now = Date.now();
      const diff = end - now;

      if (diff <= 0) {
        setTimeLeft("Ended");
        return;
      }

      const days = Math.floor(diff / (1000 * 60 * 60 * 24));
      const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
      const minutes = Math.floor((diff / (1000 * 60)) % 60);
      const seconds = Math.floor((diff / 1000) % 60);

      setTimeLeft(`${days}d ${hours}h ${minutes}m ${seconds}s`);
    };

    updateTimer();
    const interval = setInterval(updateTimer, 1000);
    return () => clearInterval(interval);
  }, [endsAt]);

  const topThree = useMemo(() => users.slice(0, 3), [users]);
  const restUsers = useMemo(() => users.slice(3), [users]);

  const nextGap = useMemo(() => {
    if (!me || !myRank || myRank <= 1 || users.length === 0) return null;

    const targetUser = users.find((_, index) => index + 1 === myRank - 1);
    if (!targetUser) return null;

    const gap = Math.max(0, Math.floor((targetUser.weekly_score || 0) - (me.weekly_score || 0)));
    return gap > 0 ? gap : null;
  }, [me, myRank, users]);

  const renderPodiumCard = (user: LeaderboardUser | undefined, place: 1 | 2 | 3) => {
    if (!user) return null;

    const isMe = me?.id === user.id;
    const wrapperClass = place === 1 ? "order-2 -mt-2" : place === 2 ? "order-1 mt-8" : "order-3 mt-8";
    const ringClass =
      place === 1
        ? "border-[#ffd08a] shadow-[0_0_30px_rgba(255,208,138,0.35)]"
        : place === 2
          ? "border-white/40"
          : "border-[#d28a57]";
    const podiumClass =
      place === 1
        ? "bg-gradient-to-b from-[#ffcf70] via-[#f0a12c] to-[#a84a0a] h-24"
        : place === 2
          ? "bg-gradient-to-b from-[#d8dbe6] via-[#9ca3af] to-[#5b6472] h-20"
          : "bg-gradient-to-b from-[#e8a160] via-[#b4662f] to-[#703615] h-20";

    const badgeText = place === 1 ? "100 💎" : place === 2 ? "50 💎" : "25 💎";

    return (
      <div className={`flex-1 ${wrapperClass}`} key={user.id}>
        <div className="flex flex-col items-center">
          <div className={`relative mb-3 flex h-20 w-20 items-center justify-center rounded-full border-4 bg-[#2a0d0a] ${ringClass}`}>
            {place === 1 && (
              <div className="absolute -top-5 text-2xl drop-shadow-[0_0_10px_rgba(255,208,138,0.8)]">👑</div>
            )}

            <img
              src={levelConfig.frogs[user.level_id || 1]}
              alt="level"
              className="h-16 w-16 object-contain"
              style={{ filter: levelConfig.filter[user.level_id || 1] }}
            />
          </div>

          <div className="mb-2 text-center">
            <p className="max-w-[110px] truncate text-sm font-black text-white">{user.display_name || "Player"}</p>
            <p className="text-[11px] text-white/55">
              Lvl {user.level_id || 1}
              {isMe && <span className="ml-1 text-[#ffd08a]">• YOU</span>}
            </p>
          </div>

          <div className="mb-2 rounded-full border border-white/10 bg-black/20 px-3 py-1 text-xs font-bold text-[#ffd08a]">
            {badgeText}
          </div>

          <div className="mb-3 rounded-full bg-black/30 px-3 py-1 text-sm font-black text-[#ffd08a]">
            {Math.floor(user.weekly_score || 0).toLocaleString()}
          </div>

          <div className={`flex w-full items-center justify-center rounded-t-3xl border border-white/10 ${podiumClass}`}>
            <span className="text-4xl font-black text-white/95">#{place}</span>
          </div>
        </div>
      </div>
    );
  };

  return (
    <div className="px-4 pb-32 pt-4">
      <div className="glass-card-soft relative mb-5 overflow-hidden rounded-[28px] border border-[#ff8a3d]/25 p-5">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,140,60,0.18),transparent_55%)]" />

        <div className="relative flex items-start justify-between gap-3">
          <div>
            <p className="text-xs text-white/40">Leaderboard</p>
            <h1 className="text-[28px] font-black leading-none text-white">Weekly Crown 👑</h1>
            <p className="mt-2 text-sm text-white/55">Fight for top 3 and earn 💎 rewards</p>
          </div>

          <div className="text-right">
            <p className="text-xs text-white/40">Ends in</p>
            <p className="text-sm font-bold text-[#ffd08a]">{timeLeft}</p>
          </div>
        </div>

        {me && (
          <div className="relative mt-5 rounded-2xl border border-[#ff8a3d]/20 bg-black/20 p-4">
            <div className="flex items-center justify-between gap-3">
              <div>
                <p className="text-xs text-white/40">You</p>
                <p className="text-lg font-black text-white">#{myRank ?? "-"} {me.display_name || "Player"}</p>
                <p className="mt-1 text-xs text-white/50">
                  {myRank === 1 ? "You are the Champion" : nextGap ? `${nextGap.toLocaleString()} to next rank` : "Keep climbing"}
                </p>
                {me?.diamonds_balance > 0 && (
                  <p className="mt-2 text-xs font-bold text-[#00e0ff]">💎 {me.diamonds_balance} diamonds</p>
                )}
              </div>

              <div className="text-right">
                <p className="text-xs text-white/40">Weekly Score</p>
                <p className="text-2xl font-black text-[#ffd08a]">{Math.floor(me.weekly_score || 0).toLocaleString()}</p>
              </div>
            </div>
          </div>
        )}
      </div>

      {topThree.length > 0 && (
        <div className="glass-card-soft mb-5 rounded-[28px] border border-[#ff8a3d]/20 px-3 pb-4 pt-6">
          <div className="mb-4 text-center">
            <p className="text-xs uppercase tracking-[0.2em] text-white/35">Podium</p>
            <p className="text-base font-black text-white">Top 3 Fighters</p>
          </div>

          <div className="flex items-end gap-3">
            {renderPodiumCard(topThree[1], 2)}
            {renderPodiumCard(topThree[0], 1)}
            {renderPodiumCard(topThree[2], 3)}
          </div>
        </div>
      )}

      <div className="space-y-3">
        {loading &&
          Array.from({ length: 5 }).map((_, index) => (
            <div
              key={index}
              className="glass-card-soft h-[88px] animate-pulse rounded-2xl border border-white/5"
            />
          ))}

        {!loading &&
          restUsers.map((u, idx) => {
            const rank = idx + 4;
            const isMe = me?.id === u.id;

            return (
              <div
                key={u.id}
                className={`glass-card-soft flex items-center justify-between rounded-2xl px-4 py-4 transition-all ${
                  isMe
                    ? "border border-[#ffd08a] shadow-[0_0_18px_rgba(255,208,138,0.18)]"
                    : "border border-[#ff8a3d]/10"
                }`}
              >
                <div className="flex min-w-0 items-center gap-3">
                  <img
                    src={levelConfig.frogs[u.level_id || 1]}
                    alt="level"
                    className="h-10 w-10 object-contain"
                    style={{ filter: levelConfig.filter[u.level_id || 1] }}
                  />

                  <div className={`w-12 text-lg font-black ${isMe ? "text-[#ffd08a]" : "text-white/60"}`}>
                    #{rank}
                  </div>

                  <div className="min-w-0">
                    <p className="truncate text-xl font-black text-white">
                      {u.display_name || "Player"}
                      {isMe && (
                        <span className="ml-2 rounded-full border border-[#ffd08a]/40 bg-[#ffd08a]/10 px-2 py-0.5 text-xs font-bold text-[#ffd08a] align-middle">
                          YOU
                        </span>
                      )}
                    </p>
                    <p className="text-xs text-white/40">Lvl {u.level_id || 1}</p>
                  </div>
                </div>

                <div className="ml-3 text-right">
                  <p className="text-xs text-white/35">Weekly Score</p>
                  <p className="text-2xl font-black text-[#ffd08a]">{Math.floor(u.weekly_score || 0).toLocaleString()}</p>
                </div>
              </div>
            );
          })}
      </div>

      {me && (
        <div className="glass-card-soft fixed bottom-24 left-4 right-4 z-10 rounded-2xl border border-[#ff8a3d]/25 bg-[rgba(58,11,8,0.92)] px-4 py-3 backdrop-blur-xl">
          <div className="flex items-center justify-between gap-3">
            <div>
              <p className="text-xs text-white/40">Your Rank</p>
              <p className="text-lg font-black text-white">#{myRank ?? "-"}</p>
            </div>

            <div className="text-center">
              <p className="text-xs text-white/40">Weekly Score</p>
              <p className="text-lg font-black text-[#ffd08a]">{Math.floor(me.weekly_score || 0).toLocaleString()}</p>
            </div>

            <div className="text-right">
              <p className="text-xs text-white/40">{myRank === 1 ? "Status" : "Next"}</p>
              <p className="text-sm font-black text-white">
                {myRank === 1 ? "Champion" : nextGap ? `+${nextGap.toLocaleString()}` : "Keep going"}
              </p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

import { Button } from "@/components/ui/button";
import { compactNumber } from "@/lib/utils";
import { uesStore } from "@/store";
import { useUserStore } from "@/store/user-store";
import { useCopyToClipboard } from "@uidotdev/usehooks";
import { Copy, Gift, Star, Users } from "lucide-react";
import { useMemo } from "react";
import { toast } from "react-toastify";
import { useQuery } from "@tanstack/react-query";
import { $http } from "@/lib/http";
import EarnTabs from "@/components/EarnTabs";

type ReferredUser = {
  id: number;
  first_name?: string;
  last_name?: string;
  username?: string;
  level?: { name?: string } | null;
};

const BOT_USERNAME = "monkeypunch_game_bot";

export default function Friends() {
  const [, copy] = useCopyToClipboard();
  const { telegram_id } = useUserStore();
  const { referral, totalReferals } = uesStore();

  const referralCode = useMemo(() => `${telegram_id}`, [telegram_id]);

  const referralLink = useMemo(() => {
  return `https://t.me/${BOT_USERNAME}?start=${referralCode}`;
  }, [referralCode]);

  const shareUrl = useMemo(() => {
    const text = encodeURIComponent("Join my MonkeyPunch room!");
    const url = encodeURIComponent(referralLink);
    return `https://t.me/share/url?url=${url}&text=${text}`;
  }, [referralLink]);

  const referredUsersQuery = useQuery({
    queryKey: ["referred-users"],
    queryFn: async () => {
      try {
        const response = await $http.$get<{ data: ReferredUser[] } | ReferredUser[]>("/referred-users");

        if (Array.isArray(response)) return response;
        if (response && Array.isArray(response.data)) return response.data;

        return [];
      } catch {
        return [];
      }
    },
  });

  const referredUsers = referredUsersQuery.data || [];
  const totalInvited = referredUsers.length || totalReferals || 0;

  return (
    <div className="flex flex-1 flex-col px-4 pb-24 pt-4">
      <EarnTabs />

      <div className="top-chip mt-4 rounded-[26px] p-4">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-xs uppercase tracking-[0.3em] text-[#ffc266]">Referral hub</p>
            <h1 className="mt-1 text-2xl font-black text-white">Invite a friend</h1>
          </div>
          <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#1a7fff33]">
            <Users className="h-6 w-6 text-[#ffc36a]" />
          </div>
        </div>

        <div className="mt-4 rounded-[22px] border border-[#75caff33] bg-[#0a2d57] p-4">
          <div className="flex items-start gap-3">
            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#ff6a1a22]">
              <Gift className="h-5 w-5 text-[#ffb11f]" />
            </div>
            <div>
              <p className="font-semibold text-white">Invite a friend</p>
              <p className="text-sm text-[#61d88f]">
                +{(referral?.base?.welcome || 2500).toLocaleString()} for you
              </p>
            </div>
          </div>

          <div className="mt-3 h-px bg-white/10" />

          <div className="mt-3 flex items-start gap-3">
            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#ff6a1a22]">
              <Star className="h-5 w-5 text-[#ffca4a]" />
            </div>
            <div>
              <p className="font-semibold text-white">Invite premium friend</p>
              <p className="text-sm text-[#61d88f]">
                +{(referral?.premium?.welcome || 5000).toLocaleString()} for you
              </p>
            </div>
          </div>
        </div>

        <div className="mt-4 rounded-[18px] border border-white/10 bg-black/15 px-4 py-3">
          <p className="text-xs uppercase tracking-[0.2em] text-white/40">Your invite link</p>
          <p className="mt-2 break-all text-sm font-medium text-white">{referralLink}</p>
        </div>

        <div className="mt-4 flex gap-3">
          <Button
            className="pill-gold h-14 flex-1 rounded-full text-base font-bold text-white"
            onClick={() => {
              try {
                Telegram.WebApp.openTelegramLink(shareUrl);
              } catch {
                window.open(shareUrl, "_blank");
              }
            }}
          >
            Invite a friend
          </Button>

          <Button
            className="h-14 w-14 rounded-full border border-[#ffc36a44] bg-[#0d3d74] text-white"
            onClick={() => {
              copy(referralLink);
              toast.success("Referral link copied");
            }}
          >
            <Copy className="h-5 w-5" />
          </Button>
        </div>
      </div>

      <div className="glass-card mt-4 flex-1 rounded-[28px] p-4">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-xs uppercase tracking-[0.24em] text-white/45">List friends</p>
            <p className="mt-1 text-lg font-bold text-white">{totalInvited} invited</p>
          </div>
          <div className="rounded-full bg-[#6f180f] px-4 py-2 text-xs font-bold text-[#ffd08a]">
            Live referrals
          </div>
        </div>

        <div className="scroll-hidden mt-4 space-y-3 overflow-y-auto pb-4">
          {referredUsers.length ? (
            referredUsers.map((friend, key) => (
              <div
                key={friend.id}
                className="glass-card-soft flex items-center justify-between rounded-2xl px-4 py-3"
              >
                <div className="flex items-center gap-3">
                  <img
                    src="/images/avatar.png"
                    alt="avatar"
                    className="h-10 w-10 rounded-full object-cover ring-2 ring-[#ffb05c33]"
                  />
                  <div>
                    <p className="text-sm font-semibold text-white">
                      {friend.first_name || friend.username || `Player #${key + 1}`}
                    </p>
                    <p className="text-xs text-white/45">{friend.level?.name || "Rookie"}</p>
                  </div>
                </div>
                <div className="text-right">
                  <p className="text-xs uppercase tracking-[0.2em] text-white/40">Earned</p>
                  <p className="text-sm font-bold text-[#64d98f]">
                    +{compactNumber(referral?.base?.welcome || 2500)}
                  </p>
                </div>
              </div>
            ))
          ) : (
            <div className="flex h-32 items-center justify-center rounded-[22px] border border-dashed border-white/15 text-center text-sm text-white/45">
              No invited friends yet.
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

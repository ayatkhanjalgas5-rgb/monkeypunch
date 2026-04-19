import { Coins, Hand, Wallet2, Trophy, Swords } from "lucide-react";
import { Link, useLocation } from "react-router-dom";
import { cn } from "../lib/utils";

const links = [
  { name: "Earn", link: "/earn", icon: Coins, activePaths: ["/earn", "/friends"] },
  { name: "Tap", link: "/", icon: Hand },
  { name: "Battle", link: "/battle", icon: Swords },
  { name: "Top", link: "/leaderboard", icon: Trophy },
  { name: "Wallet", link: "/wallet", icon: Wallet2 },
];

export default function AppBar() {
  const { pathname } = useLocation();

  return (
    <div className="fixed bottom-3 left-0 z-20 w-full px-4">
      <div className="mx-auto flex max-w-lg items-center gap-2 rounded-[28px] border border-[#ff6a1a33] bg-[#360b09]/95 p-2 shadow-[0_10px_35px_rgba(0,0,0,0.35)] backdrop-blur-xl">
        {links.map((link) => {
          const Icon = link.icon;
          const active = link.activePaths ? link.activePaths.includes(pathname) : pathname === link.link;

          return (
            <Link
              key={link.link}
              to={link.link}
              className={cn(
                "relative flex min-w-0 flex-1 flex-col items-center justify-center gap-1 rounded-2xl px-1 py-2 text-[10px] font-semibold transition",
                active ? "bg-[#78150f] text-white" : "text-white/60"
              )}
            >
              <span
                className={cn(
                  "flex h-9 w-9 items-center justify-center rounded-2xl border text-white/90",
                  active
                    ? "border-[#ff8f3a66] bg-[#ff6a1a22] shadow-[inset_0_1px_0_rgba(255,255,255,0.12)]"
                    : "border-transparent bg-transparent"
                )}
              >
                <Icon className="h-4 w-4" />
              </span>
              <span className="truncate">{link.name}</span>
              {active && (
                <div className="absolute bottom-0 h-1 w-7 rounded-full bg-[#ff9b10] shadow-[0_0_16px_rgba(255,155,16,0.65)]" />
              )}
            </Link>
          );
        })}
      </div>
    </div>
  );
}

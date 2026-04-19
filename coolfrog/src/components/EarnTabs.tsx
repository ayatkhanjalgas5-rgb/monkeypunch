import { Link, useLocation } from "react-router-dom";
import { cn } from "@/lib/utils";

const tabs = [
  { label: "Earn", to: "/earn" },
  { label: "Refs", to: "/friends" },
] as const;

export default function EarnTabs() {
  const { pathname } = useLocation();

  return (
    <div className="top-chip flex items-center justify-between rounded-full p-1">
      {tabs.map((tab) => {
        const active = pathname === tab.to;
        return (
          <Link
            key={tab.to}
            to={tab.to}
            className={cn(
              "flex-1 rounded-full px-4 py-3 text-center text-sm font-semibold transition",
              active ? "pill-blue text-white" : "text-white/60"
            )}
          >
            {tab.label}
          </Link>
        );
      })}
    </div>
  );
}

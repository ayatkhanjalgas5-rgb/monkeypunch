import { useState } from "react";
import {
  Gift,
  Target,
  PlayCircle,
  Send,
  Video,
  RefreshCcw,
  Loader2,
  AlertCircle,
} from "lucide-react";
import { useLocation } from "react-router-dom";
import Friends from "./Friends";
import { useQuery } from "@tanstack/react-query";
import { $http } from "@/lib/http";
import { TaskType, ReferralTaskType, DailyTaskType } from "@/types/TaskType";
import TaskDrawer from "@/components/TaskDrawer";
import ReferralTaskDrawer from "@/components/ReferralTaskDrawer";
import DailyDrawer from "@/components/DailyDrawer";
import EarnTabs from "@/components/EarnTabs";

type EarnTab = "overview" | "referrals";

const socialTaskIcons: Record<string, typeof PlayCircle> = {
  video: Video,
  youtube: Video,
  telegram: Send,
  x: PlayCircle,
  twitter: PlayCircle,
  other: PlayCircle,
};

function LoadingCard({ lines = 2 }: { lines?: number }) {
  return (
    <div className="glass-card-soft rounded-2xl px-4 py-4 animate-pulse">
      <div className="flex items-center gap-3">
        <div className="h-12 w-12 rounded-2xl bg-white/10" />
        <div className="flex-1 space-y-2">
          <div className="h-4 w-32 rounded bg-white/10" />
          {Array.from({ length: lines }).map((_, index) => (
            <div key={index} className="h-3 rounded bg-white/5" />
          ))}
        </div>
      </div>
    </div>
  );
}

function StateCard({
  icon: Icon,
  title,
  text,
  action,
  actionLabel,
}: {
  icon: typeof AlertCircle;
  title: string;
  text: string;
  action?: () => void;
  actionLabel?: string;
}) {
  return (
    <div className="glass-card-soft rounded-2xl px-4 py-5 text-center">
      <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#922214] text-[#ffd08a]">
        <Icon className="h-5 w-5" />
      </div>
      <p className="mt-3 font-semibold text-white">{title}</p>
      <p className="mt-1 text-sm text-white/55">{text}</p>
      {action && actionLabel ? (
        <button
          onClick={action}
          className="mt-4 inline-flex items-center gap-2 rounded-full bg-[#6f180f] px-4 py-2 text-sm font-bold text-[#ffd08a]"
        >
          <RefreshCcw className="h-4 w-4" />
          {actionLabel}
        </button>
      ) : null}
    </div>
  );
}

function EarnOverview() {
  const [selectedTask, setSelectedTask] = useState<TaskType | null>(null);
  const [selectedReferralTask, setSelectedReferralTask] = useState<ReferralTaskType | null>(null);
  const [dailyOpen, setDailyOpen] = useState(false);

  const tasksQuery = useQuery({
    queryKey: ["tasks"],
    queryFn: () => $http.$get<TaskType[]>("/clicker/tasks"),
    staleTime: 30_000,
    retry: 1,
  });

  const referralTasksQuery = useQuery({
    queryKey: ["referral-tasks"],
    queryFn: () => $http.$get<ReferralTaskType[]>("/clicker/referral-tasks"),
    staleTime: 30_000,
    retry: 1,
  });

  const dailyTasksQuery = useQuery({
    queryKey: ["daily-tasks"],
    queryFn: () => $http.$get<DailyTaskType[]>("/clicker/daily-tasks"),
    staleTime: 30_000,
    retry: 1,
  });

  const safeTasks = Array.isArray(tasksQuery.data) ? tasksQuery.data : [];
  const dailyTasks = Array.isArray(dailyTasksQuery.data) ? dailyTasksQuery.data : [];
  const referralTasks = Array.isArray(referralTasksQuery.data) ? referralTasksQuery.data : [];

  const socialTasks = [
    safeTasks.find((task) => /twitter|x/i.test(`${task?.name ?? ""} ${task?.type ?? ""} ${task?.link ?? ""}`)),
    safeTasks.find((task) => /telegram/i.test(`${task?.name ?? ""} ${task?.type ?? ""} ${task?.link ?? ""}`)),
    safeTasks.find((task) => /youtube|video/i.test(`${task?.name ?? ""} ${task?.type ?? ""} ${task?.link ?? ""}`)),
    ...safeTasks,
  ]
    .filter(Boolean)
    .filter((task, index, arr) => arr.findIndex((t) => t?.id === task?.id) === index)
    .slice(0, 6) as TaskType[];

  const dailyReady = dailyTasks.find((item) => item?.available && !item?.completed) ?? null;
  const hasDailyReady = dailyTasks.some((item) => item?.available && !item?.completed);
  const referralMilestones = referralTasks.slice(0, 6);

  return (
    <>
      <div className="flex flex-1 flex-col px-4 pb-24 pt-4">
        <div className="glass-card mt-4 rounded-[28px] p-4">
          <div className="flex items-center justify-between gap-3">
            <div>
              <p className="text-xs uppercase tracking-[0.24em] text-white/45">Social tasks</p>
              <p className="mt-1 text-xl font-black text-white">X + Telegram + YouTube</p>
            </div>
            <div className="rounded-full bg-[#6f180f] px-4 py-2 text-xs font-bold text-[#ffd08a]">Task hub</div>
          </div>

          <div className="mt-4 grid gap-3">
            {tasksQuery.isLoading ? (
              <>
                <LoadingCard />
                <LoadingCard />
                <LoadingCard />
              </>
            ) : tasksQuery.isError ? (
              <StateCard
                icon={AlertCircle}
                title="Tasks failed to load"
                text="The social task list could not be loaded right now."
                action={() => tasksQuery.refetch()}
                actionLabel="Retry"
              />
            ) : socialTasks.length === 0 ? (
              <StateCard
                icon={Target}
                title="No social tasks yet"
                text="Create X, Telegram or YouTube tasks in the admin panel and they will appear here."
              />
            ) : (
              socialTasks.map((task) => {
                const Icon = socialTaskIcons[(task.type || "").toLowerCase()] || PlayCircle;
                const status = task.is_rewarded
                  ? "Claimed"
                  : task.is_submitted
                    ? "Claim reward"
                    : task.action_name || "Open";

                return (
                  <button
                    key={task.id}
                    onClick={() => {
  if (task.link) {
    if (window.Telegram?.WebApp) {
      window.Telegram.WebApp.openLink(task.link);
    } else {
      window.open(task.link, "_blank");
    }
  }

  setSelectedTask(task);
}}
                    className="glass-card-soft flex items-center justify-between rounded-2xl px-4 py-4 text-left"
                  >
                    <div className="flex items-center gap-3">
                      <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#922214] text-[#ffd08a]">
                        <Icon className="h-5 w-5" />
                      </div>
                      <div>
                        <p className="font-semibold text-white">{task.name}</p>
                        <p className="text-xs text-white/45 line-clamp-2">{task.description || task.link}</p>
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="text-sm font-black text-[#ffd08a]">
                        +{Math.floor(task.reward_coins || 0).toLocaleString()}
                      </p>
                      <p className="text-xs text-white/45">{status}</p>
                    </div>
                  </button>
                );
              })
            )}
          </div>
        </div>

        <div className="mt-4 grid gap-4 md:grid-cols-2">
          <button onClick={() => setDailyOpen(true)} className="glass-card rounded-[28px] p-4 text-left">
            <div className="flex items-center justify-between gap-3">
              <div>
                <p className="text-xs uppercase tracking-[0.24em] text-white/45">Daily rewards</p>
                <p className="mt-1 text-xl font-black text-white">Login rewards</p>
                <p className="mt-2 text-sm text-white/55">
                  {dailyTasksQuery.isLoading
                    ? "Loading streak rewards..."
                    : dailyTasksQuery.isError
                      ? "Could not load daily rewards"
                      : hasDailyReady && dailyReady
                        ? `${dailyReady.name} ready to claim`
                        : "Track your streak rewards"}
                </p>
              </div>
              <div className="rounded-full bg-[#6f180f] px-4 py-2 text-sm font-bold text-[#ffd08a]">
                {dailyTasksQuery.isLoading ? (
                  <Loader2 className="h-4 w-4 animate-spin" />
                ) : hasDailyReady && dailyReady ? (
                  `+${Math.floor(dailyReady.reward_coins || 0).toLocaleString()}`
                ) : (
                  "Open"
                )}
              </div>
            </div>
          </button>

          <div className="glass-card rounded-[28px] p-4">
            <div className="flex items-center justify-between gap-3">
              <div>
                <p className="text-xs uppercase tracking-[0.24em] text-white/45">Referral milestones</p>
                <p className="mt-1 text-xl font-black text-white">Invite rewards</p>
              </div>
              <div className="rounded-full bg-[#6f180f] px-4 py-2 text-xs font-bold text-[#ffd08a]">
                {referralMilestones.length} milestones
              </div>
            </div>

            <div className="mt-4 grid gap-3">
              {referralTasksQuery.isLoading ? (
                <>
                  <LoadingCard lines={1} />
                  <LoadingCard lines={1} />
                </>
              ) : referralTasksQuery.isError ? (
                <StateCard
                  icon={AlertCircle}
                  title="Referral rewards unavailable"
                  text="Milestone rewards could not be loaded right now."
                  action={() => referralTasksQuery.refetch()}
                  actionLabel="Retry"
                />
              ) : referralMilestones.length === 0 ? (
                <StateCard
                  icon={Gift}
                  title="No referral milestones yet"
                  text="Add referral milestones in the admin panel to show invite rewards here."
                />
              ) : (
                referralMilestones.map((task) => (
                  <button
                    key={task.id}
                    onClick={() => setSelectedReferralTask(task)}
                    className="glass-card-soft flex items-center justify-between rounded-2xl px-4 py-3 text-left"
                  >
                    <div>
                      <p className="font-semibold text-white">{task.title}</p>
                      <p className="text-xs text-white/45">Need {task.number_of_referrals} referrals</p>
                    </div>
                    <div className="text-right">
                      <p className="text-sm font-black text-[#ffd08a]">
                        +{Math.floor(task.reward || 0).toLocaleString()}
                      </p>
                      <p className="text-xs text-white/45">{task.is_completed ? "Claimed" : "Open"}</p>
                    </div>
                  </button>
                ))
              )}
            </div>
          </div>
        </div>
      </div>

      <TaskDrawer task={selectedTask} open={!!selectedTask} onOpenChange={(open) => !open && setSelectedTask(null)} />
      <ReferralTaskDrawer task={selectedReferralTask} open={!!selectedReferralTask} onOpenChange={(open) => !open && setSelectedReferralTask(null)} />
      <DailyDrawer open={dailyOpen} onOpenChange={setDailyOpen} />
    </>
  );
}

export default function Earn() {
  const { pathname } = useLocation();

  const activeTab: EarnTab = pathname === "/friends" ? "referrals" : "overview";

  return (
    <div className="flex flex-1 flex-col">
      <div className="sticky top-0 z-10 px-4 pt-4">
        <EarnTabs />
      </div>

      {activeTab === "referrals" ? <Friends /> : <EarnOverview />}
    </div>
  );
}

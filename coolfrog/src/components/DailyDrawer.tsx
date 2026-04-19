import { cn, compactNumber } from "@/lib/utils";
import { Button } from "./ui/button";
import Drawer, { DrawerProps } from "./ui/drawer";
import { useMutation, useQuery } from "@tanstack/react-query";
import { $http } from "@/lib/http";
import { toast } from "react-toastify";
import { Gift, Loader2Icon, AlertCircle } from "lucide-react";
import { DailyTaskType } from "@/types/TaskType";
import { useUserStore } from "@/store/user-store";

export default function DailyDrawer({ ...props }: DrawerProps) {
  const dailyTasks = useQuery({
    queryKey: ["daily-tasks"],
    queryFn: () => $http.$get<DailyTaskType[]>("/clicker/daily-tasks"),
    staleTime: 30_000,
    retry: 1,
  });

  const claimTaskMutation = useMutation({
    mutationFn: () => $http.post<{ message: string; balance: number }>(`/clicker/claim-daily-task`),
    onSuccess: (response) => {
      toast.success(response.data.message);
      dailyTasks.refetch();
      useUserStore.setState({
        balance: response.data.balance,
      });
    },
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    onError: (error: any) => {
      toast.error(error.response?.data?.message || "Something went wrong");
    },
  });

  const dailyItems = Array.isArray(dailyTasks.data) ? dailyTasks.data : [];
  const canClaim = dailyItems.some((item) => item?.available && !item?.completed);

  return (
    <Drawer {...props}>
      <img src="/images/coins.png" alt="coins-3" className="mx-auto h-28" />
      <h2 className="mt-6 text-center text-2xl font-bold">Daily Reward</h2>
      <p className="mt-2.5 text-center font-medium">Acquire coins for logging into the game daily without skipping</p>
      <div className="mt-10 grid max-h-64 grid-cols-4 gap-3 overflow-y-auto">
        {dailyTasks.isLoading ? (
          Array.from({ length: 8 }).map((_, key) => (
            <div key={key} className="rounded-xl bg-white/10 px-4 py-6 opacity-60 animate-pulse" />
          ))
        ) : dailyTasks.isError ? (
          <div className="col-span-4 rounded-2xl bg-white/10 px-4 py-6 text-center">
            <AlertCircle className="mx-auto h-6 w-6 text-[#ffd08a]" />
            <p className="mt-2 font-semibold">Daily rewards could not be loaded</p>
            <Button className="mt-4" onClick={() => dailyTasks.refetch()}>
              Retry
            </Button>
          </div>
        ) : dailyItems.length ? (
          dailyItems.map((item, key) => (
            <div
              key={key}
              className={cn(
                "flex flex-col items-center rounded-xl border-2 border-transparent bg-white/10 px-4 py-2.5 opacity-40",
                item.completed && "border-[#27D46C] bg-[#27D46C]/20 opacity-100",
                item.available && !item.completed && "border-primary opacity-100"
              )}
            >
              <p className="text-sm font-medium">{item.name}</p>
              <img src="/images/coin.png" alt="coin" className="h-5 w-5 object-contain" />
              <p className={cn("font-bold text-primary", item.completed && "text-[#27D46C]")}>
                {compactNumber(item.reward_coins)}
              </p>
            </div>
          ))
        ) : (
          <div className="col-span-4 rounded-2xl bg-white/10 px-4 py-6 text-center">
            <Gift className="mx-auto h-6 w-6 text-[#ffd08a]" />
            <p className="mt-2 font-semibold">No daily rewards configured</p>
          </div>
        )}
      </div>
      <Button
        className="mt-6 w-full"
        disabled={!canClaim || claimTaskMutation.isPending || dailyTasks.isLoading}
        onClick={() => claimTaskMutation.mutate()}
      >
        {claimTaskMutation.isPending && <Loader2Icon className="mr-2 h-6 w-6 animate-spin" />}
        {canClaim ? "Claim" : "Nothing to claim"}
      </Button>
    </Drawer>
  );
}
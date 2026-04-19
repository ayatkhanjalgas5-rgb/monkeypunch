import { TaskType } from "../types/TaskType";
import { Button } from "./ui/button";
import Drawer, { DrawerProps } from "./ui/drawer";
import Price from "./Price";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { $http } from "@/lib/http";
import { toast } from "react-toastify";
import { ExternalLink, Loader2Icon, CheckCircle2 } from "lucide-react";
import { useUserStore } from "@/store/user-store";

export default function TaskDrawer({
  task,
  ...props
}: DrawerProps & {
  task: TaskType | null;
}) {
  const queryClient = useQueryClient();

  const submitMutation = useMutation({
    mutationFn: () => $http.post<{ message: string }>(`/clicker/tasks/${task?.id}`),
    onSuccess: (response) => {
      toast.success(response?.data?.message || "Task started successfully");
      queryClient.invalidateQueries({ queryKey: ["tasks"] });
      if (task) {
        task.is_submitted = true;
        task.submitted_at = new Date().toISOString();
      }
      if (task?.link) {
        window.open(task.link, "_blank", "noopener,noreferrer");
      }
    },
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    onError: (error: any) => {
      toast.error(error?.response?.data?.message || "Could not open task");
    },
  });

  const claimMutation = useMutation({
    mutationFn: () => $http.post<{ message: string; balance?: number }>(`/clicker/tasks/${task?.id}/claim`),
    onSuccess: (response) => {
      toast.success(response?.data?.message || "Reward claimed");
      queryClient.invalidateQueries({ queryKey: ["tasks"] });
      if (task) {
        task.is_rewarded = true;
      }
      useUserStore.setState((state) => {
        state.balance = response.data.balance ?? state.balance + (task?.reward_coins || 0);
        return state;
      });
      props.onOpenChange?.(false);
    },
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    onError: (error: any) => {
      toast.error(error?.response?.data?.message || "Could not claim reward");
    },
  });

  if (!task) return null;

  const startLabel = task.is_submitted ? "Open task again" : task.action_name || "Open task";

  return (
    <Drawer {...props}>
      <img
        src={task.image || (task.type === "video" ? "/images/youtube.png" : "/images/bounty.png")}
        alt={task.name}
        className="mx-auto h-24 object-contain"
      />
      <h2 className="mt-9 text-center text-2xl font-medium">{task.name}</h2>
      <div className="mx-auto mt-4 w-fit rounded-full border-2 border-dashed border-primary px-5 py-2">
        <Price amount={task.reward_coins.toLocaleString()} className="justify-center text-xl" />
      </div>
      <p className="mt-6 text-center text-white/80">
        {!task.is_submitted
          ? "Open the task link first, then come back and claim your reward."
          : task.is_rewarded
            ? "Reward already claimed."
            : "Task opened. Claim your reward when you are done."}
      </p>

      {!task.is_rewarded ? (
        <>
          <Button className="mt-12 w-full" disabled={submitMutation.isPending} onClick={() => submitMutation.mutate()}>
            {submitMutation.isPending ? <Loader2Icon className="mr-2 h-6 w-6 animate-spin" /> : <ExternalLink className="mr-2 h-5 w-5" />}
            {startLabel}
          </Button>

          <Button
            className="mt-6 w-full"
            disabled={claimMutation.isPending || !task.is_submitted}
            onClick={() => claimMutation.mutate()}
          >
            {claimMutation.isPending ? <Loader2Icon className="mr-2 h-6 w-6 animate-spin" /> : <CheckCircle2 className="mr-2 h-5 w-5" />}
            Claim reward
          </Button>
        </>
      ) : (
        <Button className="mt-12 w-full" disabled>
          <CheckCircle2 className="mr-2 h-5 w-5" />
          Claimed
        </Button>
      )}
    </Drawer>
  );
}

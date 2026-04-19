import { create } from "zustand";

type Click = {
  id: string;
  value: number;
  style: React.CSSProperties;
  createdAt: number;
};

type ClicksStore = {
  clicks: Click[];
  addClick: (click: Omit<Click, "createdAt">) => void;
  removeClick: (id: string) => void;
  clearOldClicks: () => void;
};

const MAX_FLOATING_CLICKS = 8;
const CLICK_LIFETIME_MS = 700;

export const useClicksStore = create<ClicksStore>((set) => ({
  clicks: [],

  addClick: (click) => {
    const now = Date.now();

    set((state) => {
      const freshClicks = state.clicks.filter(
        (item) => now - item.createdAt < CLICK_LIFETIME_MS
      );

      return {
        clicks: [
          ...freshClicks,
          {
            ...click,
            createdAt: now,
          },
        ].slice(-MAX_FLOATING_CLICKS),
      };
    });
  },

  removeClick: (id) => {
    set((state) => ({
      clicks: state.clicks.filter((click) => click.id !== id),
    }));
  },

  clearOldClicks: () => {
    const now = Date.now();

    set((state) => ({
      clicks: state.clicks.filter(
        (click) => now - click.createdAt < CLICK_LIFETIME_MS
      ),
    }));
  },
}));

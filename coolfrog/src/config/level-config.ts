// level-config.ts

const MAX_IMAGE_TIER = 5;

// 🔒 Level-ді қауіпсіз диапазонға түсіреміз (1–5)
const normalizeLevel = (level?: number | null): number => {
  const safe = Number(level || 1);

  if (!Number.isFinite(safe) || safe < 1) return 1;
  if (safe > MAX_IMAGE_TIER) return MAX_IMAGE_TIER;

  return Math.floor(safe);
};

// 🐸 Level images
const frogImages: Record<number, string> = {
  1: "/images/levels/Frog-1.png",
  2: "/images/levels/Frog-2.png",
  3: "/images/levels/Frog-3.png",
  4: "/images/levels/Frog-4.png",
  5: "/images/levels/Frog-5.png",
};

// ✨ Glow filters
const frogFilters: Record<number, string> = {
  1: "drop-shadow(0 0 40px rgba(255,140,60,0.35))",
  2: "drop-shadow(0 0 48px rgba(255,140,60,0.40))",
  3: "drop-shadow(0 0 56px rgba(255,140,60,0.45))",
  4: "drop-shadow(0 0 64px rgba(255,140,60,0.50))",
  5: "drop-shadow(0 0 72px rgba(255,140,60,0.55))",
};

// 🌌 Background
const frogBgs: Record<number, string> = {
  1: "/images/levels/Frog-bg.png",
  2: "/images/levels/Frog-bg.png",
  3: "/images/levels/Frog-bg.png",
  4: "/images/levels/Frog-bg.png",
  5: "/images/levels/Frog-bg.png",
};

// 🚀 Main config
const levelConfig = {
  frogs: frogImages,
  filter: frogFilters,
  bg: frogBgs,

  // 🔑 ең маңызды helper
  getLevelKey(level?: number | null) {
    return normalizeLevel(level);
  },

  getFrog(level?: number | null) {
    return frogImages[normalizeLevel(level)];
  },

  getFilter(level?: number | null) {
    return frogFilters[normalizeLevel(level)];
  },

  getBg(level?: number | null) {
    return frogBgs[normalizeLevel(level)];
  },
};

export default levelConfig;
export const isDemoModeEnabled =
  import.meta.env.DEV && import.meta.env.VITE_ENABLE_DEMO_MODE === "true";

export const isProductionBuild = import.meta.env.PROD;

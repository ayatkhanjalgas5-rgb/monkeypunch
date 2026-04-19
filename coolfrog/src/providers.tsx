import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { PropsWithChildren, useMemo } from "react";
import { ToastContainer } from "react-toastify";
import { TonConnectUIProvider } from "@tonconnect/ui-react";

import "react-toastify/dist/ReactToastify.css";

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
    },
  },
});

export default function Providers({ children }: PropsWithChildren) {
  const manifestBase = import.meta.env.VITE_APP_URL || window.location.origin;

  const manifestUrl = useMemo(() => {
    const manifest = {
      url: manifestBase,
      name: "MonkeyPunch",
      iconUrl: `${manifestBase.replace(/\/$/, "")}/icon-192.png`,
      termsOfUseUrl: `${manifestBase.replace(/\/$/, "")}/terms`,
      privacyPolicyUrl: `${manifestBase.replace(/\/$/, "")}/privacy`,
    };

    return URL.createObjectURL(new Blob([JSON.stringify(manifest)], { type: "application/json" }));
  }, [manifestBase]);

  return (
    <QueryClientProvider client={queryClient}>
      <TonConnectUIProvider manifestUrl={manifestUrl}>{children}</TonConnectUIProvider>

      <ToastContainer theme="dark" position="top-center" hideProgressBar stacked />
    </QueryClientProvider>
  );
}

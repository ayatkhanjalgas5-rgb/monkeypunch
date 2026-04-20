import { Outlet, useLocation, useNavigate } from "react-router-dom";
import AppBar from "../AppBar";
import { useEffect } from "react";
import { useQuery } from "@tanstack/react-query";
import { $http } from "@/lib/http";
import { PopupMessageType } from "@/types/PopupMessageType";
import PopupMessageDialog from "../PopupMessageDialog";

export default function Layout() {
  const { pathname } = useLocation();
  const navigate = useNavigate();

  const popupMessgae = useQuery({
    queryKey: ["popup-message"],
    queryFn: () => $http.$get<PopupMessageType>("/popups"),
  });

  useEffect(() => {
    if (pathname !== "/") {
      window.Telegram?.WebApp?.BackButton?.show();
    } else {
      window.Telegram?.WebApp?.BackButton?.hide();
    }
  }, [pathname]);

  useEffect(() => {
    const backButton = window.Telegram?.WebApp?.BackButton;
    if (!backButton) return;

    const handleBack = () => navigate("/");

    backButton.onClick(handleBack);

    return () => {
      backButton.offClick(handleBack);
    };
  }, [navigate]);

  return (
    <main className="app-shell mx-auto flex h-[--tg-viewport-height] w-full max-w-lg flex-col text-white">
      <Outlet />
      <AppBar />
      <PopupMessageDialog message={popupMessgae.data} />
    </main>
  );
}

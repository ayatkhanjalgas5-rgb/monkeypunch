import { useEffect, useMemo, useState } from "react";
import { useUserStore } from "@/store/user-store";
import { AlertTriangle, ArrowDownToLine, Landmark, Loader2, Wallet2 } from "lucide-react";
import { toast } from "react-toastify";
import { $http } from "@/lib/http";
import { TonConnectButton, useTonAddress } from "@tonconnect/ui-react";
import axios from "axios";

type TransactionItem = {
  id: number;
  type: string;
  amount: number;
  balance_before: number;
  balance_after: number;
  created_at: string;
  meta?: Record<string, unknown> | null;
};

type WithdrawItem = {
  id: number;
  amount: number;
  wallet_address: string;
  network?: string;
  status: "pending" | "approved" | "rejected" | "paid";
  tx_hash?: string | null;
  admin_note?: string | null;
  processed_at?: string | null;
  paid_at?: string | null;
  created_at: string;
};

type WithdrawRequestsResponse = {
  items: WithdrawItem[];
  pending_total: number;
  wallet_address: string | null;
  wallet_provider?: string | null;
};

type TransactionsResponse = {
  items: TransactionItem[];
};

type WalletSummaryResponse = {
  summary: {
    balance: number;
    locked_balance: number;
    wallet_address: string | null;
    wallet_provider?: string | null;
  };
};

type ConnectWalletResponse = {
  ton_wallet: string;
  wallet_provider?: string;
};

type CreateWithdrawResponse = {
  balance: number;
  pending_total: number;
  message: string;
};

const MIN_WITHDRAW = 10_000;
const TON_ADDRESS_PATTERN = /^(UQ|EQ|kQ|0Q)[A-Za-z0-9_-]{40,120}$/;

function normalizeWalletAddress(value: string) {
  return value.trim().replace(/\s+/g, "");
}

function isValidTonAddress(value: string) {
  return TON_ADDRESS_PATTERN.test(normalizeWalletAddress(value));
}

function parseWithdrawAmount(value: string) {
  const digitsOnly = value.replace(/[^\d]/g, "");
  return digitsOnly ? Number(digitsOnly) : 0;
}

export default function Wallet() {
  const user = useUserStore();
  const connectedAddress = useTonAddress(false);
  const [walletAddress, setWalletAddress] = useState(user.ton_wallet || "");
  const [withdraw, setWithdraw] = useState(String(MIN_WITHDRAW));
  const [loading, setLoading] = useState(false);
  const [savingWallet, setSavingWallet] = useState(false);
  const [pendingTotal, setPendingTotal] = useState(0);
  const [items, setItems] = useState<WithdrawItem[]>([]);
  const [transactions, setTransactions] = useState<TransactionItem[]>([]);

  const normalizedWalletAddress = useMemo(
    () => normalizeWalletAddress(walletAddress || user.ton_wallet || ""),
    [walletAddress, user.ton_wallet]
  );

  const withdrawAmount = useMemo(() => parseWithdrawAmount(withdraw), [withdraw]);

  const walletValid = useMemo(
    () => Boolean(normalizedWalletAddress) && isValidTonAddress(normalizedWalletAddress),
    [normalizedWalletAddress]
  );

  const canCreateWithdraw =
    walletValid &&
    withdrawAmount >= MIN_WITHDRAW &&
    withdrawAmount <= Math.floor(user.balance || 0) &&
    !loading;

  const displayWallet = useMemo(
    () => normalizedWalletAddress || "TON wallet not set",
    [normalizedWalletAddress]
  );

  const withdrawValidationMessage = useMemo(() => {
    if (!normalizedWalletAddress) return "Connect or save a TON wallet first.";
    if (!walletValid) return "Enter a valid TON wallet address.";
    if (!withdrawAmount) return "Enter a withdraw amount.";
    if (withdrawAmount < MIN_WITHDRAW) {
      return `Minimum withdraw is ${MIN_WITHDRAW.toLocaleString()}.`;
    }
    if (withdrawAmount > Math.floor(user.balance || 0)) {
      return "Withdraw amount cannot exceed your balance.";
    }
    return null;
  }, [normalizedWalletAddress, walletValid, withdrawAmount, user.balance]);

  const loadWithdraws = async () => {
    try {
      setLoading(true);

      const [withdrawData, txData, walletData] = await Promise.all([
        $http.$get<WithdrawRequestsResponse>("/clicker/withdraw-requests"),
        $http.$get<TransactionsResponse>("/clicker/transactions"),
        $http.$get<WalletSummaryResponse>("/clicker/wallet"),
      ]);

      setItems(withdrawData.items || []);
      setTransactions(txData.items || []);
      setPendingTotal(withdrawData.pending_total || 0);
      setWalletAddress(
        normalizeWalletAddress(
          withdrawData.wallet_address ||
            connectedAddress ||
            walletData.summary.wallet_address ||
            ""
        )
      );

      useUserStore.setState({
        balance: walletData.summary.balance,
        ton_wallet: withdrawData.wallet_address || walletData.summary.wallet_address || null,
        wallet_provider:
          withdrawData.wallet_provider || walletData.summary.wallet_provider || null,
      });
    } catch {
      toast.error("Wallet data load failed");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadWithdraws();
  }, []);

  useEffect(() => {
    if (!connectedAddress) return;

    const normalizedConnectedAddress = normalizeWalletAddress(connectedAddress);
    setWalletAddress(normalizedConnectedAddress);

    const syncWallet = async () => {
      try {
        setSavingWallet(true);

        const { data } = await $http.post<ConnectWalletResponse>(
          "/clicker/wallet/connect",
          {
            ton_wallet: normalizedConnectedAddress,
            wallet_provider: "tonconnect",
          }
        );

        useUserStore.setState({
          ton_wallet: data.ton_wallet,
          wallet_provider: data.wallet_provider || "tonconnect",
        });
      } catch {
        toast.error("TON wallet sync failed");
      } finally {
        setSavingWallet(false);
      }
    };

    if (normalizedConnectedAddress !== user.ton_wallet) {
      syncWallet();
    }
  }, [connectedAddress, user.ton_wallet]);

  const saveWallet = async () => {
    const sanitizedWalletAddress = normalizeWalletAddress(walletAddress);

    if (!sanitizedWalletAddress) {
      toast.error("Enter wallet address");
      return;
    }

    if (!isValidTonAddress(sanitizedWalletAddress)) {
      toast.error("Enter a valid TON wallet address");
      return;
    }

    try {
      setSavingWallet(true);

      const { data } = await $http.post<ConnectWalletResponse>(
        "/clicker/set-ton-wallet",
        {
          ton_wallet: sanitizedWalletAddress,
          wallet_provider: connectedAddress ? "tonconnect" : "manual",
        }
      );

      setWalletAddress(normalizeWalletAddress(data.ton_wallet || sanitizedWalletAddress));
      useUserStore.setState({
        ton_wallet: data.ton_wallet || sanitizedWalletAddress,
        wallet_provider: data.wallet_provider || "manual",
      });

      toast.success("Wallet saved");
    } catch (error: unknown) {
      if (axios.isAxiosError(error)) {
        toast.error(
          (error.response?.data as { message?: string } | undefined)?.message ||
            "Wallet save failed"
        );
      } else {
        toast.error("Wallet save failed");
      }
    } finally {
      setSavingWallet(false);
    }
  };

  const createWithdraw = async () => {
    if (withdrawValidationMessage) {
      toast.error(withdrawValidationMessage);
      return;
    }

    try {
      setLoading(true);

      const { data } = await $http.post<CreateWithdrawResponse>(
        "/clicker/withdraw-requests",
        {
          amount: withdrawAmount,
          wallet_address: normalizedWalletAddress,
          network: "TON",
          client_request_id: `wd-${Date.now()}-${withdrawAmount}`,
        }
      );

      useUserStore.setState({
        balance: data.balance,
        ton_wallet: normalizedWalletAddress,
      });

      setPendingTotal(data.pending_total || 0);
      setWithdraw(String(MIN_WITHDRAW));
      toast.success(data.message || "Withdraw request created");
      await loadWithdraws();
    } catch (error: unknown) {
      if (axios.isAxiosError(error)) {
        toast.error(
          (error.response?.data as { message?: string } | undefined)?.message ||
            "Withdraw request failed"
        );
      } else {
        toast.error("Withdraw request failed");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex flex-1 flex-col px-4 pb-24 pt-4">
      <div className="top-chip flex items-center justify-between rounded-full p-1">
        {["Wallet", "TON Connect", "Withdraw", "History"].map((tab, index) => (
          <button
            key={tab}
            className={
              index === 0
                ? "pill-blue flex-1 rounded-full px-4 py-3 text-sm font-bold text-white"
                : "flex-1 rounded-full px-4 py-3 text-sm font-semibold text-white/60"
            }
          >
            {tab}
          </button>
        ))}
      </div>

      <div className="glass-card mt-4 rounded-[30px] p-5 text-center">
        <div className="mx-auto flex h-40 w-40 items-center justify-center rounded-full bg-[radial-gradient(circle_at_top,_rgba(255,196,92,0.88),_rgba(126,28,18,0.96)_60%,_rgba(28,4,4,1)_100%)] shadow-[0_0_50px_rgba(255,120,40,0.28)]">
          <Wallet2 className="h-16 w-16 text-white" />
        </div>

        <h1 className="mt-6 text-4xl font-black text-white">TON wallet</h1>
        <p className="mt-3 text-sm text-white/55">
          Connect with TON Connect or save an address manually. Withdraw requests
          are stored in the backend and tracked in the admin panel.
        </p>

        <div className="mt-5 flex justify-center [&_button]:!rounded-full [&_button]:!bg-white [&_button]:!text-black">
          <TonConnectButton />
        </div>

        <div className="mt-5 rounded-[28px] glass-card-soft p-4 text-left">
          <div className="flex items-center gap-3 text-xs uppercase tracking-[0.2em] text-white/45">
            <Landmark className="h-4 w-4" />
            Wallet address
          </div>

          <input
            value={walletAddress}
            onChange={(e) => setWalletAddress(normalizeWalletAddress(e.target.value))}
            className="mt-3 w-full bg-transparent text-sm font-semibold text-white outline-none"
            placeholder="UQ..."
            autoCapitalize="none"
            autoCorrect="off"
            spellCheck={false}
          />

          <div className="mt-2 text-xs text-white/45">
            Provider: {connectedAddress ? "tonconnect" : user.wallet_provider || "manual"}
          </div>

          {!walletValid && normalizedWalletAddress ? (
            <div className="mt-3 inline-flex items-center gap-2 rounded-full bg-[#4d130f] px-3 py-2 text-xs font-semibold text-[#ffd08a]">
              <AlertTriangle className="h-4 w-4" />
              Invalid TON wallet format
            </div>
          ) : null}

          <div className="mt-4 grid grid-cols-2 gap-3">
            <button
              onClick={saveWallet}
              disabled={savingWallet}
              className="pill-blue h-12 rounded-full text-sm font-bold text-white disabled:opacity-60"
            >
              {savingWallet ? "Saving..." : "Save wallet"}
            </button>

            <button
              type="button"
              onClick={() => {
                navigator.clipboard.writeText(displayWallet);
                toast.success("Wallet copied");
              }}
              className="pill-gold h-12 rounded-full text-sm font-bold text-white"
              disabled={!normalizedWalletAddress}
            >
              Copy
            </button>
          </div>
        </div>
      </div>

      <div className="glass-card mt-4 rounded-[30px] p-4">
        <div className="grid gap-4">
          <label className="glass-card-soft rounded-3xl px-4 py-4 text-left">
            <div className="flex items-center justify-between text-xs uppercase tracking-[0.2em] text-white/45">
              <span>Withdraw amount</span>
              <span>Min {MIN_WITHDRAW.toLocaleString()}</span>
              <span>Balance: {Math.floor(user.balance || 0).toLocaleString()} max</span>
            </div>

            <input
              value={withdraw}
              onChange={(e) => setWithdraw(e.target.value.replace(/[^\d]/g, ""))}
              className="mt-3 w-full bg-transparent text-3xl font-black text-white outline-none"
              inputMode="numeric"
              placeholder={String(MIN_WITHDRAW)}
            />

            {withdrawValidationMessage ? (
              <p className="mt-3 text-xs font-semibold text-[#ffb870]">
                {withdrawValidationMessage}
              </p>
            ) : null}
          </label>

          <div className="grid grid-cols-3 gap-3">
            {[MIN_WITHDRAW, 25_000, 50_000].map((preset) => (
              <button
                key={preset}
                type="button"
                onClick={() =>
                  setWithdraw(
                    String(
                      Math.min(preset, Math.max(Math.floor(user.balance || 0), MIN_WITHDRAW))
                    )
                  )
                }
                className="glass-card-soft rounded-2xl px-3 py-3 text-sm font-bold text-white"
              >
                {preset.toLocaleString()}
              </button>
            ))}
          </div>

          <div className="flex justify-center">
            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-[#0e315d] text-[#ffd08a]">
              <ArrowDownToLine className="h-6 w-6" />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div className="glass-card-soft rounded-3xl px-4 py-4 text-left">
              <p className="text-xs uppercase tracking-[0.2em] text-white/45">
                Pending total
              </p>
              <p className="mt-3 break-all text-2xl font-black text-white">
                {pendingTotal.toLocaleString()}
              </p>
            </div>

            <button
              onClick={createWithdraw}
              disabled={!canCreateWithdraw}
              className="pill-gold h-full min-h-16 rounded-3xl text-lg font-bold text-white disabled:opacity-60"
            >
              {loading ? "Processing..." : "Create request"}
            </button>
          </div>
        </div>
      </div>

      <div className="glass-card mt-4 rounded-[30px] p-4">
        <div className="flex items-center justify-between gap-3">
          <p className="text-xs uppercase tracking-[0.2em] text-white/45">
            Withdraw history
          </p>
          <button
            onClick={loadWithdraws}
            className="rounded-full bg-[#7e1c12] px-3 py-2 text-xs font-bold text-white"
          >
            Refresh
          </button>
        </div>

        <div className="mt-3 space-y-3">
          {loading ? (
            <div className="flex items-center gap-2 text-sm text-white/60">
              <Loader2 className="h-4 w-4 animate-spin" /> Loading...
            </div>
          ) : items.length ? (
            items.map((item) => (
              <div key={item.id} className="glass-card-soft rounded-2xl px-4 py-3">
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <p className="font-semibold text-white">Withdraw #{item.id}</p>
                    <p className="text-xs text-white/45 break-all">{item.wallet_address}</p>
                    <p className="text-xs text-white/45">
                      {new Date(item.created_at).toLocaleString()}
                    </p>
                    {item.tx_hash ? (
                      <p className="mt-1 text-xs text-[#ffd08a] break-all">
                        TX: {item.tx_hash}
                      </p>
                    ) : null}
                    {item.admin_note ? (
                      <p className="mt-1 text-xs text-[#ffd98c]">
                        Note: {item.admin_note}
                      </p>
                    ) : null}
                  </div>

                  <div className="text-right">
                    <p className="text-sm font-black text-[#ffb84d]">
                      -{item.amount.toLocaleString()}
                    </p>
                    <span
                      className={`mt-2 inline-flex rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.2em] ${
                        item.status === "pending"
                          ? "bg-[#5b4311] text-[#ffd98c]"
                          : item.status === "approved"
                            ? "bg-[#163e65] text-[#ffd08a]"
                            : item.status === "paid"
                              ? "bg-[#154830] text-[#8effb8]"
                              : "bg-[#5c1f27] text-[#ff9da9]"
                      }`}
                    >
                      {item.status}
                    </span>
                  </div>
                </div>
              </div>
            ))
          ) : (
            <div className="text-sm text-white/50">No withdraw requests yet.</div>
          )}
        </div>
      </div>

      <div className="glass-card mt-4 rounded-[30px] p-4">
        <div className="flex items-center justify-between gap-3">
          <p className="text-xs uppercase tracking-[0.2em] text-white/45">
            Transaction history
          </p>
        </div>

        <div className="mt-3 space-y-3">
          {transactions.length ? (
            transactions.map((item) => (
              <div key={item.id} className="glass-card-soft rounded-2xl px-4 py-3">
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <p className="font-semibold text-white">{item.type}</p>
                    <p className="text-xs text-white/45">
                      {new Date(item.created_at).toLocaleString()}
                    </p>
                  </div>

                  <div className="text-right">
                    <p
                      className={`text-sm font-black ${
                        item.amount >= 0 ? "text-[#8effb8]" : "text-[#ffb84d]"
                      }`}
                    >
                      {item.amount >= 0 ? "+" : ""}
                      {Number(item.amount).toLocaleString()}
                    </p>
                    <p className="text-xs text-white/45">
                      {Number(item.balance_before).toLocaleString()} →{" "}
                      {Number(item.balance_after).toLocaleString()}
                    </p>
                  </div>
                </div>
              </div>
            ))
          ) : (
            <div className="text-sm text-white/50">No transactions yet.</div>
          )}
        </div>
      </div>
    </div>
  );
}

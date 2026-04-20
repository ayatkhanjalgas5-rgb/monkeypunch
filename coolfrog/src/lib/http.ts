import axios, { AxiosInstance, AxiosRequestConfig } from "axios";

declare module "axios" {
  export interface AxiosInstance {
    $get: <T>(url: string, config?: AxiosRequestConfig) => Promise<T>;
  }
}

const normalizeBaseUrl = (raw?: string) => {
  const fallback = window.location.origin;
  const value = (raw || fallback).trim();

  return value
    .replace(/\/+$/, "")
    .replace(/\/api$/, "");
};

const getToken = () => localStorage.getItem("token");

const apiBase = `${normalizeBaseUrl(import.meta.env.VITE_API_URL)}/api`;

const $http: AxiosInstance = axios.create({
  baseURL: apiBase,
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },
});

$http.interceptors.request.use((config) => {
  const token = getToken();

  config.headers = config.headers ?? {};
  config.headers.Accept = "application/json";
  config.headers["Content-Type"] = "application/json";

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  } else {
    delete config.headers.Authorization;
  }

  return config;
});

$http.$get = async <T>(url: string, config?: AxiosRequestConfig) => {
  const response = await $http.get<T>(url, config);
  return response.data;
};

$http.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error?.response?.status === 401) {
      localStorage.removeItem("token");
      delete $http.defaults.headers.Authorization;
    }

    return Promise.reject(error);
  }
);

const setBearerToken = (token: string) => {
  localStorage.setItem("token", token);
  $http.defaults.headers.Authorization = `Bearer ${token}`;
};

const clearBearerToken = () => {
  localStorage.removeItem("token");
  delete $http.defaults.headers.Authorization;
};

const setTelegramInitData = (rawInitData: string) => {
  if (!rawInitData) {
    delete $http.defaults.headers['X-Telegram-Init-Data'];
    return;
  }

  $http.defaults.headers['X-Telegram-Init-Data'] = rawInitData;
};

export { $http, clearBearerToken, setBearerToken, setTelegramInitData };

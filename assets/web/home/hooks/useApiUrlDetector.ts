import { useEffect } from "react";
import axios, { AxiosRequestConfig } from "axios";

const useApiUrlDetector = (customApiCallback: (config: AxiosRequestConfig) => void) => {
  useEffect(() => {
    const requestInterceptor = axios.interceptors.request.use(
      (config) => {
        if (typeof customApiCallback === "function") {
          customApiCallback(config);
        }
        return config;
      },
      (error) => {
        console.error("API Request Error:", error);
        return Promise.reject(error);
      }
    );

    return () => {
      axios.interceptors.request.eject(requestInterceptor);
    };
  }, [customApiCallback]);

  return () => {};
};

export default useApiUrlDetector;

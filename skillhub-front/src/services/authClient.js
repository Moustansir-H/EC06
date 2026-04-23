import axios from "axios";

const authEnv = import.meta?.env ?? {};

const authClient = axios.create({
  baseURL: authEnv.VITE_AUTH_API_URL ?? "http://127.0.0.1:8080/api",
  headers: {
    "Content-Type": "application/json",
  },
});

export default authClient;

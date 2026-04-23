import authClient from "./authClient";

const TOKEN_KEYS = ["access_token", "token", "auth_token"];
const AUTH_LOGIN_PATH = "/auth/login";
const AUTH_REGISTER_PATH = "/auth/register";
const AUTH_ME_PATH = "/me";

function isRecord(value) {
  return value !== null && typeof value === "object";
}

function pickString(value) {
  if (typeof value !== "string") return null;
  const v = value.trim();
  if (!v || v === "undefined" || v === "null") return null;
  return v;
}

function firstString(values) {
  for (const value of values) {
    const stringValue = pickString(value);
    if (stringValue) return stringValue;
  }

  return null;
}

function extractBearerToken(authHeader) {
  const header = pickString(authHeader);
  if (!header) return null;
  const lowerHeader = header.toLowerCase();
  const bearerPrefix = "bearer ";

  if (!lowerHeader.startsWith(bearerPrefix)) {
    return header;
  }

  return pickString(header.slice(bearerPrefix.length).trimStart());
}

function extractTokenFromNode(node, keys) {
  if (!isRecord(node)) return null;
  return firstString(keys.map((key) => node[key]));
}

function extractTokenFromCandidates(candidates, keys) {
  for (const node of candidates) {
    const token = extractTokenFromNode(node, keys);
    if (token) return token;
  }

  return null;
}

function extractToken(data) {
  if (!isRecord(data)) return null;

  const nestedCandidates = [
    data,
    data?.data,
    data?.result,
    data?.authorization,
    data?.authorisation,
    data?.meta,
  ];
  const alternativeCandidates = [data, data?.data];
  const alternativeKeys = ["accessToken", "jwt", "plainTextToken"];

  return (
    extractTokenFromCandidates(nestedCandidates, TOKEN_KEYS) ??
    extractTokenFromCandidates(alternativeCandidates, alternativeKeys)
  );
}

function bytesToBase64(bytes) {
  if (typeof Buffer !== "undefined") {
    return Buffer.from(bytes).toString("base64");
  }

  let binary = "";
  for (const byte of bytes) {
    binary += String.fromCharCode(byte);
  }
  return btoa(binary);
}

function createNonce() {
  if (
    typeof crypto !== "undefined" &&
    typeof crypto.randomUUID === "function"
  ) {
    return crypto.randomUUID();
  }

  return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

async function computeHmacBase64(password, message) {
  if (!globalThis.crypto?.subtle) {
    throw new Error("Le navigateur ne supporte pas le login SSO.");
  }

  const encoder = new TextEncoder();
  const keyMaterial = await globalThis.crypto.subtle.importKey(
    "raw",
    encoder.encode(password),
    { name: "HMAC", hash: "SHA-256" },
    false,
    ["sign"],
  );

  const signature = await globalThis.crypto.subtle.sign(
    "HMAC",
    keyMaterial,
    encoder.encode(message),
  );

  return bytesToBase64(new Uint8Array(signature));
}

function extractUser(data, fallbackUser) {
  if (data?.user && typeof data.user === "object") return data.user;
  if (data?.data?.user && typeof data.data.user === "object")
    return data.data.user;
  return fallbackUser ?? null;
}

function storeSession(data, fallbackUser) {
  const token = extractToken(data);
  const user = extractUser(data, fallbackUser);

  if (token) {
    localStorage.setItem("token", token);
  } else {
    localStorage.removeItem("token");
  }

  // On ne conserve l'utilisateur que si un token valide existe.
  // Sinon l'UI peut croire a une connexion alors que l'API refuse toutes les routes protegees.
  if (token && user) {
    localStorage.setItem("user", JSON.stringify(user));
  } else {
    localStorage.removeItem("user");
  }

  return { token, user };
}

export const getToken = () => {
  const token = localStorage.getItem("token");
  if (!token || token === "undefined" || token === "null") return null;
  return token;
};

export const getUser = () => JSON.parse(localStorage.getItem("user") || "null");

export const clearSession = () => {
  localStorage.removeItem("token");
  localStorage.removeItem("user");
};

export const login = async (email, password) => {
  const nonce = createNonce();
  const timestamp = Math.floor(Date.now() / 1000);
  const message = `${email}:${nonce}:${timestamp}`;
  const hmac = await computeHmacBase64(password, message);

  const response = await authClient.post(AUTH_LOGIN_PATH, {
    email,
    nonce,
    timestamp,
    hmac,
  });

  const headerToken =
    extractBearerToken(response.headers?.authorization) ||
    extractBearerToken(response.headers?.Authorization);

  const payload = headerToken
    ? { ...(response.data || {}), token: response.data?.token ?? headerToken }
    : response.data;

  const { token, user } = storeSession(payload);
  if (!token) {
    clearSession();
    throw new Error(
      "Connexion reussie mais token manquant dans la reponse serveur.",
    );
  }
  return user;
};

export const register = async ({ nom, prenom, email, password, role }) => {
  const { data } = await authClient.post(AUTH_REGISTER_PATH, {
    nom,
    prenom,
    email,
    password,
    role,
  });

  return data?.user ?? data ?? null;
};

export const logout = async () => {
  clearSession();
};

export const fetchCurrentSession = async () => {
  const token = getToken();
  if (!token) {
    return null;
  }

  const response = await authClient.get(AUTH_ME_PATH, {
    headers: { Authorization: `Bearer ${token}` },
  });

  return response.data ?? null;
};

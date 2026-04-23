import { describe, expect, it, vi } from "vitest";

const { authClientMock } = vi.hoisted(() => ({
  authClientMock: {
    post: vi.fn(),
    get: vi.fn(),
  },
}));

vi.mock("./authClient", () => ({ default: authClientMock }));

function createLocalStorageMock() {
  const storage = {};

  return {
    getItem: (key) => (key in storage ? storage[key] : null),
    setItem: (key, value) => {
      storage[key] = String(value);
    },
    removeItem: (key) => {
      delete storage[key];
    },
    clear: () => {
      for (const key of Object.keys(storage)) {
        delete storage[key];
      }
    },
  };
}

describe("authService helpers", () => {
  it("clearSession removes persisted data", async () => {
    globalThis.localStorage = createLocalStorageMock();
    const { clearSession, getToken, getUser } = await import("./authService");

    localStorage.setItem("token", "token");
    localStorage.setItem("user", JSON.stringify({ id: 6 }));

    clearSession();

    expect(getToken()).toBeNull();
    expect(getUser()).toBeNull();
  });

  it("logout clears the local session without calling the auth service", async () => {
    globalThis.localStorage = createLocalStorageMock();
    const { logout, getToken, getUser } = await import("./authService");

    localStorage.setItem("token", "persisted-token");
    localStorage.setItem("user", JSON.stringify({ id: 5 }));

    await logout();

    expect(authClientMock.post).not.toHaveBeenCalled();
    expect(getToken()).toBeNull();
    expect(getUser()).toBeNull();
  });
});

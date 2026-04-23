import { beforeEach, describe, expect, it, vi } from "vitest";
import api from "../axios";
import {
  desinscrireAtelier,
  fetchAtelierDetail,
  fetchAteliers,
  fetchMesInscriptions,
  inscrireAtelier,
} from "../atelierService";

vi.mock("../axios", () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    delete: vi.fn(),
  },
}));

describe("atelierService", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("fetchAteliers returns API payload", async () => {
    api.get.mockResolvedValueOnce({ data: [{ id: 1 }, { id: 2 }] });

    const payload = await fetchAteliers();

    expect(api.get).toHaveBeenCalledWith("/ateliers");
    expect(payload).toEqual([{ id: 1 }, { id: 2 }]);
  });

  it("fetchAtelierDetail normalizes id before request", async () => {
    api.get.mockResolvedValueOnce({ data: { id: 4, titre: "React" } });

    const payload = await fetchAtelierDetail("4abc");

    expect(api.get).toHaveBeenCalledWith("/ateliers/4");
    expect(payload).toEqual({ id: 4, titre: "React" });
  });

  it("inscrireAtelier and desinscrireAtelier call expected endpoints", async () => {
    api.post.mockResolvedValueOnce({ data: { ok: true } });
    api.delete.mockResolvedValueOnce({ data: { ok: true } });

    const inscription = await inscrireAtelier("12");
    const desinscription = await desinscrireAtelier(12);

    expect(api.post).toHaveBeenCalledWith("/ateliers/12/inscription");
    expect(api.delete).toHaveBeenCalledWith("/ateliers/12/inscription");
    expect(inscription).toEqual({ ok: true });
    expect(desinscription).toEqual({ ok: true });
  });

  it("fetchMesInscriptions returns API payload", async () => {
    api.get.mockResolvedValueOnce({ data: [{ idFormation: 3 }] });

    const payload = await fetchMesInscriptions();

    expect(api.get).toHaveBeenCalledWith("/mes-inscriptions");
    expect(payload).toEqual([{ idFormation: 3 }]);
  });

  it("throws for invalid atelier id", async () => {
    await expect(fetchAtelierDetail("0")).rejects.toThrow(
      "Identifiant atelier invalide.",
    );
    await expect(inscrireAtelier("abc")).rejects.toThrow(
      "Identifiant atelier invalide.",
    );
  });
});

import { describe, expect, it } from "vitest";
import {
  getCategoriesLabelMap,
  getCategoryIconByName,
  getCategoryVisualByKey,
} from "../categoryVisuals";

describe("categoryVisuals", () => {
  it("returns mapped visual by known key", () => {
    expect(getCategoryVisualByKey("design")).toEqual({
      label: "Design",
      icon: "fa-solid fa-palette",
    });
  });

  it("returns fallback visual for unknown key", () => {
    expect(getCategoryVisualByKey("inconnue")).toEqual({
      label: "inconnue",
      icon: "fa-solid fa-book-open",
    });
    expect(getCategoryVisualByKey("")).toEqual({
      label: "Categorie",
      icon: "fa-solid fa-book-open",
    });
  });

  it("detects icons by category name hints", () => {
    expect(getCategoryIconByName("Developpement web")).toBe("fa-solid fa-code");
    expect(getCategoryIconByName("UI/UX Design")).toBe("fa-solid fa-palette");
    expect(getCategoryIconByName("Cyber securite")).toBe(
      "fa-solid fa-shield-halved",
    );
  });

  it("returns default icon when no hint matches", () => {
    expect(getCategoryIconByName("Comptabilite")).toBe("fa-solid fa-book-open");
  });

  it("builds label map from configured categories", () => {
    const labelMap = getCategoriesLabelMap();
    expect(labelMap.design).toBe("Design");
    expect(labelMap.data).toBe("Data");
    expect(labelMap["soft-skills"]).toBe("Soft Skills");
  });
});

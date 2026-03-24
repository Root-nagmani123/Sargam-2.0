const { test, expect } = require("@playwright/test");

test.describe("ERP pre-login smoke", () => {
  test("login page and core assets render", async ({ page }) => {
    const response = await page.goto("", { waitUntil: "domcontentloaded" });
    expect(response, "Base URL should respond").not.toBeNull();
    expect(response.status(), "Base URL should not 5xx").toBeLessThan(500);

    await expect(page).toHaveTitle(/.+/);

    const loginCandidates = [
      'input[name="email"]',
      'input[name="username"]',
      'input[type="email"]',
      'input[name="password"]',
      'button[type="submit"]',
      'text=/login|sign in/i',
    ];

    let hasLoginUI = false;
    for (const candidate of loginCandidates) {
      const locator = page.locator(candidate).first();
      if (await locator.isVisible().catch(() => false)) {
        hasLoginUI = true;
        break;
      }
    }

    expect(hasLoginUI, "Expected login UI elements to be visible").toBeTruthy();

    const brokenImages = await page.evaluate(() => {
      const images = Array.from(document.images);
      return images
        .filter((img) => img.complete && img.naturalWidth === 0)
        .map((img) => img.src);
    });
    expect(
      brokenImages,
      `Broken images detected: ${brokenImages.join(", ")}`
    ).toEqual([]);
  });

  test("page has no critical js errors and no failed static assets", async ({
    page,
  }) => {
    const jsErrors = [];
    const failedAssets = [];

    page.on("pageerror", (error) => {
      jsErrors.push(error.message);
    });

    page.on("requestfailed", (request) => {
      const type = request.resourceType();
      if (["stylesheet", "script", "image", "font"].includes(type)) {
        failedAssets.push(`${type}: ${request.url()}`);
      }
    });

    await page.goto("", { waitUntil: "networkidle" });

    expect(jsErrors, `JavaScript runtime errors: ${jsErrors.join(" | ")}`).toEqual(
      []
    );
    expect(
      failedAssets,
      `Failed static assets: ${failedAssets.join(" | ")}`
    ).toEqual([]);
  });
});

test.describe("ERP authenticated smoke", () => {
  test("login and open key ERP pages", async ({ page, baseURL }) => {
    test.setTimeout(180 * 1000);
    const username = process.env.E2E_USERNAME;
    const password = process.env.E2E_PASSWORD;
    test.skip(!username || !password, "E2E_USERNAME/E2E_PASSWORD not provided");

    const jsErrors = [];
    page.on("pageerror", (error) => jsErrors.push(error.message));

    await page.goto("", { waitUntil: "domcontentloaded" });

    const userInput = page
      .locator(
        'input[name="email"], input[name="username"], input[type="email"], input[type="text"]'
      )
      .first();
    const passInput = page.locator('input[name="password"], input[type="password"]').first();
    const submit = page
      .locator('button[type="submit"], input[type="submit"], button:has-text("Login")')
      .first();

    await userInput.fill(username);
    await passInput.fill(password);
    await Promise.all([
      page.waitForLoadState("networkidle"),
      submit.click(),
    ]);

    await expect(page, "Should leave login page after successful auth").not.toHaveURL(
      /login/i
    );

    await page.waitForTimeout(1500);
    const links = await page.evaluate(() => {
      const origin = window.location.origin;
      const hrefs = Array.from(document.querySelectorAll("a[href]"))
        .map((a) => a.href || a.getAttribute("href") || "")
        .filter((href) => href && !href.startsWith("javascript:"))
        .filter((href) => href.startsWith(origin))
        .filter((href) => !href.includes("#"))
        .filter((href) => !/logout/i.test(href));
      return Array.from(new Set(hrefs)).slice(0, 8);
    });

    const normalizedBase = (baseURL || "").replace(/\/$/, "");
    const fallbackLinks = [
      `${normalizedBase}/dashboard`,
      `${normalizedBase}/member/profile/edit/10466`,
      `${normalizedBase}/dashboard-statistics`,
      `${normalizedBase}/admin/notice`,
    ];
    const linksToVisit = links.length > 0 ? links : fallbackLinks;

    for (const link of linksToVisit) {
      const response = await page.goto(link, {
        waitUntil: "domcontentloaded",
        timeout: 20_000,
      });
      expect(response, `No response for ${link}`).not.toBeNull();
      expect(response.status(), `HTTP 5xx at ${link}`).toBeLessThan(500);
    }

    expect(jsErrors, `JavaScript runtime errors: ${jsErrors.join(" | ")}`).toEqual([]);
  });
});

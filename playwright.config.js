const { defineConfig, devices } = require("@playwright/test");

const baseURL = process.env.E2E_BASE_URL || "http://localhost/";

module.exports = defineConfig({
  testDir: "./tests/e2e",
  timeout: 60 * 1000,
  expect: {
    timeout: 10 * 1000,
  },
  fullyParallel: false,
  retries: 0,
  workers: 1,
  reporter: [["list"], ["html", { open: "never" }]],
  use: {
    baseURL,
    trace: "on-first-retry",
    screenshot: "only-on-failure",
    video: "retain-on-failure",
    viewport: { width: 1366, height: 768 },
  },
  projects: [
    {
      name: "chrome",
      use: {
        ...devices["Desktop Chrome"],
        channel: "chrome",
      },
    },
    {
      name: "firefox",
      use: { ...devices["Desktop Firefox"] },
    },
    {
      name: "edge",
      use: {
        ...devices["Desktop Edge"],
        channel: "msedge",
      },
    },
    {
      name: "safari-webkit",
      use: { ...devices["Desktop Safari"] },
    },
  ],
});

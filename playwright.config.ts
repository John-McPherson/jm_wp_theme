import { defineConfig, devices } from "@playwright/test";
import dotenv from "dotenv";

dotenv.config({ path: ".env.e2e" });

export default defineConfig({
  testDir: "./tests/e2e",
  outputDir: "./artifacts/e2e/test-results",
  fullyParallel: false,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: process.env.CI
    ? [
        ["line"],
        [
          "html",
          {
            outputFolder: "./artifacts/e2e/report",
            open: "never",
          },
        ],
      ]
    : [
        ["list"],
        [
          "html",
          {
            outputFolder: "./artifacts/e2e/report",
            open: "never",
          },
        ],
      ],
  use: {
    baseURL: process.env.WP_BASE_URL ?? "http://localhost:8888",
    trace: "on-first-retry",
    screenshot: "only-on-failure",
    video: "retain-on-failure",
  },

  projects: [
    {
      name: "chromium",
      use: {
        ...devices["Desktop Chrome"],
      },
    },
    {
      name: "firefox",
      use: {
        ...devices["Desktop Firefox"],
      },
    },
    {
      name: "webkit",
      use: {
        ...devices["Desktop Safari"],
      },
    },
  ],
});

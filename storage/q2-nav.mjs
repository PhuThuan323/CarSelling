export default async function run(page, ui) {
  const base = "http://localhost:8124";
  const out = {};

  await page.goto(base + "/auth/login", { waitUntil: "domcontentloaded" });
  await page
    .locator('input[type="email"], input[name="email"]')
    .first()
    .fill("phuthuan323@gmail.com");
  await page
    .locator('input[type="password"], input[name="password"]')
    .first()
    .fill("QaTemp@12345");
  await page
    .locator('button[type="submit"], input[type="submit"]')
    .first()
    .click();
  await page.waitForLoadState("domcontentloaded");
  await page.waitForTimeout(1200);

  // Collect every sidebar link and where it actually points
  await page.goto(base + "/admin", { waitUntil: "domcontentloaded" });
  await page.waitForTimeout(500);
  out.sidebarLinks = await page.$$eval(".admin-nav a", (as) =>
    as.map(
      (a) =>
        a.getAttribute("href") +
        " :: " +
        a.textContent.trim().replace(/\s+/g, " "),
    ),
  );
  await page.screenshot({ path: "storage/q2-sidebar.png", fullPage: true });

  // Click "Hồ sơ bán xe" from the sidebar and confirm we land on the new page
  await page.locator('.admin-nav a[href="/admin/requests"]').first().click();
  await page.waitForLoadState("domcontentloaded");
  await page.waitForTimeout(700);
  out.afterRequestsClick = {
    url: page.url(),
    h2: await page.locator("h2").first().innerText(),
  };
  out.requestsTabs = await page.$$eval(".admin-tab", (t) =>
    t.map((x) => x.textContent.trim().replace(/\s+/g, " ")),
  );
  await page.screenshot({ path: "storage/q2-requests.png", fullPage: true });

  // Click a filter tab -> should navigate with ?filter=
  const pendingTab = page
    .locator(".admin-tab", { hasText: "Chờ tiếp nhận" })
    .first();
  if (await pendingTab.count()) {
    await pendingTab.click();
    await page.waitForLoadState("domcontentloaded");
    await page.waitForTimeout(600);
    out.afterFilterClick = {
      url: page.url(),
      h2: await page.locator("h2").first().innerText(),
    };
  }

  // Go to inspections, then click "Chưa phân công" from sidebar
  await page.goto(base + "/admin/inspections", {
    waitUntil: "domcontentloaded",
  });
  await page.waitForTimeout(500);
  out.inspectionsTabs = await page.$$eval(".admin-tab", (t) =>
    t.map((x) => x.textContent.trim().replace(/\s+/g, " ")),
  );

  await page
    .locator('.admin-nav a[href="/admin/inspections?group=unassigned"]')
    .first()
    .click();
  await page.waitForLoadState("domcontentloaded");
  await page.waitForTimeout(600);
  out.afterUnassignedClick = {
    url: page.url(),
    h2: await page.locator("h2").first().innerText(),
  };
  await page.screenshot({ path: "storage/q2-unassigned.png", fullPage: true });

  // Now the key test: from THIS non-dashboard page, does "Danh mục xe" work?
  const modelsLink = page.locator('.admin-nav a[href="/admin#models"]').first();
  out.modelsLinkExists = await modelsLink.count();
  if (out.modelsLinkExists) {
    await modelsLink.click();
    await page.waitForLoadState("domcontentloaded");
    await page.waitForTimeout(1500);
    out.afterModelsClick = page.url();
    // Which catalog panel is visible?
    out.visiblePanel = await page.evaluate(() => {
      const ids = ["panel-brands", "panel-models", "panel-versions"];
      const vis = ids.filter((id) => {
        const el = document.getElementById(id);
        return el && !el.classList.contains("is-hidden");
      });
      return vis;
    });
    // Did the catalog JS actually load data?
    out.modelRows = await page.locator("#modelTableBody tr").count();
  }

  return out;
}

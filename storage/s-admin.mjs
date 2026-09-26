export default async function run(page, ui) {
  const base = "http://localhost:8126";
  const out = {};

  async function login(email, password) {
    await page.goto(base + "/auth/login", { waitUntil: "domcontentloaded" });
    await page.locator('input[type="email"], input[name="email"]').first().fill(email);
    await page.locator('input[type="password"], input[name="password"]').first().fill(password);
    await page.locator('button[type="submit"], input[type="submit"]').first().click();
    await page.waitForLoadState("domcontentloaded");
    await page.waitForTimeout(1200);
  }

  // ---------- ADMIN: staff page ----------
  await login("phuthuan323@gmail.com", "QaTemp@12345");
  await page.goto(base + "/admin/staff", { waitUntil: "domcontentloaded" });
  await page.waitForTimeout(800);
  out.staffPage = {
    url: page.url(),
    h1: await page.locator("h1").first().innerText(),
    tabs: await page.$$eval(".admin-tab", (t) => t.map((x) => x.textContent.trim().replace(/\s+/g, " "))),
    rows: await page.locator("#staffTableBody tr").count(),
  };
  out.sidebarHasStaffSection = await page.$$eval(".admin-nav a", (as) =>
    as.filter((a) => a.textContent.includes("Nhân viên Inspection")).map((a) => a.getAttribute("href"))
  );
  await page.screenshot({ path: "storage/s1-staff-page.png", fullPage: true });

  // Client-side search filters the table
  if ((await page.locator("#staffSearch").count()) > 0) {
    await page.locator("#staffSearch").fill("Nguyen Van B");
    await page.waitForTimeout(400);
    out.visibleAfterSearch = await page.$$eval("#staffTableBody tr", (rs) =>
      rs.filter((r) => !r.hidden).length
    );
    await page.locator("#staffSearch").fill("");
    await page.waitForTimeout(300);
  }

  // ---------- ADMIN topbar menu ----------
  await page.goto(base + "/", { waitUntil: "domcontentloaded" });
  await page.waitForTimeout(800);
  const gear = page.locator("#userSettingsToggle");
  if (await gear.count()) {
    await gear.click();
    await page.waitForTimeout(500);
    out.adminMenuItems = await page.$$eval("#userSettingsMenu a", (as) =>
      as.map((a) => a.getAttribute("href") + " :: " + a.textContent.trim().replace(/\s+/g, " "))
    );
    await page.screenshot({ path: "storage/s2-admin-menu.png" });
  }

  return out;
}
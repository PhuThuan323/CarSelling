export default async function run(page, ui) {
  const base = "http://localhost:8126";
  const out = {};

  async function login(email, password) {
    await page.goto(base + "/auth/login", { waitUntil: "domcontentloaded" });
    await page
      .locator('input[type="email"], input[name="email"]')
      .first()
      .fill(email);
    await page
      .locator('input[type="password"], input[name="password"]')
      .first()
      .fill(password);
    await page
      .locator('button[type="submit"], input[type="submit"]')
      .first()
      .click();
    await page.waitForLoadState("domcontentloaded");
    await page.waitForTimeout(1400);
  }

  // ---------- STAFF user ----------
  await login("staff.test@fastcar.local", "Staff@12345");
  out.staffLoginLandedOn = page.url();

  await page.goto(base + "/", { waitUntil: "domcontentloaded" });
  await page.waitForTimeout(900);
  const gear = page.locator("#userSettingsToggle");
  out.staffHasGear = await gear.count();
  if (await gear.count()) {
    await gear.click();
    await page.waitForTimeout(500);
    out.staffMenuItems = await page.$$eval("#userSettingsMenu a", (as) =>
      as.map(
        (a) =>
          a.getAttribute("href") +
          " :: " +
          a.textContent.trim().replace(/\s+/g, " "),
      ),
    );
    await page.screenshot({ path: "storage/s3-staff-menu.png" });

    // Click through to the staff area from the menu
    const link = page.locator('#userSettingsMenu a[href="/staff/inspections"]');
    if (await link.count()) {
      await link.click();
      await page.waitForLoadState("domcontentloaded");
      await page.waitForTimeout(900);
      out.staffAreaFromMenu = {
        url: page.url(),
        h1: await page.locator("h1").first().innerText(),
      };
      await page.screenshot({
        path: "storage/s4-staff-area.png",
        fullPage: true,
      });
    }
  }

  // Staff must NOT reach admin
  await page.goto(base + "/admin/staff", { waitUntil: "domcontentloaded" });
  await page.waitForTimeout(600);
  out.staffOnAdminStaffPage = {
    bodyStart: (await page.locator("body").innerText()).slice(0, 120),
  };

  // ---------- CUSTOMER (normal user) ----------
  await page.context().clearCookies();
  await login("23521554@gmail.com", "Cust@12345");
  out.customerLoginLandedOn = page.url();

  await page.goto(base + "/", { waitUntil: "domcontentloaded" });
  await page.waitForTimeout(900);
  const g2 = page.locator("#userSettingsToggle");
  if (await g2.count()) {
    await g2.click();
    await page.waitForTimeout(500);
    out.customerMenuItems = await page.$$eval("#userSettingsMenu a", (as) =>
      as.map(
        (a) =>
          a.getAttribute("href") +
          " :: " +
          a.textContent.trim().replace(/\s+/g, " "),
      ),
    );
  }

  return out;
}

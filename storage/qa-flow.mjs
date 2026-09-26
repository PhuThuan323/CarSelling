export default async function run(page, ui) {
  const base = "http://localhost:8123";
  const results = {};

  async function loginAs(email, password) {
    await page.goto(base + "/auth/login", { waitUntil: "domcontentloaded" });
    const emailBox = page
      .locator('input[type="email"], input[name="email"]')
      .first();
    const passBox = page
      .locator('input[type="password"], input[name="password"]')
      .first();
    await emailBox.fill(email);
    await passBox.fill(password);
    const submit = page
      .locator('button[type="submit"], input[type="submit"]')
      .first();
    await submit.click();
    await page.waitForLoadState("domcontentloaded");
    await page.waitForTimeout(1200);
  }

  // ---- 1. ADMIN inspection list ----
  await page.context().clearCookies();
  await loginAs("phuthuan323@gmail.com", "Admin@12345");
  await page.goto(base + "/admin/inspections", {
    waitUntil: "domcontentloaded",
  });
  await page.waitForTimeout(600);
  results.adminUrl = page.url();
  results.adminTitle = await page.title();
  results.adminBodyStart = (await page.locator("body").innerText()).slice(
    0,
    700,
  );
  await page.screenshot({
    path: "storage/qa-admin-inspections.png",
    fullPage: true,
  });

  // ---- 2. ADMIN inspection detail (request 4) ----
  await page.goto(base + "/admin/inspections/4", {
    waitUntil: "domcontentloaded",
  });
  await page.waitForTimeout(600);
  results.detailBodyStart = (await page.locator("body").innerText()).slice(
    0,
    1200,
  );
  await page.screenshot({
    path: "storage/qa-admin-detail.png",
    fullPage: true,
  });

  // ---- 3. STAFF list + form ----
  await page.context().clearCookies();
  await loginAs("staff.test@fastcar.local", "Staff@12345");
  await page.goto(base + "/staff/inspections", {
    waitUntil: "domcontentloaded",
  });
  await page.waitForTimeout(600);
  results.staffUrl = page.url();
  results.staffBodyStart = (await page.locator("body").innerText()).slice(
    0,
    600,
  );
  await page.screenshot({ path: "storage/qa-staff-list.png", fullPage: true });

  await page.goto(base + "/staff/inspections/1", {
    waitUntil: "domcontentloaded",
  });
  await page.waitForTimeout(600);
  results.staffDetailBody = (await page.locator("body").innerText()).slice(
    0,
    900,
  );
  results.ratingFieldCount = await page
    .locator("select[id^='rating_']")
    .count();
  await page.screenshot({ path: "storage/qa-staff-form.png", fullPage: true });

  // ---- 4. CUSTOMER result page ----
  await page.context().clearCookies();
  await loginAs("phuthuan323@gmail.com", "Admin@12345");
  await page.goto(base + "/my-selling-cars/4", {
    waitUntil: "domcontentloaded",
  });
  await page.waitForTimeout(600);
  results.customerBody = (await page.locator("body").innerText()).slice(0, 900);
  results.hasAcceptBtn = await page.locator("#acceptOfferBtn").count();
  await page.screenshot({
    path: "storage/qa-customer-result.png",
    fullPage: true,
  });

  return results;
}

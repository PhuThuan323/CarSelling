export default async function run(page, ui) {
  const base = "http://localhost:8123";
  const results = {};
  page.on("dialog", async (d) => {
    await d.accept();
  });

  await page.goto(base + "/auth/login", { waitUntil: "domcontentloaded" });
  await page
    .locator('input[type="email"], input[name="email"]')
    .first()
    .fill("phuthuan323@gmail.com");
  await page
    .locator('input[type="password"], input[name="password"]')
    .first()
    .fill("Admin@12345");
  await page
    .locator('button[type="submit"], input[type="submit"]')
    .first()
    .click();
  await page.waitForLoadState("domcontentloaded");
  await page.waitForTimeout(1000);

  // ---------- DECLINE request 6 through the confirm popup ----------
  await page.goto(base + "/my-selling-cars/6", {
    waitUntil: "domcontentloaded",
  });
  await page.waitForTimeout(500);
  await page.locator("#declineOfferBtn").click();
  await page.waitForTimeout(600);
  const modal = page.locator("#declineModal");
  results.modalVisible = await modal.isVisible();
  // The confirming button is the destructive one
  const confirmBtn = modal
    .locator("button")
    .filter({ hasText: /Xác nhận không bán/i })
    .first();
  results.confirmBtnCount = await confirmBtn.count();
  if (await confirmBtn.count()) {
    await confirmBtn.click();
    await page.waitForTimeout(2000);
  }
  results.afterDeclineUrl = page.url();
  results.afterDeclineBody = (await page.locator("body").innerText()).slice(
    0,
    600,
  );
  await page.screenshot({
    path: "storage/qa-after-decline.png",
    fullPage: true,
  });

  // ---------- ACCEPT request 7 ----------
  await page.goto(base + "/my-selling-cars/7", {
    waitUntil: "domcontentloaded",
  });
  await page.waitForTimeout(500);
  await page.locator("#acceptOfferBtn").click();
  await page.waitForTimeout(2000);
  results.afterAcceptUrl = page.url();
  results.afterAcceptBody = (await page.locator("body").innerText()).slice(
    0,
    600,
  );
  await page.screenshot({
    path: "storage/qa-after-accept.png",
    fullPage: true,
  });

  return results;
}

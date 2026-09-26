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

  await page.goto(base + "/my-selling-cars/7", {
    waitUntil: "domcontentloaded",
  });
  await page.waitForTimeout(500);
  await page.locator("#acceptOfferBtn").click();
  await page.waitForTimeout(700);

  // Confirm the accept popup
  const modal = page.locator("#acceptModal");
  results.acceptModalVisible = await modal.isVisible().catch(() => false);
  const btn = modal
    .locator("button")
    .filter({ hasText: /Xác nhận đồng ý bán/i })
    .first();
  if (await btn.count()) {
    results.foundConfirm = true;
    await btn.click();
    await page.waitForTimeout(2500);
  } else {
    results.foundConfirm = false;
    results.modalText = await modal.innerText().catch(() => "(none)");
  }
  results.body = (await page.locator("body").innerText()).slice(0, 500);
  await page.screenshot({
    path: "storage/qa-accepted-final.png",
    fullPage: true,
  });
  return results;
}

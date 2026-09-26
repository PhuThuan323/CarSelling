export default async function run(page, ui) {
  const base = "http://localhost:8123";
  const results = {};

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

  // Status shown on the list page
  await page.goto(base + "/my-selling-cars", { waitUntil: "domcontentloaded" });
  await page.waitForTimeout(500);
  results.listBody = (await page.locator("body").innerText()).slice(0, 900);
  await page.screenshot({ path: "storage/qa-user-list.png", fullPage: true });

  // Estimated record -> should offer accept/decline
  await page.goto(base + "/my-selling-cars/5", {
    waitUntil: "domcontentloaded",
  });
  await page.waitForTimeout(500);
  results.estimatedBody = (await page.locator("body").innerText()).slice(
    0,
    1000,
  );
  results.acceptBtn = await page.locator("#acceptOfferBtn").count();
  results.declineBtn = await page.locator("#declineOfferBtn").count();
  await page.screenshot({
    path: "storage/qa-user-estimated.png",
    fullPage: true,
  });

  // Decline must open a confirm popup (not cancel immediately)
  if (results.declineBtn > 0) {
    await page.locator("#declineOfferBtn").click();
    await page.waitForTimeout(700);
    const modal = page.locator("#declineModal");
    results.modalVisible = await modal.isVisible();
    results.modalText = results.modalVisible
      ? (await modal.innerText()).slice(0, 400)
      : "(not visible)";
    await page.screenshot({
      path: "storage/qa-user-decline-modal.png",
      fullPage: true,
    });

    // Dismiss with "back" to prove nothing was cancelled yet
    const back = modal
      .locator("button, a")
      .filter({ hasText: /Quay lại|Hủy|Đóng/i })
      .first();
    if (await back.count()) {
      await back.click();
      await page.waitForTimeout(500);
    }
  }

  return results;
}

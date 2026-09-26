export default async function run(page, ui) {
  const base = "http://localhost:8126";
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

  await page.goto(base + "/admin/staff", { waitUntil: "domcontentloaded" });
  await page.waitForTimeout(700);

  // The "no results" block must be invisible while the table has rows
  out.noResultVisibleInitially = await page
    .locator("#staffNoResult")
    .isVisible();

  // Type a keyword that matches nothing -> it should appear
  await page.locator("#staffSearch").fill("zzz-khong-co-ai");
  await page.waitForTimeout(400);
  out.visibleRowsOnNoMatch = await page.$$eval(
    "#staffTableBody tr",
    (rs) => rs.filter((r) => !r.hidden).length,
  );
  out.noResultVisibleOnNoMatch = await page
    .locator("#staffNoResult")
    .isVisible();
  await page.screenshot({ path: "storage/s5-noresult.png", fullPage: true });

  // Clear -> table returns, block hides again
  await page.locator("#staffSearch").fill("");
  await page.waitForTimeout(400);
  out.visibleRowsAfterClear = await page.$$eval(
    "#staffTableBody tr",
    (rs) => rs.filter((r) => !r.hidden).length,
  );
  out.noResultVisibleAfterClear = await page
    .locator("#staffNoResult")
    .isVisible();

  return out;
}

/**
 * QA tam thoi: them mot cau chuyen qua UI roi xoa lai, kiem tra thong bao.
 */
export default async function run(page, ui) {
  const sid = process.env.QA_SESSION;

  await page.context().addCookies([
    {
      name: "PHPSESSID",
      value: sid,
      domain: "localhost",
      path: "/",
      httpOnly: true,
    },
  ]);

  await page.goto("http://localhost:8123/admin/feedback", {
    waitUntil: "domcontentloaded",
  });

  await page
    .waitForSelector(".story-admin-card", { timeout: 8000 })
    .catch(function () {});

  const before = await page.evaluate(function () {
    return document.querySelectorAll(".story-admin-card").length;
  });

  // 1. Them moi qua UI
  await page.click("#storyCreateBtn");
  await page.fill("#storyAuthor", "QA Browser");
  await page.fill("#storyTitle", "Cau chuyen kiem tra tu trinh duyet");
  await page.fill("#storyContent", "Dong mot.\n\nDong hai.");
  await page.click("#storyModalSubmit");

  await page.waitForFunction(
    function (count) {
      return (
        document.querySelectorAll(".story-admin-card").length === count + 1
      );
    },
    before,
    { timeout: 8000 },
  );

  const afterCreate = await page.evaluate(function () {
    var alertBox = document.getElementById("adminAlert");
    return {
      count: document.querySelectorAll(".story-admin-card").length,
      alert: alertBox && !alertBox.hidden ? alertBox.textContent.trim() : "",
      firstTitle: document.querySelector(".story-admin-title")
        ? document.querySelector(".story-admin-title").textContent.trim()
        : "",
      autoExcerpt: document.querySelector(".story-admin-excerpt")
        ? document.querySelector(".story-admin-excerpt").textContent.trim()
        : "",
      modalClosed: document.getElementById("storyModal").hidden,
    };
  });

  // 2. Tim kiem phia client
  await page.fill("#storySearch", "QA Browser");
  await page.waitForTimeout(200);

  const search = await page.evaluate(function () {
    var cards = Array.from(document.querySelectorAll(".story-admin-card"));
    return {
      visible: cards.filter(function (c) {
        return !c.hidden;
      }).length,
      total: cards.length,
    };
  });

  await page.fill("#storySearch", "");
  await page.waitForTimeout(150);

  // 3. Xoa cau chuyen vua tao qua UI
  await page.click('[data-story-name="Cau chuyen kiem tra tu trinh duyet"]');
  await page.waitForTimeout(250);

  const deleteModalVisible = await page.evaluate(function () {
    return !document.getElementById("storyDeleteModal").hidden;
  });

  await page.click("#storyDeleteConfirm");

  await page.waitForFunction(
    function (count) {
      return document.querySelectorAll(".story-admin-card").length === count;
    },
    before,
    { timeout: 8000 },
  );

  const afterDelete = await page.evaluate(function () {
    var alertBox = document.getElementById("adminAlert");
    return {
      count: document.querySelectorAll(".story-admin-card").length,
      alert: alertBox && !alertBox.hidden ? alertBox.textContent.trim() : "",
    };
  });

  return {
    before: before,
    afterCreate: afterCreate,
    search: search,
    deleteModalVisible: deleteModalVisible,
    afterDelete: afterDelete,
  };
}

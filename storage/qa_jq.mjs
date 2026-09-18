export default async function run(page, ui) {
  const out = {}

  await page.goto('http://127.0.0.1:8877/')
  await page.waitForLoadState('networkidle').catch(() => { })
  await page.waitForTimeout(2000)

  // Is jQuery present at all?
  out.jQuery = await page.evaluate(() => typeof window.jQuery)
  out.dollar = await page.evaluate(() => typeof window.$)

  out.scriptTags = await page.evaluate(() =>
    Array.from(document.querySelectorAll('script[src]')).map((s) => s.getAttribute('src'))
  )

  // Ask the page to report the error for us
  out.errorProbe = await page.evaluate(() => {
    try {
      // eslint-disable-next-line no-undef
      return { jqAvailable: typeof jQuery !== 'undefined' }
    } catch (e) {
      return { threw: String(e) }
    }
  })

  return out
}

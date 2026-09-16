export default async function run(page, ui) {
  const out = {}

  await page.goto('http://127.0.0.1:8877/')
  await page.waitForLoadState('networkidle').catch(() => {})
  await page.waitForTimeout(3000)

  // Which script tags does the page actually have?
  out.scripts = await page.evaluate(() =>
    Array.from(document.querySelectorAll('script[src]')).map((s) => s.getAttribute('src'))
  )

  // Did the brand picker populate, or is it stuck on "Dang tai..."?
  out.brandPicker = await page.evaluate(() => {
    const p = document.getElementById('brandPicker')
    if (!p) return '(no #brandPicker)'
    return {
      chips: p.querySelectorAll('.brand-chip').length,
      text: p.innerText.slice(0, 200),
    }
  })

  out.featuredGrid = await page.evaluate(() => {
    const g = document.getElementById('featuredVehicleGrid')
    if (!g) return '(no #featuredVehicleGrid)'
    return { children: g.children.length, text: g.innerText.slice(0, 200) }
  })

  out.reviewGrid = await page.evaluate(() => {
    const g = document.getElementById('reviewGrid')
    if (!g) return '(no #reviewGrid)'
    return { children: g.children.length, text: g.innerText.slice(0, 200) }
  })

  out.brandCountText = await page.evaluate(() => {
    const el = document.getElementById('brandCountText')
    return el ? el.innerText : '(missing)'
  })

  // Any JS error captured on window?
  out.hasHomepageJs = await page.evaluate(() => typeof window.FastCarHome !== 'undefined')

  return out
}

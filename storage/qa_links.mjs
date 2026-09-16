export default async function run(page, ui) {
  const out = {}

  // 1. Load the homepage and inspect the two buttons
  await page.goto('http://127.0.0.1:8877/')
  await page.waitForLoadState('networkidle').catch(() => {})
  await page.waitForTimeout(1200)

  out.homeLinks = await page.evaluate(() => {
    const wrap = document.querySelector('.user-management')
    if (!wrap) return '(no .user-management)'
    return Array.from(wrap.querySelectorAll('a, button')).map((el) => ({
      tag: el.tagName,
      text: el.innerText.trim(),
      href: el.getAttribute('href'),
      visible: el.getBoundingClientRect().width > 0 && el.getBoundingClientRect().height > 0,
    }))
  })

  // 2. Click "Đăng nhập" -> should land on /auth/login
  await page.evaluate(() => {
    const a = Array.from(document.querySelectorAll('.user-management a')).find(
      (x) => x.innerText.trim() === 'Đăng nhập'
    )
    a && a.click()
  })
  await page.waitForLoadState('networkidle').catch(() => {})
  await page.waitForTimeout(1200)
  out.afterLoginClick = {
    url: page.url(),
    title: await page.title(),
    hasEmailField: await page.evaluate(() => !!document.querySelector('input[name="email"]')),
  }

  // 3. Back home, click "Đăng ký" -> should land on /auth/register with the sign-up panel
  await page.goto('http://127.0.0.1:8877/')
  await page.waitForLoadState('networkidle').catch(() => {})
  await page.waitForTimeout(1000)

  await page.evaluate(() => {
    const a = Array.from(document.querySelectorAll('.user-management a')).find(
      (x) => x.innerText.trim() === 'Đăng ký'
    )
    a && a.click()
  })
  await page.waitForLoadState('networkidle').catch(() => {})
  await page.waitForTimeout(1200)

  out.afterRegisterClick = {
    url: page.url(),
    title: await page.title(),
    registerFormAction: await page.evaluate(() => {
      const f = document.getElementById('register')
      return f ? f.getAttribute('action') : null
    }),
    signupFields: await page.evaluate(() => {
      const f = document.getElementById('register')
      if (!f) return null
      return Array.from(f.querySelectorAll('input')).map((i) => i.name || i.id)
    }),
    signupPanelVisible: await page.evaluate(() => {
      const p = document.querySelector('.sign-up-panel')
      return p ? p.getBoundingClientRect().height > 0 : null
    }),
  }

  return out
}

import { test } from '@playwright/test';
test('diagnostic system pages row', async ({ page }) => {
  const consoleErrors:string[]=[]; const pageErrors:string[]=[]; const responses:{url:string,status:number}[]=[];
  page.on('console',m=>{if(m.type()==='error') consoleErrors.push(m.text())}); page.on('pageerror',e=>pageErrors.push(String(e)));
  page.on('response',r=>{if(r.url().includes('/club-management/')||r.url().includes('/wp-login.php')) responses.push({url:r.url(),status:r.status()})});
  const base=process.env.ILUNGU_BASE_URL!; const url=(p:string)=>new URL(p,base.replace(/\/$/,'')+'/').toString();
  await page.goto(url('/wp-login.php'),{waitUntil:'domcontentloaded'}); await page.getByLabel(/username or email address/i).fill(process.env.ILUNGU_ADMIN_USER!); await page.getByLabel(/^password$/i).fill(process.env.ILUNGU_ADMIN_PASSWORD!); await page.getByRole('button',{name:/log in/i}).click(); await page.waitForURL(/\/(wp-admin\/|member-login\/|club-management\/|noticeboard\/)/);
  const nav=await page.goto(url('/club-management/?workspace=system-pages&tpw_club_playwright_system_pages=1'),{waitUntil:'domcontentloaded'});
  const row=page.locator('.tpw-flexiclub-system-pages__row').filter({has:page.locator('.tpw-flexiclub-system-pages__page-title',{hasText:'Club Management'})}).first();
  const data=await row.evaluate((el:Element)=>{const norm=(s:string|null)=>(s||'').replace(/\s+/g,' ').trim(); const redact=(s:string)=>s.replace(/((?:nonce|token|_wpnonce|csrf|signature)[-_a-z0-9]*\s*=\s*["'])[^"']+(["'])/ig,'$1[REDACTED]$2').replace(/((?:nonce|token|_wpnonce|csrf|signature)[-_a-z0-9]*\s*:\s*["'])[^"']+(["'])/ig,'$1[REDACTED]$2'); return {childCount:el.children.length,children:Array.from(el.children).map(c=>({class:c.className,text:norm(c.textContent)})),innerHTML:redact(el.innerHTML),links:Array.from(el.querySelectorAll('a')).map(a=>({text:norm(a.textContent),href:(a as HTMLAnchorElement).href}))}});
  console.log(JSON.stringify({finalURL:page.url(),title:await page.title(),row:data,bodyClass:await page.locator('body').getAttribute('class'),consoleErrors,pageErrors,navigationResponse:nav?{url:nav.url(),status:nav.status()}:null,responses},null,2));
});

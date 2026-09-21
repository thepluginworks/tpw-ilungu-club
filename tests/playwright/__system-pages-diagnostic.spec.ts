import { test } from '@playwright/test';
test('diagnostic', async ({ page }) => {
 const ce:string[]=[]; const pe:string[]=[]; const rs:{url:string,status:number}[]=[];
 page.on('console',m=>{if(m.type()==='error')ce.push(m.text())}); page.on('pageerror',e=>pe.push(String(e)));
 page.on('response',r=>{if(r.url().includes('/wp-login.php')||r.url().includes('/club-management/'))rs.push({url:r.url(),status:r.status()})});
 const base=process.env.ILUNGU_BASE_URL!; const u=(p:string)=>new URL(p,base.replace(/\/$/,'')+'/').toString();
 await page.goto(u('/wp-login.php'),{waitUntil:'domcontentloaded'}); await page.getByLabel(/username or email address/i).fill(process.env.ILUNGU_ADMIN_USER!); await page.getByLabel(/^password$/i).fill(process.env.ILUNGU_ADMIN_PASSWORD!); await page.getByRole('button',{name:/log in/i}).click(); await page.waitForURL(/\/(wp-admin\/|member-login\/|club-management\/|noticeboard\/)/);
 const nav=await page.goto(u('/club-management/?workspace=system-pages&tpw_club_playwright_system_pages=1'),{waitUntil:'domcontentloaded'});
 const row=page.locator('.tpw-flexiclub-system-pages__row').filter({hasText:/^\s*Club Management\s*$/}).first();
 const data=await row.evaluate((el:Element)=>{const n=(x:string|null)=>(x||'').replace(/\s+/g,' ').trim(); const red=(x:string)=>x.replace(/((?:nonce|token|csrf|signature)[^=]*=\s*["'])[^"']+(["'])/ig,'$1[REDACTED]$2'); return {childCount:el.children.length,children:Array.from(el.children).map(c=>({class:c.className,text:n(c.textContent)})),innerHTML:red(el.innerHTML),links:Array.from(el.querySelectorAll('a')).map(a=>({text:n(a.textContent),href:(a as HTMLAnchorElement).href}))}});
 console.log(JSON.stringify({finalURL:page.url(),title:await page.title(),row:data,bodyClass:await page.locator('body').getAttribute('class'),consoleErrors:ce,pageErrors:pe,navigationResponse:nav&&{url:nav.url(),status:nav.status()},responses:rs},null,2));
});

'use strict';
const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');
const { chromium } = require('playwright-core');
const root = path.resolve(__dirname, '..');
const rendered = spawnSync('php', [path.join(__dirname, 'home-oscar-mobile-art-runtime.php'), '--fixture'], {encoding:'utf8'});
if (rendered.status !== 0) throw new Error(rendered.stderr || rendered.stdout);
const styles = ['style.css','assets/css/lunara-shell.css','assets/css/lunara-home-modules.css','assets/css/lunara-public-guardrails.css'];
const html = `<!doctype html><html lang="en"><head><meta name="viewport" content="width=device-width,initial-scale=1">${styles.map(file=>`<link rel="stylesheet" href="/${file}">`).join('')}<style>:root{--lunara-home-oscar-picks-gap:24px;--lunara-home-oscar-picks-card-min:460px;--lunara-home-oscar-picks-mobile-column:86%}body{margin:0}.lunara-front-page{width:calc(100% - 32px);margin:16px auto}</style><script src="/assets/js/lunara-scroll-carousel.js" defer></script></head><body class="home"><main class="lunara-front-page">${rendered.stdout}</main></body></html>`;
const executablePath = process.env.LUNARA_BROWSER_EXECUTABLE || ['C:/Program Files/Google/Chrome/Application/chrome.exe','C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe','/usr/bin/chromium','/usr/bin/chromium-browser','/usr/bin/google-chrome'].find(fs.existsSync);
let checks = 0;
function check(value, message) { checks++; if (!value) throw new Error(message); }
(async () => {
    const browser = await chromium.launch({headless:true,executablePath});
    try {
        for (const width of [390,1440]) for (const reducedMotion of ['reduce','no-preference']) {
            const page = await browser.newPage({viewport:{width,height:1000},reducedMotion});
            await page.addInitScript(() => {
                window.railScrollBehaviors = [];
                for (const method of ['scrollTo','scrollBy']) {
                    const original = Element.prototype[method];
                    Element.prototype[method] = function (...args) {
                        if (this.matches('[data-lunara-carousel-track]')) window.railScrollBehaviors.push(args[0].behavior);
                        return original.apply(this,args);
                    };
                }
            });
            await page.route('https://oscar-rail.test/**', route => {
                const pathname = new URL(route.request().url()).pathname;
                if (pathname === '/') return route.fulfill({contentType:'text/html',body:html});
                const file = path.resolve(root,'.'+pathname);
                return file.startsWith(root+path.sep) && fs.existsSync(file) ? route.fulfill({contentType:file.endsWith('.js')?'text/javascript':'text/css',body:fs.readFileSync(file)}) : route.fulfill({status:404,body:''});
            });
            await page.goto('https://oscar-rail.test/');
            const track = page.locator('[data-lunara-carousel-track]');
            const next = page.locator('[data-lunara-carousel-next]');
            const prev = page.locator('[data-lunara-carousel-prev]');
            const waitOffset = async expected => {
                try { await page.waitForFunction(value => Math.abs(document.querySelector('[data-lunara-carousel-track]').scrollLeft-value)<2,expected,{timeout:3000}); }
                catch (error) { throw new Error(`${width}/${reducedMotion}: expected offset ${expected}, saw ${await track.evaluate(el=>el.scrollLeft)}. ${error.message}`); }
            };
            const geometry = await track.evaluate(el=>{
                const styles=getComputedStyle(el),max=el.scrollWidth-el.clientWidth,left=el.getBoundingClientRect().left;
                return {max,step:el.children[0].offsetWidth+parseInt(styles.columnGap,10),targets:Array.from(el.children).map(card=>Math.min(max,Math.max(0,card.getBoundingClientRect().left-left-parseFloat(styles.scrollPaddingLeft))))};
            });
            check(geometry.max > 0,`${width}: fixture must have more than one visible page.`);
            const advances = Math.ceil(geometry.max/geometry.step);
            for (let index=1;index<=advances;index++) {
                await next.click();
                try { await waitOffset(geometry.targets[index]); }
                catch (error) { throw new Error(`${width}/${reducedMotion}: Next must reach card ${index+1} before wrapping. ${JSON.stringify(geometry)} ${error.message}`); }
                check(true, 'Next reaches the next card.');
            }
            await next.click(); await waitOffset(0); check(true,'Next wraps only after reaching the end.');
            await prev.click(); await waitOffset(geometry.max); check(true,'Previous wraps from the first to the last page.');
            const keyboardTarget = geometry.targets.reduce((nearest,target)=>Math.abs(target-(geometry.max-geometry.step))<Math.abs(nearest-(geometry.max-geometry.step))?target:nearest,0);
            await track.focus(); await page.keyboard.press('ArrowLeft'); await waitOffset(keyboardTarget); check(true,'Keyboard navigation moves back to the previous snap point.');
            await page.locator('[data-lunara-carousel-dot]').first().click(); await waitOffset(0);
            await page.locator('[data-lunara-carousel-dot]').last().click(); await waitOffset(geometry.max); check(true,'Dots can reach the final card.');
            if (width===390) {
                await page.waitForFunction(()=>document.querySelectorAll('[data-lunara-carousel-dot]')[3].getAttribute('aria-selected')==='true');
                check(true,'The final mobile dot reflects the visible final card.');
            }
            const behaviors = await page.evaluate(()=>window.railScrollBehaviors);
            check(behaviors.length >= 5,'The real runtime must handle the controls.');
            if (reducedMotion==='reduce') check(behaviors.every(value=>value==='auto'),'Reduced motion must also govern arrows and keyboard advances.');
            await page.close();
        }
        console.log(`Homepage Oscar navigation runtime passed: ${checks} checks across phone/desktop and reduced/normal motion.`);
    } finally { await browser.close(); }
})().catch(error=>{console.error(error.stack);process.exitCode=1;});

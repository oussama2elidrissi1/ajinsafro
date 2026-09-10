// Native Chrome smoke checks. Usage: node tests/Browser/hajj-omra-formulas.mjs <fixture-directory>
import {spawn} from 'node:child_process';
import {readFile, writeFile, mkdir} from 'node:fs/promises';
import {join, resolve} from 'node:path';
import {pathToFileURL} from 'node:url';

const folder = resolve(process.argv[2]);
const profile = join(folder, 'chrome-' + Date.now());
await mkdir(profile, {recursive: true});
const chrome = spawn(process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe', [
    '--headless=new', '--no-first-run', '--no-default-browser-check', '--disable-gpu', '--allow-file-access-from-files',
    '--remote-debugging-address=127.0.0.1', '--remote-debugging-port=0', '--user-data-dir=' + profile, 'about:blank'
], {windowsHide: true, stdio: 'ignore'});
const pause = ms => new Promise(done => setTimeout(done, ms));
let socket;
const results = [];
try {
    let port;
    for (let i = 0; i < 100; i++) {
        try { port = (await readFile(join(profile, 'DevToolsActivePort'), 'utf8')).split('\n')[0]; break; } catch { await pause(100); }
    }
    if (!port) throw new Error('Chrome did not start');
    const tabs = await (await fetch('http://127.0.0.1:' + port + '/json')).json();
    socket = new WebSocket(tabs.find(tab => tab.type === 'page').webSocketDebuggerUrl);
    await new Promise((done, reject) => { socket.onopen = done; socket.onerror = reject; });
    let sequence = 0;
    const pending = new Map(), errors = [];
    socket.onmessage = event => {
        const message = JSON.parse(event.data);
        if (message.id && pending.has(message.id)) {
            const {done, reject} = pending.get(message.id); pending.delete(message.id);
            message.error ? reject(new Error(JSON.stringify(message.error))) : done(message.result);
        }
        if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.exception?.description || message.params.exceptionDetails.text);
    };
    const cdp = (method, params = {}) => new Promise((done, reject) => {
        const id = ++sequence; pending.set(id, {done, reject}); socket.send(JSON.stringify({id, method, params}));
    });
    const evaluate = async expression => {
        const response = await cdp('Runtime.evaluate', {expression, returnByValue: true, awaitPromise: true});
        if (response.exceptionDetails) throw new Error(response.exceptionDetails.exception?.description || response.exceptionDetails.text);
        return response.result.value;
    };
    await cdp('Runtime.enable'); await cdp('Page.enable');
    async function navigate(file, width) {
        errors.length = 0;
        await cdp('Emulation.setDeviceMetricsOverride', {width, height: 1000, deviceScaleFactor: 1, mobile: false});
        await cdp('Page.navigate', {url: pathToFileURL(join(folder, file)).href});
        for (let i = 0; i < 100; i++) {
            if (await evaluate('document.readyState === "complete"')) break;
            await pause(100);
        }
        await evaluate('Promise.race([document.fonts.ready, new Promise(r => setTimeout(r, 1500))]).then(() => true)');
    }
    const frontChecks = () => {
        const checks = [], root = document.querySelector('[data-ajod]');
        const check = (name, passed) => checks.push({name, passed: !!passed});
        const get = selector => root.querySelector(selector);
        const change = (selector, value, event = 'change') => { const node = get(selector); node.value = value; node.dispatchEvent(new Event(event, {bubbles: true})); };
        const digits = selector => get(selector).textContent.replace(/[^0-9]/g, '');
        check('No page overflow', document.documentElement.scrollWidth <= innerWidth + 1);
        check('Two formulas', root.querySelectorAll('.ajho-formula').length === 2);
        check('Mobile cards / desktop table', getComputedStyle(get('.ajho-formula__table')).display === (innerWidth <= 900 ? 'block' : 'table'));
        check('Initial price comes from linked tariff', digits('[data-estimate]') === '14900');
        check('Initial IDs', get('[name="tariff_id"]').value === '3' && get('[name="departure_id"]').value === '1');
        if (root.lang === 'ar') {
            check('Arabic direction and content', root.dir === 'rtl' && root.textContent.includes('البرنامج السياحي') && root.textContent.includes('فندق فلسطين'));
            check('Arabic reservation labels', root.textContent.includes('طلب حجز') && !root.textContent.includes('Demander une réservation'));
            check('Numeric bidi isolation', get('.ajho-formula__price bdi').dir === 'ltr');
            if (innerWidth > 900) {
                const cells = root.querySelectorAll('.ajho-formula__table th');
                check('Natural RTL column order', cells[0].getBoundingClientRect().x > cells[1].getBoundingClientRect().x);
            }
        }
        change('#ajho-formula', '2');
        check('Formula limits room choices', get('#ajho-room-type').options.length === 2 && get('#ajho-room-type').value === 'double');
        check('Premium estimate and IDs', digits('[data-estimate]') === '21900' && get('[name="tariff_id"]').value === '4' && get('[name="departure_id"]').value === '2');
        check('Departure outside formula disabled', get('#ajho-departure option[data-id="1"]').disabled);
        change('#ajho-formula', '1'); change('#ajho-room-type', 'double'); change('#ajho-adults', '2', 'input');
        check('Estimate tracks linked room price', digits('[data-estimate]') === '33800' && get('[name="tariff_id"]').value === '1');
        change('#ajho-adults', '1', 'input'); change('#ajho-room-type', 'quadruple');
        check('Empty room cannot use unrelated fallback', (() => { change('#ajho-room-type', ''); const valid = get('#ajho-room-type').checkValidity(); change('#ajho-room-type', 'quadruple'); return !valid; })());
        return checks;
    };
    for (const locale of ['fr', 'ar']) {
        for (const width of [390, 800, 1440]) {
            await navigate('detail-' + locale + '.html', width);
            const checks = await evaluate('(' + frontChecks.toString() + ')()');
            checks.push({name: 'No JavaScript exceptions', passed: errors.length === 0, errors: [...errors]});
            results.push({page: locale, width, checks});
            await evaluate('document.querySelector("#tarifs").scrollIntoView({block:"start"})');
            const shot = await cdp('Page.captureScreenshot', {format: 'png'});
            await writeFile(join(folder, 'formulas-' + locale + '-' + width + '.png'), Buffer.from(shot.data, 'base64'));
        }
    }
    await navigate('admin.html', 1440);
    const adminChecks = () => {
        const editor = document.querySelector('[data-ho-editor]'), checks = [];
        const check = (name, passed) => checks.push({name, passed: !!passed});
        const cards = () => editor.querySelectorAll('[data-formula-card]');
        check('Saved formulas restored', cards().length === 2);
        check('Admin price is calculated from linked tariffs', editor.querySelector('[data-role="price-current"]').disabled && editor.querySelector('[data-role="price-current"]').value === '14900');
        check('All same-city hotels retained', editor.querySelectorAll('[data-repeat-list="hotel"] [data-repeat-item]').length === 3);
        editor.querySelector('[data-lang-switch="ar"]').click();
        check('Arabic global UI', editor.dir === 'rtl' && editor.lang === 'ar' && editor.querySelector('[data-step="tarifs"]').textContent.includes('الأسعار'));
        check('Arabic formula name pane', getComputedStyle(cards()[0].querySelector('[data-lang-pane="ar"]')).display !== 'none' && cards()[0].querySelector('[data-f="name_ar"]').value === 'البرنامج السياحي');
        check('French pane hidden in Arabic', getComputedStyle(cards()[0].querySelector('[data-lang-pane="fr"]')).display === 'none');
        check('Arabic preview URL', editor.querySelector('[data-formula-preview]').href.includes('locale=ar'));
        editor.querySelector('[data-repeat-add="room"]').click();
        const row = Array.from(editor.querySelectorAll('[data-repeat-list="room"] [data-repeat-item]')).at(-1);
        row.querySelector('[name$="[room_type]"]').value = 'single';
        const price = row.querySelector('[name$="[price]"]'); price.value = '25000'; price.dispatchEvent(new Event('input', {bubbles: true}));
        const ref = 'new:' + row.querySelector('[name$="[client_key]"]').value;
        const selector = cards()[0].querySelector('[data-f="tariff_ids"]');
        const option = Array.from(selector.options).find(option => option.value === ref);
        check('New unsaved tariff can be linked', !!option && option.textContent.includes('25000'));
        option.selected = true;
        const preserved = ref;
        editor.querySelector('[data-repeat-list="room"] [data-repeat-remove]').click();
        editor.dispatchEvent(new CustomEvent('ho:before-submit'));
        check('Transient reference stable after reindex', Array.from(selector.selectedOptions).some(option => option.value === preserved));
        const form = new FormData(editor.querySelector('form'));
        check('Form submission carries linked IDs', form.getAll('formulas[0][tariff_ids][]').includes(preserved) && form.get('formulas[0][hotels][0][hotel_id]') === '1');
        check('Missing source is preserved for server validation', Array.from(selector.options).some(option => option.value === '1' && option.textContent.includes('تم حذف')));
        editor.querySelector('[data-formula-add]').click();
        check('Formula create', cards().length === 3);
        Array.from(cards()[2].querySelectorAll('button')).at(-1).click();
        check('Formula delete', cards().length === 2);
        return checks;
    };
    const checks = await evaluate('(' + adminChecks.toString() + ')()');
    checks.push({name: 'No JavaScript exceptions', passed: errors.length === 0, errors: [...errors]});
    results.push({page: 'admin', width: 1440, checks});
    await writeFile(join(folder, 'admin-ar.png'), Buffer.from((await cdp('Page.captureScreenshot', {format: 'png'})).data, 'base64'));
    await writeFile(join(folder, 'results.json'), JSON.stringify(results, null, 2));
    const failures = results.flatMap(group => group.checks.filter(check => !check.passed).map(check => ({page: group.page, width: group.width, ...check})));
    console.log(JSON.stringify({checks: results.reduce((total, group) => total + group.checks.length, 0), failures, artifacts: folder}, null, 2));
    if (failures.length) process.exitCode = 1;
    await cdp('Browser.close');
} finally {
    socket?.close(); chrome.kill();
}

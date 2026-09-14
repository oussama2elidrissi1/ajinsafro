// Usage: node tests/Browser/departure-rooms.mjs <fixture-directory>
import {spawn} from 'node:child_process';
import {readFile, writeFile, mkdir} from 'node:fs/promises';
import {join, resolve} from 'node:path';
import {pathToFileURL} from 'node:url';
const folder = resolve(process.argv[2]), profile = join(folder, 'chrome-' + Date.now());
await mkdir(profile, {recursive:true});
const chrome = spawn(process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe', ['--headless=new','--no-first-run','--no-default-browser-check','--disable-gpu','--allow-file-access-from-files','--remote-debugging-address=127.0.0.1','--remote-debugging-port=0','--user-data-dir='+profile,'about:blank'], {windowsHide:true,stdio:'ignore'});
const pause = ms => new Promise(done => setTimeout(done, ms));
let socket;
try {
    let port;
    for (let i=0;i<100;i++) { try { port=(await readFile(join(profile,'DevToolsActivePort'),'utf8')).split('\n')[0]; break; } catch { await pause(100); } }
    if (!port) throw new Error('Chrome did not start');
    const tabs = await (await fetch('http://127.0.0.1:'+port+'/json')).json();
    socket = new WebSocket(tabs.find(tab=>tab.type==='page').webSocketDebuggerUrl);
    await new Promise((done,reject)=>{socket.onopen=done;socket.onerror=reject;});
    let sequence=0; const pending=new Map(), errors=[];
    socket.onmessage=event=>{
        const message=JSON.parse(event.data);
        if (message.id && pending.has(message.id)) {const {done,reject}=pending.get(message.id);pending.delete(message.id);message.error?reject(new Error(JSON.stringify(message.error))):done(message.result);}
        if (message.method==='Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.exception?.description || message.params.exceptionDetails.text);
    };
    const cdp=(method,params={})=>new Promise((done,reject)=>{const id=++sequence;pending.set(id,{done,reject});socket.send(JSON.stringify({id,method,params}));});
    const evaluate=async expression=>{const response=await cdp('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});if(response.exceptionDetails)throw new Error(response.exceptionDetails.exception?.description||response.exceptionDetails.text);return response.result.value;};
    await cdp('Runtime.enable'); await cdp('Page.enable');
    const results=[];
    for (const width of [1440,390]) {
        await cdp('Emulation.setDeviceMetricsOverride',{width,height:1100,deviceScaleFactor:1,mobile:false});
        await cdp('Page.navigate',{url:pathToFileURL(join(folder,'rooms.html')).href}); await pause(900);
        const checks=await evaluate('('+ (async function () {
            const checks=[], check=(name,passed)=>checks.push({name,passed:!!passed}), get=s=>document.querySelector(s), pause=ms=>new Promise(done=>setTimeout(done,ms));
            const change=(selector,value)=>{const el=get(selector);el.value=value;el.dispatchEvent(new Event('input',{bubbles:true}));};
            const until=async predicate=>{for(let i=0;i<80;i++){if(predicate())return;await pause(100);}throw new Error('Timed out waiting for save: '+get('#departure-rooms-status')?.textContent);};
            get('[data-create-step="1"] [data-create-next]').click(); await pause(300);
            get('#btn-auto-rooming').click();
            check('Reservation allocations created',window.reservationState.roomAllocations.length===1 && window.reservationState.roomAllocations[0].traveler_keys.length===2);
            const original=JSON.stringify(window.reservationState.roomAllocations.map(r=>({id:r.local_id,travelers:r.traveler_keys,mode:r.occupancy_mode})));
            const extra=get('.reservation-create-extra-cb'); extra.click();
            const extraTotal=get('#create-summary-extras').textContent;
            get('#btn-manage-departure-rooms').click(); await until(()=>!get('#departure-rooms-fields').disabled);
            check('Native accessible modal opened',get('#departure-rooms-modal').open && get('#departure-rooms-modal').getAttribute('aria-labelledby')==='departure-rooms-title');
            check('Selected departure and initial coverage',get('#departure-rooms-summary').textContent.includes('04/10/2026') && get('#departure-rooms-summary').textContent.includes('20/20'));
            check('No modal overflow',get('#departure-rooms-modal').getBoundingClientRect().right<=innerWidth && document.documentElement.scrollWidth<=innerWidth+1);
            window.roomsTest.roomDelay=1300; window.reservationCreateReloadDepartureRooms();
            change('[data-field="quantity"]','8'); change('[data-field="supplement"]','250');
            check('Coverage changes immediately',get('#departure-rooms-summary').textContent.includes('16/20'));
            await until(()=>get('#departure-rooms-status').textContent.startsWith('Enregistré'));
            check('Inventory persisted and available rooms refreshed',window.roomsTest.rooms[0].quantity===8 && window.availableRoomTypes[0].available_rooms===8);
            check('Existing travelers and room mode preserved',JSON.stringify(window.reservationState.roomAllocations.map(r=>({id:r.local_id,travelers:r.traveler_keys,mode:r.occupancy_mode})))===original);
            check('Supplement calculated per person and total refreshed',get('#reservation-room-supplement-total-input').value==='500.00' && get('#reservation-total-amount-input').value==='37150.00');
            checks[checks.length-1].actual={supplement:get('#reservation-room-supplement-total-input').value,total:get('#reservation-total-amount-input').value,extras:extraTotal,unitPrice:get('#reservation-base-price').value};
            check('Extras preserved',extra.checked && get('#create-summary-extras').textContent===extraTotal);
            await pause(1400); window.roomsTest.roomDelay=0;
            check('Stale availability response cannot overwrite the saved inventory',window.availableRoomTypes[0].available_rooms===8 && get('#reservation-room-supplement-total-input').value==='500.00');
            window.roomsTest.delay=800;
            change('[data-field="quantity"]','7'); await pause(600); change('[data-field="quantity"]','6');
            await until(()=>get('#departure-rooms-status').textContent.startsWith('Enregistré') && window.roomsTest.rooms[0].quantity===6);
            check('Rapid writes are serialized and latest value wins',window.roomsTest.maxActive===1 && window.availableRoomTypes[0].available_rooms===6);
            window.roomsTest.delay=0;
            get('[data-rooms-add]').click();
            change('#departure-rooms-rows tr:last-child [data-field="room_type"]','Single');
            change('#departure-rooms-rows tr:last-child [data-field="quantity"]','8');
            change('#departure-rooms-rows tr:last-child [data-field="capacity_per_room"]','1');
            await until(()=>get('#departure-rooms-status').textContent.startsWith('Enregistré'));
            check('New room gets a persisted ID and appears immediately',window.roomsTest.rooms.length===2 && Number(get('#departure-rooms-rows tr:last-child').dataset.roomId)>1 && window.availableRoomTypes.length===2);
            window.roomsTest.fail=true;
            change('[data-field="quantity"]','5'); await until(()=>get('#departure-rooms-status').dataset.error==='true');
            check('Server failure keeps dialog open and previous availability',get('#departure-rooms-modal').open && window.availableRoomTypes[0].available_rooms===6);
            window.roomsTest.fail=false; get('[data-rooms-reload]').click(); await until(()=>!get('#departure-rooms-fields').disabled);
            check('Reload restores last saved values',get('[data-field="quantity"]').value==='6');
            get('#departure-rooms-rows tr:first-child [data-rooms-remove]').click(); await until(()=>get('#departure-rooms-status').textContent.startsWith('Enregistré'));
            const roomingInvalid=()=>{const pill=get('#rooming-status-pill');return pill.className.includes('is-invalid') || pill.textContent.includes('invalid');};
            check('Removing allocated inventory keeps travelers and flags invalid room',window.reservationState.roomAllocations[0].traveler_keys.length===2 && roomingInvalid() && get('[data-rooming-room-type] option:checked').textContent.includes('indisponible'));
            change('[data-field="quantity"]','0'); await until(()=>get('#departure-rooms-status').textContent.startsWith('Enregistré'));
            check('Zero inventory clears stale available rooms',window.availableRoomTypes.length===0);
            get('[data-rooms-default]').click(); await until(()=>get('#departure-rooms-status').textContent.startsWith('Enregistré'));
            check('Default configuration uses departure capacity',window.roomsTest.rooms.length===1 && window.roomsTest.rooms[0].quantity===10 && get('#departure-rooms-summary').textContent.includes('20/20'));
            change('[data-field="room_type"]','<img src=x onerror="window.roomXss=true">'); await until(()=>get('#departure-rooms-status').textContent.startsWith('Enregistré'));
            check('Room labels rendered as text',!window.roomXss && !get('#rooming-available-rooms img'));
            change('[data-field="room_type"]','Double'); await until(()=>get('#departure-rooms-status').textContent.startsWith('Enregistré'));
            get('[data-rooms-close]').click(); await pause(100);
            check('Close returns focus to trigger',!get('#departure-rooms-modal').open && document.activeElement.id==='btn-manage-departure-rooms');
            get('#btn-manage-departure-rooms').click(); await until(()=>!get('#departure-rooms-fields').disabled);
            check('Reopening reads saved configuration',get('[data-field="quantity"]').value==='10');
            return checks;
        }).toString()+')()');
        checks.push({name:'No JavaScript exceptions',passed:errors.length===0,errors:[...errors]});
        results.push({width,checks});
        const screenshot=await cdp('Page.captureScreenshot',{format:'png'}); await writeFile(join(folder,'rooms-'+width+'.png'),Buffer.from(screenshot.data,'base64'));
    }
    await writeFile(join(folder,'results.json'),JSON.stringify(results,null,2));
    const checks=results.flatMap(r=>r.checks),failed=checks.filter(c=>!c.passed);
    console.log(JSON.stringify({passed:checks.length-failed.length,total:checks.length,failed},null,2));
    if(failed.length)process.exitCode=1;
    await cdp('Browser.close').catch(()=>{});
} finally {if(socket)socket.close();chrome.kill();}

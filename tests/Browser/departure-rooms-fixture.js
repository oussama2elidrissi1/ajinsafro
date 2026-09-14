// Browser-only transport fixture. Production scripts and Blade templates are used unchanged.
window.reservationState = {selectedTourId:'1',selectedDepartureId:'1',selectedTravelDateId:'1',availableRooms:[],pricing:{unit_price:17950}};
window.roomsTest = {
    version:1, nextId:2, delay:0, fail:false, puts:[], active:0, maxActive:0,
    departure:{id:1,voyage_id:1,start_date:'2026-10-04',end_date:'2026-10-15',total_capacity:20,reserved_capacity:0,available_capacity:20,available_places:20},
    rooms:[{id:1,room_type:'Double',quantity:10,capacity_per_room:2,supplement:0,hotel_id:null}],
    availability:function () {
        return {success:true,mode:this.rooms.some(r => r.quantity > 0) ? 'rooms':'blocked',rooms_source:'departure_room_allocations',departure:this.departure,pricing:{unit_price:17950},rooms:this.rooms.filter(r => r.quantity > 0).map(r => ({
            id:r.id,departure_hotel_room_id:r.id,room_source_id:r.id,room_source_type:'departure_room_allocation',room_type:r.room_type,capacity:r.capacity_per_room,available_rooms:r.quantity,available_places:r.quantity*r.capacity_per_room,unit_supplement:r.supplement
        }))};
    },
    snapshot:function () { return {departure:this.departure,rooms:structuredClone(this.rooms),revision:String(this.version).padStart(64,'0'),hotels:[{id:10,hotel_name:'Hôtel du Nil'}]}; }
};
window.fetch = async function (url, options = {}) {
    const test = window.roomsTest;
    const response = (data, status = 200) => new Response(JSON.stringify(data), {status,headers:{'Content-Type':'application/json'}});
    if (String(url).includes('room-allocations')) {
        if (options.method === 'PUT') {
            const payload = JSON.parse(options.body); test.puts.push(payload); test.active++; test.maxActive = Math.max(test.maxActive,test.active);
            await new Promise(done => setTimeout(done,test.delay)); test.active--;
            if (test.fail) return response({message:'Stock déjà réservé.'},422);
            if (payload.revision !== test.snapshot().revision) return response({message:'Les chambres ont été modifiées ailleurs. Rechargez.'},409);
            test.rooms = payload.rooms.map(r => ({...r,id:r.id || test.nextId++})); test.version++;
            return response({...test.snapshot(),availability:test.availability()});
        }
        return response(test.snapshot());
    }
    if (String(url).includes('voyage-departures')) return response({success:true,departures:[{...test.departure,wp_travel_date_id:1,unit_price:17950}]});
    if (String(url).includes('departure-hotels-rooms')) {
        const snapshot = structuredClone(test.availability());
        await new Promise(done => setTimeout(done,test.roomDelay || 0));
        return response(snapshot);
    }
    if (String(url).includes('extras')) return response({extras:[]});
    throw new Error('Unexpected fixture request: ' + url);
};

(() => {
    const validPoint = (lat, lng) => lat !== null && lng !== null && lat !== '' && lng !== '' && Number.isFinite(Number(lat)) && Number.isFinite(Number(lng)) && Math.abs(Number(lat)) <= 90 && Math.abs(Number(lng)) <= 180;
    function create(element, center = [-14.2, -51.9], zoom = 4) {
        if (typeof L === 'undefined') { element.textContent = 'O mapa não carregou. Consulte a cidade e o endereço informados no perfil.'; return null; }
        const map = L.map(element, {scrollWheelZoom:false}).setView(center, zoom);
        const tileUrl = element.dataset.tileUrl || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
        L.tileLayer(tileUrl, {maxZoom:19,referrerPolicy:'strict-origin-when-cross-origin',attribution:'&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'}).addTo(map);
        return map;
    }
    window.ServiMaps = {create,validPoint};
    document.addEventListener('DOMContentLoaded', () => {
        const element = document.querySelector('[data-provider-map]');
        const source = document.getElementById('mapProviders');
        if (!element || !source) return;
        let points = [];
        try { points = JSON.parse(source.textContent); } catch (_) {}
        let map = null; const markers = [];
        const disclosure = element.closest('details');
        function initialize() {
            if (element.offsetParent === null) return;
            if (map) { map.invalidateSize(); return; }
            const visible = points.filter(point => validPoint(point.latitude,point.longitude));
            if (!visible.length) { element.classList.add('map-message'); element.textContent = 'Nenhuma empresa desta página publicou uma localização no mapa. Confira as regiões atendidas nos resultados.'; return; }
            map = create(element); if (!map) return;
            points.forEach((point,index) => {
                if (!validPoint(point.latitude,point.longitude)) return;
                const marker = L.marker([Number(point.latitude),Number(point.longitude)],{title:point.nome_empresa}).addTo(map);
                const popup = document.createElement('div'), title = document.createElement('strong'), link = document.createElement('a');
                title.textContent = point.nome_empresa; link.href = 'perfil_empresa.php?id=' + encodeURIComponent(point.usuario_id); link.textContent = 'Ver perfil da empresa'; link.style.display = 'block';
                popup.append(title,link); marker.bindPopup(popup); markers[index] = marker;
            });
            map.fitBounds(L.featureGroup(markers.filter(Boolean)).getBounds(),{padding:[28,28],maxZoom:15});
        }
        if (disclosure) disclosure.addEventListener('toggle', initialize);
        document.addEventListener('app:panel', () => requestAnimationFrame(initialize));
        window.focarNoMapa = index => {
            if (disclosure) disclosure.open = true;
            requestAnimationFrame(() => { initialize(); const marker=markers[index]; if (marker && map) { map.flyTo(marker.getLatLng(),16); marker.openPopup(); element.focus(); } });
        };
        initialize();
    });
})();

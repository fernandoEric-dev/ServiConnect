document.addEventListener('DOMContentLoaded', () => {
    const form=document.querySelector('[data-profile-editor]'); if(!form)return;
    const fold=value=>value.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
    const state=form.querySelector('[data-address-state]'), city=form.querySelector('[data-address-city]'), code=document.getElementById('municipioIbge'), cep=form.querySelector('[data-address-cep]'), status=document.getElementById('cepStatus');
    const citiesCache=new Map();
    async function citiesFor(uf) {
        if(!/^[A-Z]{2}$/.test(uf))return [];
        if(!citiesCache.has(uf)) citiesCache.set(uf,fetch('data/cidades/'+uf+'.json').then(response=>{if(!response.ok)throw new Error();return response.json();}).catch(error=>{citiesCache.delete(uf);throw error;}));
        return citiesCache.get(uf);
    }
    async function fillCities(select,uf,selected='',useIds=false) {
        select.disabled=true;
        try {
            const cities=await citiesFor(uf);
            select.replaceChildren(new Option(uf?'Selecione uma cidade':'Selecione o estado',''));
            cities.forEach(item=>{const option=new Option(item.nome,useIds?item.id:item.nome);option.dataset.id=item.id;select.append(option);});
            const match=cities.find(item=>fold(item.nome)===fold(selected)||item.id===selected);
            if(match)select.value=useIds?match.id:match.nome;
            return match || null;
        } finally {select.disabled=false;}
    }
    function updateCode(){code.value=city.selectedOptions[0]?.dataset.id || '';}
    fillCities(city,state.value,city.dataset.currentCity||'').then(updateCode).catch(()=>{status.textContent='A lista de cidades não carregou. Tente novamente atualizando a página.';});
    state.addEventListener('change',()=>{city.dataset.currentCity='';code.value='';clearPoint();fillCities(city,state.value).catch(()=>{status.textContent='Não foi possível carregar as cidades.';});});
    city.addEventListener('change',()=>{updateCode();clearPoint();});
    const serviceFilter=document.getElementById('serviceFilter');
    if(serviceFilter){
        const choices=Array.from(form.querySelectorAll('.choice-service')),groups=Array.from(form.querySelectorAll('.service-group')),count=document.getElementById('serviceCount'),error=document.getElementById('serviceError');
        const updateCount=()=>{const total=form.querySelectorAll('[name="servicos[]"]:checked').length;count.textContent=total+(total===1?' serviço selecionado':' serviços selecionados');};
        serviceFilter.addEventListener('input',()=>{const query=fold(serviceFilter.value.trim());choices.forEach(choice=>{choice.hidden=!fold(choice.textContent+' '+choice.querySelector('input').value+' '+(choice.dataset.aliases||'')).includes(query);});groups.forEach(group=>{group.hidden=!Array.from(group.querySelectorAll('.choice-service')).some(choice=>!choice.hidden);});});
        choices.forEach(choice=>choice.querySelector('input').addEventListener('change',event=>{if(form.querySelectorAll('[name="servicos[]"]:checked').length>20){event.target.checked=false;error.textContent='Selecione até 20 serviços.';}else error.textContent='';updateCount();}));
        updateCount();
    }
    const regionState=document.getElementById('regionState'),regionCity=document.getElementById('regionCity'),regionsField=document.getElementById('selectedCities'),regionStatus=document.getElementById('regionStatus');
    let regions=[];try{regions=regionsField?JSON.parse(regionsField.value):[];}catch(_){}
    function renderRegions(){
        if(!regionsField)return;const chips=document.getElementById('regionChips');chips.replaceChildren();
        regions.forEach((item,index)=>{const chip=document.createElement('span'),label=document.createElement('span'),remove=document.createElement('button');chip.className='location-chip';label.textContent=item.cidade+' / '+item.uf;remove.type='button';remove.textContent='×';remove.setAttribute('aria-label','Remover '+item.cidade);remove.addEventListener('click',()=>{regions.splice(index,1);renderRegions();});chip.append(label,remove);chips.append(chip);});
        regionsField.value=JSON.stringify(regions);
    }
    if(regionState){
        const loadRegions=()=>fillCities(regionCity,regionState.value,'',true).catch(()=>{regionStatus.textContent='Não foi possível carregar a lista de cidades.';});
        regionState.addEventListener('change',loadRegions);loadRegions();renderRegions();
        document.getElementById('addRegion').addEventListener('click',()=>{
            const selected=regionCity.selectedOptions[0];
            if(!regionCity.value||!regionState.value){regionStatus.textContent='Selecione um estado e uma cidade.';return;}
            if(regions.some(item=>item.id===regionCity.value)){regionStatus.textContent='Esta cidade já está na sua lista.';return;}
            if(regions.length>=20){regionStatus.textContent='Você pode selecionar até 20 cidades.';return;}
            regions.push({id:regionCity.value,cidade:selected.textContent,uf:regionState.value});renderRegions();regionStatus.textContent='Cidade adicionada. Salve o perfil para confirmar.';
        });
    }
    cep.addEventListener('blur',async()=>{
        const value=cep.value.replace(/\D/g,'');if(value.length!==8)return;status.textContent='Consultando CEP…';
        try {
            const response=await fetch('https://viacep.com.br/ws/'+value+'/json/');if(!response.ok)throw new Error();
            const address=await response.json();if(cep.value.replace(/\D/g,'')!==value)return;if(address.erro)throw new Error();
            const suffix=form.dataset.provider==='1'?'Empresa':'';
            const fields=suffix?[['logradouroEmpresa','logradouro'],['bairroEmpresa','bairro']]:[['logradouro','logradouro'],['bairro','bairro']];
            fields.forEach(([id,key])=>{document.getElementById(id).value=address[key]||'';});
            state.value=address.uf||'';await fillCities(city,state.value,address.localidade||'');updateCode();clearPoint();
            status.textContent='Endereço preenchido. Confira os dados e informe o número.';
        } catch(_){status.textContent='Não foi possível consultar este CEP. Você pode preencher os campos e selecionar a cidade.';}
    });
    const photo=document.getElementById('fotoPerfil');
    photo.addEventListener('change',()=>{
        const file=photo.files[0];if(!file)return;const message=document.getElementById('photoStatus');
        if(file.size>2*1024*1024){message.textContent='Escolha uma imagem de até 2 MB.';photo.value='';return;}
        const url=URL.createObjectURL(file),image=document.getElementById('photoPreview');image.onload=()=>URL.revokeObjectURL(url);image.src=url;message.textContent='Prévia atualizada. Salve o perfil para confirmar.';
    });
    const mapElement=document.getElementById('profileMap'),lat=document.getElementById('profileLat'),lng=document.getElementById('profileLng'),changed=document.getElementById('mapChanged'),publish=document.getElementById('mapPublic'),mapStatus=document.getElementById('mapStatus');
    let map=null,marker=null;
    function place(latValue,lngValue,edited=true){
        if(!map||!ServiMaps.validPoint(latValue,lngValue))return;
        if(!marker){marker=L.marker([latValue,lngValue],{draggable:true,title:'Localização da sua empresa'}).addTo(map);marker.on('dragend',()=>{const point=marker.getLatLng();place(point.lat,point.lng);});}else marker.setLatLng([latValue,lngValue]);
        lat.value=Number(latValue).toFixed(8);lng.value=Number(lngValue).toFixed(8);if(edited)changed.value='1';mapStatus.textContent='Ponto marcado. Confira a localização e a autorização antes de salvar.';
    }
    function clearPoint(){
        if(!mapElement)return;if(marker&&map)map.removeLayer(marker);marker=null;lat.value='';lng.value='';changed.value='1';publish.checked=false;mapStatus.textContent='O endereço mudou. Marque novamente a localização da empresa.';
    }
    async function centerCity(){
        if(!map)return;const id=code.value;if(!/^\d{7}$/.test(id)){mapStatus.textContent='Selecione a cidade do endereço primeiro.';return;}
        mapStatus.textContent='Localizando a cidade…';
        try{
            const response=await fetch('https://servicodados.ibge.gov.br/api/v3/malhas/municipios/'+id+'?formato=application%2Fvnd.geo%2Bjson&qualidade=minima');
            if(!response.ok)throw new Error();const geometry=await response.json();if(code.value!==id)return;const bounds=L.geoJSON(geometry).getBounds();if(!bounds.isValid())throw new Error();map.fitBounds(bounds,{maxZoom:14,padding:[20,20]});mapStatus.textContent='Cidade centralizada. Toque no local exato da empresa para criar o marcador.';
        }catch(_){mapStatus.textContent='Não foi possível centralizar automaticamente. Você pode aproximar o mapa e escolher o ponto manualmente.';}
    }
    function initializeMap(){
        if(form.closest('[data-panel]')&&window.location.hash!=='#perfil')return;
        if(!mapElement||mapElement.offsetParent===null)return;if(map){map.invalidateSize();return;}
        map=ServiMaps.create(mapElement);if(!map)return;map.on('click',event=>place(event.latlng.lat,event.latlng.lng));
        if(ServiMaps.validPoint(lat.value,lng.value)){place(Number(lat.value),Number(lng.value),false);map.setView([Number(lat.value),Number(lng.value)],16);}
    }
    if(mapElement){
        form.querySelectorAll('[data-address-part]').forEach(input=>{if(input!==state&&input!==city)input.addEventListener('change',clearPoint);});
        document.getElementById('centerCity').addEventListener('click',()=>{initializeMap();centerCity();});
        document.getElementById('removeMapPoint').addEventListener('click',()=>{clearPoint();mapStatus.textContent='A localização será removida ao salvar o perfil.';});
        [lat,lng].forEach(input=>input.addEventListener('input',()=>{changed.value='1';if(ServiMaps.validPoint(lat.value,lng.value)){initializeMap();place(Number(lat.value),Number(lng.value));if(map)map.setView([Number(lat.value),Number(lng.value)],16);}else mapStatus.textContent='Informe latitude e longitude válidas.';}));
        document.getElementById('useDeviceLocation').addEventListener('click',()=>{
            if(!navigator.geolocation){mapStatus.textContent='Seu navegador não oferece localização. Marque o ponto manualmente.';return;}
            mapStatus.textContent='Aguardando a permissão de localização do dispositivo…';
            navigator.geolocation.getCurrentPosition(position=>{initializeMap();place(position.coords.latitude,position.coords.longitude);if(map)map.setView([position.coords.latitude,position.coords.longitude],16);},()=>{mapStatus.textContent='Não foi possível obter a localização. Escolha o ponto no mapa.';},{enableHighAccuracy:false,timeout:10000,maximumAge:60000});
        });
        document.addEventListener('app:panel',()=>requestAnimationFrame(initializeMap));initializeMap();
    }
});

// js/mapa_servicos.js

// Dados de exemplo de empresas (Isto virá do seu PHP/Banco de Dados real)
const empresasData = [
    { nome: "Clean Solution Ltda.", lat: -23.550520, lng: -46.633308, servico: "Limpeza" },
    { nome: "SafeZone Vigilância", lat: -23.561387, lng: -46.656549, servico: "Segurança" }
];

let map;

/**
 * Inicializa o mapa usando a biblioteca Leaflet.
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Configura o centro do mapa (Ex: São Paulo)
    const centroInicial = [-23.550520, -46.633308]; 
    
    // 2. Cria o mapa na div #map
    map = L.map('map').setView(centroInicial, 12); // Coordenadas e nível de zoom (12)

    // 3. Adiciona as camadas (tiles) do OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // 4. Adiciona os marcadores das empresas
    empresasData.forEach(empresa => {
        adicionarMarcador(empresa);
    });
});


/**
 * Cria um marcador (pin) no mapa para uma empresa específica.
 */
function adicionarMarcador(empresa) {
    const popupContent = `
        <div>
            <h4>${empresa.nome}</h4>
            <p>Serviço: ${empresa.servico}</p>
            <button onclick="solicitarOrcamento('${empresa.nome}')" 
                    style="background-color: #FFC400; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px;">
                Solicitar Orçamento
            </button>
        </div>
    `;
    
    // Cria o marcador com as coordenadas [lat, lng]
    L.marker([empresa.lat, empresa.lng])
        .addTo(map)
        .bindPopup(popupContent); // Adiciona o popup (perfil) ao marcador
}

/**
 * Função de exemplo que seria chamada ao clicar no botão 'Solicitar Orçamento'.
 */
function solicitarOrcamento(nomeEmpresa) {
    alert(`Preparando solicitação de orçamento para: ${nomeEmpresa}`);
    // Futuramente, esta função deve abrir o formulário de Solicitação de Orçamento.
}
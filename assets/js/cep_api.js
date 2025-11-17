document.addEventListener('DOMContentLoaded', () => {
    
    // ====================================================
    // FUNÇÃO PRINCIPAL DE BUSCA DE CEP
    // ====================================================

    const limparCEP = (cep) => cep.replace(/\D/g, '');

    const buscarCEP = async (cepInput, callback) => {
        const cep = limparCEP(cepInput.value);

        if (cep.length !== 8) {
            if (cep.length > 0) {
                console.error("CEP inválido. Deve conter 8 dígitos.");
            }
            return;
        }

        // Informa que está buscando
        callback({ logradouro: '... buscando ...', bairro: '... buscando ...', localidade: '... buscando ...', uf: '... buscando ...' });

        try {
            const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            const data = await response.json();

            if (data.erro) {
                alert('CEP não encontrado. Por favor, digite o endereço manualmente.');
                callback({ logradouro: '', bairro: '', localidade: '', uf: '' });
                return;
            }

            callback(data); // Chama o callback com os dados da API
            
            // Foca no campo de número após preencher os dados
            const formId = cepInput.closest('form').id;
            if (formId === 'formCadastroEmpregado') {
                document.getElementById('numero').focus();
            } else if (formId === 'formCadastroEmpresa') {
                document.getElementById('numeroEmpresa').focus();
            }

        } catch (error) {
            console.error('Erro ao buscar o CEP:', error);
            alert('Houve um erro na comunicação com a API de CEP.');
            callback({ logradouro: '', bairro: '', localidade: '', uf: '' });
        }
    };
    
    // ====================================================
    // INICIALIZAÇÃO DO LISTENER (EMPREGADO)
    // ====================================================

    const cepInputEmpregado = document.getElementById('cep');

    if (cepInputEmpregado) {
        cepInputEmpregado.addEventListener('blur', (e) => {
            if (!e.target.value) return; 
            buscarCEP(e.target, (data) => {
                document.getElementById('logradouro').value = data.logradouro || '';
                document.getElementById('bairro').value = data.bairro || '';
                document.getElementById('cidade').value = data.localidade || '';
                document.getElementById('estado').value = data.uf || '';
            });
        });
    }
    
    // ====================================================
    // INICIALIZAÇÃO DO LISTENER (EMPRESA)
    // ====================================================
    
    const cepInputEmpresa = document.getElementById('cepEmpresa');

    if (cepInputEmpresa) {
        cepInputEmpresa.addEventListener('blur', (e) => {
            if (!e.target.value) return; 
            buscarCEP(e.target, (data) => {
                document.getElementById('logradouroEmpresa').value = data.logradouro || '';
                document.getElementById('bairroEmpresa').value = data.bairro || '';
                document.getElementById('cidadeEmpresa').value = data.localidade || '';
                document.getElementById('estadoEmpresa').value = data.uf || '';
            });
        });
    }
});
document.addEventListener('DOMContentLoaded', () => {

    // ====================================================
    // 1. FUNÇÕES GERAIS DE MÁSCARA E VALIDAÇÃO (MANTIDAS)
    // ====================================================

    function applyMask(input, pattern) {
        let value = input.value.replace(/\D/g, "");
        let maskedValue = "";
        let i = 0;
        for (let j = 0; j < pattern.length; j++) {
            if (i >= value.length) break;

            if (pattern[j] === "9") {
                maskedValue += value[i];
                i++;
            } else {
                maskedValue += pattern[j];
            }
        }
        input.value = maskedValue;
    }

    function validarCNPJ(cnpj) {
        cnpj = cnpj.replace(/[^\d]/g, '');
        if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) return false;

        let tamanho = cnpj.length - 2;
        let numeros = cnpj.substring(0, tamanho);
        let digitos = cnpj.substring(tamanho);
        let soma = 0;
        let pos = tamanho - 7;

        for (let i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) pos = 9;
        }

        let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado !== parseInt(digitos.charAt(0))) return false;

        tamanho = tamanho + 1;
        numeros = cnpj.substring(0, tamanho);
        soma = 0;
        pos = tamanho - 7;

        for (let i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) pos = 9;
        }

        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        return resultado === parseInt(digitos.charAt(1));
    }

    function validarSenhas(senha, confirmarSenha) {
        // Assume que a senha de login (no login.php) só precisa de um mínimo de 6 caracteres
        if (typeof confirmarSenha === 'undefined') {
            return senha.length >= 6;
        }
        return senha === confirmarSenha && senha.length >= 6;
    }

    // FUNÇÃO REAL: VERIFICAR CNPJ ATIVO (Usando Open CNPJ)
    async function verificarCNPJ_API(cnpj) {
        const cnpjLimpo = cnpj.replace(/[^\d]/g, ''); 
        try {
            const response = await fetch(`https://api.opencnpj.org/${cnpjLimpo}`);
            if (response.ok) {
                const data = await response.json();
                return { status: 'ativo', mensagem: `CNPJ encontrado. Razão Social: ${data.razao_social}`, dados: data };
            }
            return { status: 'inativo', mensagem: 'CNPJ não encontrado ou inativo.' };
        } catch (error) {
            return { status: 'erro_rede', mensagem: `Falha na comunicação com a API Open CNPJ: ${error.message}` };
        }
    }


    // ====================================================
    // 2. INICIALIZAÇÃO E VALIDAÇÃO DE EMPRESA (CADASTRO)
    // ====================================================

    const formEmpresa = document.getElementById('formCadastroEmpresa');

    if (formEmpresa) {
        const submitButton = formEmpresa.querySelector('button[type="submit"]');

        // Mapeamento das máscaras (CNPJ e Telefone)
        const inputCnpj = document.getElementById('cnpj');
        const inputTelefoneEmpresa = document.getElementById('telefoneEmpresa');
        const inputCepEmpresa = document.getElementById('cepEmpresa');
        
        if (inputCnpj) inputCnpj.addEventListener('input', () => applyMask(inputCnpj, '99.999.999/9999-99'));
        if (inputCepEmpresa) inputCepEmpresa.addEventListener('input', () => applyMask(inputCepEmpresa, '99999-999'));
        if (inputTelefoneEmpresa) {
            inputTelefoneEmpresa.addEventListener('input', () => {
                const pattern = inputTelefoneEmpresa.value.replace(/\D/g, '').length > 10 ? '(99) 99999-9999' : '(99) 9999-9999';
                applyMask(inputTelefoneEmpresa, pattern);
            });
        }
        
        // Função para mapear todos os campos (CRÍTICO para o Back-end)
        const getFormFields = () => {
             return {
                nome: document.getElementById('nome').value,
                cnpj: document.getElementById('cnpj').value,
                tipo: document.getElementById('tipo').value, 
                email_acesso: document.getElementById('emailAcesso').value,
                senha: document.getElementById('senha').value,
                confirmarSenha: document.getElementById('confirmarSenha').value,
                
                telefoneEmpresa: document.getElementById('telefoneEmpresa').value,
                responsavel: document.getElementById('responsavel').value,
                descricao: document.getElementById('descricao').value,
                regiao: document.getElementById('regiao').value,
                horario: document.getElementById('horario').value,

                cepEmpresa: document.getElementById('cepEmpresa').value,
                logradouroEmpresa: document.getElementById('logradouroEmpresa').value,
                numeroEmpresa: document.getElementById('numeroEmpresa').value,
                complementoEmpresa: document.getElementById('complementoEmpresa').value,
                bairroEmpresa: document.getElementById('bairroEmpresa').value,
                cidadeEmpresa: document.getElementById('cidadeEmpresa').value,
                estadoEmpresa: document.getElementById('estadoEmpresa').value,
            };
        };


        // VALIDAÇÃO E ENVIO ASSÍNCRONO NO CADASTRO
        formEmpresa.addEventListener('submit', async (e) => { 
            e.preventDefault();

            const dados = getFormFields();

            // 1. Validações Locais
            if (!validarCNPJ(dados.cnpj)) {
                alert('Erro (Empresa): O CNPJ digitado é inválido no formato.');
                document.getElementById('cnpj').focus();
                return;
            }
            if (!validarSenhas(dados.senha, dados.confirmarSenha)) {
                alert('Erro (Empresa): As senhas não coincidem ou são muito curtas (mínimo 6 caracteres).');
                document.getElementById('senha').focus();
                return;
            }
            if (dados.tipo === "") {
                 alert('Erro (Empresa): Por favor, selecione o Tipo de Empresa.');
                 document.getElementById('tipo').focus();
                 return;
            }
            
            // --- 2. INÍCIO DA VERIFICAÇÃO CNPJ E ENVIO ---
            submitButton.disabled = true; 
            submitButton.textContent = 'Verificando CNPJ... Aguarde!';
            
            // Chama a API de CNPJ
            const resultadoCNPJ = await verificarCNPJ_API(dados.cnpj);
            
            if (resultadoCNPJ.status !== 'ativo') {
                submitButton.disabled = false;
                submitButton.textContent = 'Cadastrar Empresa';
                alert(`ERRO DE VERIFICAÇÃO: ${resultadoCNPJ.mensagem}`);
                return;
            }

            // --- 3. CNPJ VÁLIDO. ENVIA PARA O BACK-END PHP ---
            submitButton.textContent = 'Finalizando Cadastro...';
            
            try {
                // Requisição Fetch para o Controller PHP
                const response = await fetch('backend/controllers/CadastroEmpresaController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dados) // Envia todos os dados como JSON
                });
                
                const resultadoCadastro = await response.json();

                // 4. Lida com a resposta do Servidor
                if (resultadoCadastro.success) {
                    // Redireciona DIRETAMENTE para a página de Login após sucesso
                    alert(resultadoCadastro.message + ' Você será redirecionado para a tela de Login.');
                    window.location.href = 'login.php'; 
                } else {
                    // Erro do PHP (ex: CNPJ duplicado)
                    const msg = encodeURIComponent(resultadoCadastro.message);
                    const urlFinal = `verificacao_status.html?status=erro&mensagem=${msg}`;
                    window.location.href = urlFinal;
                }

            } catch (error) {
                alert('Erro na comunicação com o servidor. Verifique o XAMPP e a URL do Controller.');
            } finally {
                 submitButton.disabled = false;
                 submitButton.textContent = 'Cadastrar Empresa';
            }
        });
    }

    // ====================================================
    // 3. VALIDAÇÃO DE LOGIN (login.php) - FINAL
    // ====================================================

    const formLogin = document.getElementById('formLogin');

    if (formLogin) {
        const inputIdentificacao = document.getElementById('identificacao');
        const inputSenhaLogin = document.getElementById('senhaLogin');
        const submitButtonLogin = document.getElementById('submitButtonLogin'); 

        // Máscara CNPJ no Login
        if (inputIdentificacao) {
            inputIdentificacao.addEventListener('input', () => {
                applyMask(inputIdentificacao, '99.999.999/9999-99');
            });
        }

        // VALIDAÇÃO E ENVIO NO LOGIN (FINAL)
        formLogin.addEventListener('submit', async (e) => {
            e.preventDefault();

            const docValue = inputIdentificacao.value;
            const docLimpo = docValue.replace(/\D/g, ''); // CNPJ limpo
            const senhaValue = inputSenhaLogin.value;

            // 1. Validações Locais
            if (docLimpo.length !== 14 || !validarCNPJ(docLimpo)) {
                alert('Erro: O campo CNPJ deve conter 14 dígitos e ser válido.');
                inputIdentificacao.focus();
                return;
            }
            if (senhaValue.length < 6) {
                alert('Erro: A senha deve ter no mínimo 6 caracteres.');
                inputSenhaLogin.focus();
                return;
            }
            
            // --- 2. ENVIO PARA O BACK-END (AUTENTICAÇÃO) ---
            submitButtonLogin.disabled = true;
            submitButtonLogin.textContent = 'Verificando Acesso...';

            try {
                // Requisição Fetch para o Controller de Autenticação
                const response = await fetch('backend/controllers/AuthController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        identificacao: docLimpo, // Envia CNPJ limpo
                        senha: senhaValue
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Login bem-sucedido: redireciona para o dashboard correto
                    alert(result.message);
                    window.location.href = result.redirect; 
                } else {
                    alert('Falha no Login: ' + result.message);
                }

            } catch (error) {
                alert('Erro de comunicação com o servidor.');
            } finally {
                submitButtonLogin.disabled = false;
                submitButtonLogin.textContent = 'Entrar';
            }
        });
    }

});
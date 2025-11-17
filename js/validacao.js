document.addEventListener('DOMContentLoaded', () => {

    // ====================================================
    // 1. FUNÇÕES GERAIS DE MÁSCARA
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

    // ====================================================
    // 2. FUNÇÕES GERAIS DE VALIDAÇÃO LÓGICA
    // ====================================================

    function validarCPF(cpf) {
        cpf = cpf.replace(/[^\d]/g, '');
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;

        let soma = 0;
        let resto;
        for (let i = 1; i <= 9; i++) soma = soma + parseInt(cpf.substring(i - 1, i)) * (11 - i);
        resto = (soma * 10) % 11;
        if ((resto === 10) || (resto === 11)) resto = 0;
        if (resto !== parseInt(cpf.substring(9, 10))) return false;

        soma = 0;
        for (let i = 1; i <= 10; i++) soma = soma + parseInt(cpf.substring(i - 1, i)) * (12 - i);
        resto = (soma * 10) % 11;
        if ((resto === 10) || (resto === 11)) resto = 0;
        if (resto !== parseInt(cpf.substring(10, 11))) return false;

        return true;
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
        return senha === confirmarSenha && senha.length >= 6;
    }

    // FUNÇÃO REAL: VERIFICAR CNPJ ATIVO (Usando Open CNPJ)
    async function verificarCNPJ_API(cnpj) {
        const cnpjLimpo = cnpj.replace(/[^\d]/g, ''); 
        
        try {
            const response = await fetch(`https://api.opencnpj.org/${cnpjLimpo}`);

            if (response.status === 404) {
                return { status: 'inativo', mensagem: 'CNPJ não encontrado ou não ativo no cadastro da API (404).' };
            }

            if (response.ok) {
                const data = await response.json();
                
                return { 
                    status: 'ativo', 
                    mensagem: `CNPJ encontrado. Razão Social: ${data.razao_social}`,
                    dados: data 
                };
            }
            
            return { status: 'erro', mensagem: `Erro na API: ${response.status} ${response.statusText}` };

        } catch (error) {
            return { status: 'erro_rede', mensagem: `Falha na comunicação com a API Open CNPJ: ${error.message}` };
        }
    }


    // ====================================================
    // 3. INICIALIZAÇÃO E VALIDAÇÃO DE EMPREGADO (formCadastroEmpregado)
    // ====================================================

    const formEmpregado = document.getElementById('formCadastroEmpregado');

    if (formEmpregado) {
        const inputCpf = document.getElementById('cpf');
        const inputTelefone = document.getElementById('telefone');
        const inputCep = document.getElementById('cep');
        const submitButtonEmpregado = formEmpregado.querySelector('button[type="submit"]');

        // Máscaras (Mantidas)
        if (inputCpf) inputCpf.addEventListener('input', () => applyMask(inputCpf, '999.999.999-99'));
        
        if (inputTelefone) {
            inputTelefone.addEventListener('input', () => {
                const pattern = inputTelefone.value.replace(/\D/g, '').length > 10 ? '(99) 99999-9999' : '(99) 9999-9999';
                applyMask(inputTelefone, pattern);
            });
        }

        if (inputCep) inputCep.addEventListener('input', () => applyMask(inputCep, '99999-999'));


        // VALIDAÇÃO NO ENVIO
        formEmpregado.addEventListener('submit', async (e) => {
            e.preventDefault();

            // 1. Validações Locais
            const cpfValue = document.getElementById('cpf').value;
            const senhaValue = document.getElementById('senha').value;
            const confirmarSenhaValue = document.getElementById('confirmarSenha').value;

            // Checa todas as validações locais ANTES de simular o envio
            if (!validarCPF(cpfValue)) {
                alert('Erro (Empregado): O CPF digitado é inválido.');
                document.getElementById('cpf').focus();
                return;
            }

            if (!validarSenhas(senhaValue, confirmarSenhaValue)) {
                alert('Erro (Empregado): As senhas não coincidem ou são muito curtas (mínimo 6 caracteres).');
                document.getElementById('senha').focus();
                return;
            }
            
            if (document.getElementById('telefone').value.replace(/\D/g, '').length < 10) {
                alert('Erro (Empregado): O telefone está incompleto.');
                document.getElementById('telefone').focus();
                return;
            }
            
            // Se as validações locais passaram, simula o processo de cadastro
            
            // 2. Muda o estado visual
            submitButtonEmpregado.disabled = true; 
            submitButtonEmpregado.textContent = 'Finalizando Cadastro... Aguarde!';
            
            // 3. SIMULA o processamento de back-end (500ms)
            await new Promise(resolve => setTimeout(resolve, 500)); 
            
            // 4. Constrói a URL final de redirecionamento com a mensagem de sucesso e login
            const msg = encodeURIComponent("Cadastro realizado com sucesso! Retorne à página de login para acessar seu perfil."); 
            const urlFinal = `verificacao_status.html?status=sucesso&mensagem=${msg}`;
            
            // 5. Redireciona o usuário para a página de status
            window.location.href = urlFinal;
        });
    }

    // ====================================================
    // 4. INICIALIZAÇÃO E VALIDAÇÃO DE EMPRESA (formCadastroEmpresa - REDIRECIONAMENTO)
    // ====================================================

    const formEmpresa = document.getElementById('formCadastroEmpresa');

    if (formEmpresa) {
        const inputCnpj = document.getElementById('cnpj');
        const inputTipo = document.getElementById('tipo');
        const inputTelefoneEmpresa = document.getElementById('telefoneEmpresa');
        const submitButton = formEmpresa.querySelector('button[type="submit"]');

        // Máscaras (Manter listeners)
        if (inputCnpj) inputCnpj.addEventListener('input', () => applyMask(inputCnpj, '99.999.999/9999-99'));
        
        if (inputTelefoneEmpresa) {
            inputTelefoneEmpresa.addEventListener('input', () => {
                const pattern = inputTelefoneEmpresa.value.replace(/\D/g, '').length > 10 ? '(99) 99999-9999' : '(99) 9999-9999';
                applyMask(inputTelefoneEmpresa, pattern);
            });
        }


        // VALIDAÇÃO E VERIFICAÇÃO ASYNC NO ENVIO
        formEmpresa.addEventListener('submit', async (e) => { 
            e.preventDefault();

            // 1. Validações Locais
            const cnpjValue = inputCnpj.value;
            const senhaValue = document.getElementById('senha').value;
            const confirmarSenhaValue = document.getElementById('confirmarSenha').value;


            if (!validarCNPJ(cnpjValue)) {
                alert('Erro (Empresa): O CNPJ digitado é inválido no formato.');
                inputCnpj.focus();
                return;
            }
            if (!validarSenhas(senhaValue, confirmarSenhaValue)) {
                alert('Erro (Empresa): As senhas não coincidem ou são muito curtas (mínimo 6 caracteres).');
                document.getElementById('senha').focus();
                return;
            }
            if (inputTipo.value === "") {
                alert('Erro (Empresa): Por favor, selecione o tipo de empresa.');
                inputTipo.focus();
                return;
            }
            if (inputTelefoneEmpresa.value.replace(/\D/g, '').length < 10) {
                 alert('Erro (Empresa): O telefone está incompleto.');
                 inputTelefoneEmpresa.focus();
                 return;
            }
            
            // --- INÍCIO DA VERIFICAÇÃO CNPJ E REDIRECIONAMENTO ---
            
            // 1. Muda o estado visual
            submitButton.disabled = true; 
            submitButton.textContent = 'Verificando CNPJ... Aguarde!';
            
            // 2. Chama a API
            const resultado = await verificarCNPJ_API(cnpjValue);
            
            // 3. Constrói a URL final de redirecionamento
            let urlFinal = '';
            if (resultado.status === 'ativo') {
                const msg = encodeURIComponent("Obrigado por se juntar à ServiConnect!");
                urlFinal = `verificacao_status.html?status=sucesso&mensagem=${msg}`;
            } else {
                const msg = encodeURIComponent(resultado.mensagem + ". Por favor, verifique o CNPJ digitado.");
                urlFinal = `verificacao_status.html?status=erro&mensagem=${msg}`;
            }
            
            // 4. Redireciona o usuário para a página de status
            window.location.href = urlFinal;
            
            // --- FIM DA VERIFICAÇÃO CNPJ E REDIRECIONAMENTO ---
        });
    }

    // ====================================================
    // 5. VALIDAÇÃO DE LOGIN (login.html) - MODO SIMULAÇÃO
    // ====================================================

    const formLogin = document.getElementById('formLogin');

    if (formLogin) {
        const inputIdentificacao = document.getElementById('identificacao');
        const inputSenhaLogin = document.getElementById('senhaLogin');

        // Lógica de Máscara Dinâmica (Mantida)
        if (inputIdentificacao) {
            inputIdentificacao.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value.length <= 11) {
                    applyMask(inputIdentificacao, '999.999.999-99');
                    inputIdentificacao.maxLength = 14;
                } else {
                    applyMask(inputIdentificacao, '99.999.999/9999-99');
                    inputIdentificacao.maxLength = 18;
                }
            });
        }


        // VALIDAÇÃO E SIMULAÇÃO DE REDIRECIONAMENTO
        formLogin.addEventListener('submit', (e) => {
            e.preventDefault();

            const docValue = inputIdentificacao.value;
            const docLimpo = docValue.replace(/\D/g, '');

            // 1. Validação Lógica MÍNIMA (para determinar o tipo)
            let valido = false;
            let tipo = '';

            if (docLimpo.length === 11) {
                valido = validarCPF(docLimpo);
                tipo = 'empregado';
            } else if (docLimpo.length === 14) {
                valido = validarCNPJ(docLimpo);
                tipo = 'empresa';
            }

            if (!valido) {
                alert('Erro: CPF ou CNPJ inválido. Por favor, digite um documento válido para simular o login.');
                inputIdentificacao.focus();
                return;
            }

            // SIMULAÇÃO DE REGRA PARA ADMIN: Se o CNPJ/CPF for 999.xxxxxx, simula ADMIN
            let role = tipo;
            if (docLimpo.startsWith('999')) {
                role = 'admin';
            }

            // SIMULAÇÃO DE LOGIN BEM-SUCEDIDO: REDIRECIONAMENTO IMEDIATO
            let redirectUrl = '';
            if (role === 'admin') {
                redirectUrl = 'admin.html';
            } else {
                redirectUrl = `dashboard.html?role=${role}`;
            }

            window.location.href = redirectUrl;
        });
    }

});
document.addEventListener('DOMContentLoaded', () => {

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
        return senha === confirmarSenha && senha.length >= 6;
    }

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
            return { status: 'erro_rede', mensagem: `Falha na comunicação: ${error.message}` };
        }
    }

    const formEmpresa = document.getElementById('formCadastroEmpresa');

    if (formEmpresa) {
        const submitButton = formEmpresa.querySelector('button[type="submit"]');
        const inputCnpj = document.getElementById('cnpj');
        
        if (inputCnpj) inputCnpj.addEventListener('input', () => applyMask(inputCnpj, '99.999.999/9999-99'));
        
        const getFormFields = () => {
             return {
                cnpj: document.getElementById('cnpj').value,
                tipo: document.getElementById('tipo').value, 
                email_acesso: document.getElementById('emailAcesso').value,
                senha: document.getElementById('senha').value,
                confirmarSenha: document.getElementById('confirmarSenha').value,
            };
        };

        formEmpresa.addEventListener('submit', async (e) => { 
            e.preventDefault();
            const dados = getFormFields();

            if (!validarCNPJ(dados.cnpj)) {
                alert('Erro: O CNPJ digitado é inválido no formato.');
                document.getElementById('cnpj').focus(); return;
            }
            if (!validarSenhas(dados.senha, dados.confirmarSenha)) {
                alert('Erro: As senhas não coincidem ou são curtas (mínimo 6 caracteres).');
                document.getElementById('senha').focus(); return;
            }
            if (dados.tipo === "") {
                 alert('Erro: Por favor, selecione o Tipo de Conta.');
                 document.getElementById('tipo').focus(); return;
            }
            
            submitButton.disabled = true; 
            submitButton.textContent = 'Verificando CNPJ...';
            
            const resultadoCNPJ = await verificarCNPJ_API(dados.cnpj);
            
            if (resultadoCNPJ.status !== 'ativo') {
                submitButton.disabled = false;
                submitButton.textContent = 'Criar Conta e Continuar';
                alert(`ERRO DE VERIFICAÇÃO: ${resultadoCNPJ.mensagem}`);
                return;
            }

            dados.nome = resultadoCNPJ.dados.razao_social || 'Empresa em Configuração';

            submitButton.textContent = 'Criando sua conta...';
            
            try {
                const response = await fetch('backend/controllers/CadastroEmpresaController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dados)
                });
                
                const resultadoCadastro = await response.json();

                if (resultadoCadastro.success) {
                    alert('Conta criada com sucesso! Faça login para completar seu perfil.');
                    window.location.href = 'login.php'; 
                } else {
                    const msg = encodeURIComponent(resultadoCadastro.message);
                    window.location.href = `verificacao_status.html?status=erro&mensagem=${msg}`;
                }

            } catch (error) {
                alert('Erro na comunicação com o servidor.');
            } finally {
                 submitButton.disabled = false;
                 submitButton.textContent = 'Criar Conta e Continuar';
            }
        });
    }

    const formLogin = document.getElementById('formLogin');

    if (formLogin) {
        const inputIdentificacao = document.getElementById('identificacao');
        const inputSenhaLogin = document.getElementById('senhaLogin');
        const submitButtonLogin = document.getElementById('submitButtonLogin'); 

        if (inputIdentificacao) {
            inputIdentificacao.addEventListener('input', () => {
                applyMask(inputIdentificacao, '99.999.999/9999-99');
            });
        }

        formLogin.addEventListener('submit', async (e) => {
            e.preventDefault();

            const docValue = inputIdentificacao.value;
            const docLimpo = docValue.replace(/\D/g, ''); 
            const senhaValue = inputSenhaLogin.value;

            if (docLimpo.length !== 14) { 
                alert('Erro: O CNPJ deve conter 14 dígitos (sem formatação).');
                inputIdentificacao.focus();
                return;
            }
            if (senhaValue.length < 6) {
                alert('Erro: A senha deve ter no mínimo 6 caracteres.');
                inputSenhaLogin.focus();
                return;
            }
            
            submitButtonLogin.disabled = true;
            submitButtonLogin.textContent = 'Verificando Acesso...';

            try {
                const response = await fetch('backend/controllers/AuthController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        identificacao: docLimpo,
                        senha: senhaValue
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    window.location.href = result.redirect; 
                } else {
                    alert('Falha no Login: ' + result.message);
                }

            } catch (error) {
                alert('Ocorreu um erro ao conectar ao servidor. Tente novamente mais tarde.');
            } finally {
                submitButtonLogin.disabled = false;
                submitButtonLogin.textContent = 'Entrar';
            }
        });
    }

});
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

    // ====================================================
    // 3. INICIALIZAÇÃO E VALIDAÇÃO DE EMPREGADO
    // ====================================================

    const formEmpregado = document.getElementById('formCadastroEmpregado');

    if (formEmpregado) {
        const inputCpf = document.getElementById('cpf');
        const inputTelefone = document.getElementById('telefone');
        const inputCep = document.getElementById('cep');

        // Máscara CPF
        if (inputCpf) inputCpf.addEventListener('input', () => applyMask(inputCpf, '999.999.999-99'));
        
        // Máscara Telefone (com 9º dígito)
        if (inputTelefone) {
            inputTelefone.addEventListener('input', () => {
                const pattern = inputTelefone.value.replace(/\D/g, '').length > 10 ? '(99) 99999-9999' : '(99) 9999-9999';
                applyMask(inputTelefone, pattern);
            });
        }

        // Máscara CEP
        if (inputCep) inputCep.addEventListener('input', () => applyMask(inputCep, '99999-999'));


        // VALIDAÇÃO NO ENVIO
        formEmpregado.addEventListener('submit', (e) => {
            e.preventDefault();

            const cpfValue = document.getElementById('cpf').value;
            const senhaValue = document.getElementById('senha').value;
            const confirmarSenhaValue = document.getElementById('confirmarSenha').value;

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

            alert('Perfil de Empregado validado e pronto para envio!');
            // formEmpregado.submit(); 
        });
    }

    // ====================================================
    // 4. INICIALIZAÇÃO E VALIDAÇÃO DE EMPRESA (NOVO BLOCO)
    // ====================================================

    const formEmpresa = document.getElementById('formCadastroEmpresa');

    if (formEmpresa) {
        const inputCnpj = document.getElementById('cnpj');
        const inputTelefoneEmpresa = document.getElementById('telefoneEmpresa');
        const inputCepEmpresa = document.getElementById('cepEmpresa');
        const inputTipo = document.getElementById('tipo');


        // Máscara CNPJ
        if (inputCnpj) inputCnpj.addEventListener('input', () => applyMask(inputCnpj, '99.999.999/9999-99'));
        
        // Máscara Telefone Empresa
        if (inputTelefoneEmpresa) {
            inputTelefoneEmpresa.addEventListener('input', () => {
                const pattern = inputTelefoneEmpresa.value.replace(/\D/g, '').length > 10 ? '(99) 99999-9999' : '(99) 9999-9999';
                applyMask(inputTelefoneEmpresa, pattern);
            });
        }

        // Máscara CEP Empresa
        if (inputCepEmpresa) inputCepEmpresa.addEventListener('input', () => applyMask(inputCepEmpresa, '99999-999'));


        // VALIDAÇÃO NO ENVIO
        formEmpresa.addEventListener('submit', (e) => {
            e.preventDefault();

            const cnpjValue = document.getElementById('cnpj').value;

            if (!validarCNPJ(cnpjValue)) {
                alert('Erro (Empresa): O CNPJ digitado é inválido.');
                document.getElementById('cnpj').focus();
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
            
            alert('Cadastro de Empresa validado e pronto para envio!');
            // formEmpresa.submit();
        });
    }
});
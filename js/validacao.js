document.addEventListener('DOMContentLoaded', () => {
    const normalizeDocument = value => value.toUpperCase().replace(/[.\/\-\s]/g, '');

    function formatDocument(value, type) {
        const clean = normalizeDocument(value);
        if (type === 'cpf') {
            if (!/^\d*$/.test(clean) || clean.length > 11) return value.toUpperCase();
            return clean.slice(0, 11).replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        }
        if (!/^[A-Z0-9]*$/.test(clean) || clean.length > 14) return value.toUpperCase();
        return clean.slice(0, 14).replace(/^([A-Z0-9]{2})([A-Z0-9])/, '$1.$2').replace(/^([A-Z0-9]{2})\.([A-Z0-9]{3})([A-Z0-9])/, '$1.$2.$3').replace(/\.([A-Z0-9]{3})([A-Z0-9])/, '.$1/$2').replace(/([A-Z0-9]{4})([A-Z0-9]{1,2})$/, '$1-$2');
    }

    function validCpf(document) {
        if (!/^\d{11}$/.test(document) || /^(\d)\1{10}$/.test(document)) return false;
        for (const length of [9, 10]) {
            let sum = 0;
            for (let index = 0; index < length; index++) sum += Number(document[index]) * (length + 1 - index);
            const digit = ((sum * 10) % 11) % 10;
            if (Number(document[length]) !== digit) return false;
        }
        return true;
    }

    function validCnpj(document) {
        if (!/^[A-Z0-9]{12}\d{2}$/.test(document) || /^(\d)\1{13}$/.test(document)) return false;
        for (const length of [12, 13]) {
            let sum = 0;
            let weight = length - 7;
            for (let index = 0; index < length; index++) {
                sum += (document.charCodeAt(index) - 48) * weight;
                weight = weight === 2 ? 9 : weight - 1;
            }
            const digit = sum % 11 < 2 ? 0 : 11 - sum % 11;
            if (Number(document[length]) !== digit) return false;
        }
        return true;
    }

    function clearFeedback(form, feedback) {
        feedback.hidden = true;
        feedback.textContent = '';
        form.querySelectorAll('[aria-invalid]').forEach(field => field.removeAttribute('aria-invalid'));
    }

    function showFeedback(feedback, message, field = null) {
        feedback.textContent = message;
        feedback.hidden = false;
        if (field) {
            field.setAttribute('aria-invalid', 'true');
            field.focus();
        } else feedback.focus();
    }

    function validPassword(value) {
        return value.length >= 6 && new TextEncoder().encode(value).length <= 72;
    }

    async function sendJson(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(data)
        });
        let result;
        try { result = await response.json(); }
        catch { throw new Error('Não foi possível concluir a solicitação agora. Aguarde um pouco e tente novamente.'); }
        if (!response.ok || !result.success) throw new Error(result.message || 'Não foi possível concluir. Tente novamente.');
        return result;
    }

    const registration = document.getElementById('formCadastroEmpresa');
    if (registration) {
        const type = document.getElementById('tipo');
        const personType = document.getElementById('tipoPessoa');
        const personGroup = document.getElementById('tipoPessoaGrupo');
        const documentField = document.getElementById('documento');
        const nameField = document.getElementById('nome');
        const passwordField = document.getElementById('senha');
        const confirmationField = document.getElementById('confirmarSenha');
        const submit = document.getElementById('submitCadastro');
        const feedback = document.getElementById('cadastroFeedback');
        const cnpjGroup = document.getElementById('cnpjConsultaGrupo');
        const cnpjButton = document.getElementById('consultarCnpj');
        const cnpjResult = document.getElementById('cnpjConsultaResultado');
        let documentType = null;
        let lookupVersion = 0;

        function resetCnpjResult() {
            lookupVersion++;
            cnpjResult.hidden = true;
            cnpjResult.textContent = '';
            cnpjResult.removeAttribute('data-state');
            cnpjButton.disabled = false;
            cnpjButton.textContent = 'Consultar CNPJ';
        }

        function updateAccountFields() {
            const contractor = type.value === 'contratante';
            const individual = contractor && personType.value === 'fisica';
            const nextDocumentType = individual ? 'cpf' : 'cnpj';
            if (documentType && documentType !== nextDocumentType) documentField.value = '';
            documentType = nextDocumentType;
            resetCnpjResult();
            cnpjGroup.hidden = individual;
            personGroup.hidden = !contractor;
            personType.disabled = !contractor;
            documentField.maxLength = individual ? 14 : 18;
            documentField.inputMode = individual ? 'numeric' : 'text';
            documentField.placeholder = individual ? '000.000.000-00' : '00.000.000/0000-00';
            document.getElementById('documentoLabel').textContent = individual ? 'CPF' : 'CNPJ';
            document.getElementById('documentoAjuda').textContent = individual
                ? 'Use seu CPF para criar uma conta de pessoa física.'
                : 'Seu CNPJ será consultado na OpenCNPJ. Apenas empresas com situação cadastral ativa podem criar uma conta.';
            document.getElementById('nomeLabel').textContent = individual ? 'Nome completo' : type.value === 'mei' ? 'Seu nome ou nome do negócio' : 'Nome da empresa';
            nameField.autocomplete = individual ? 'name' : 'organization';
            nameField.placeholder = individual ? 'Como você quer ser identificado' : 'Como seu negócio será apresentado';
            document.getElementById('tipoAjuda').textContent = contractor
                ? 'Busque prestadores e solicite orçamentos para o serviço de que precisa.'
                : type.value === 'mei'
                    ? 'Apresente seus serviços, como jardinagem, pintura ou manutenção, e receba pedidos de orçamento.'
                    : 'Apresente os serviços da sua empresa e receba pedidos de orçamento.';
            clearFeedback(registration, feedback);
        }

        type.addEventListener('change', updateAccountFields);
        personType.addEventListener('change', updateAccountFields);
        documentField.addEventListener('input', () => {
            documentField.value = formatDocument(documentField.value, documentType);
            documentField.removeAttribute('aria-invalid');
            resetCnpjResult();
        });
        updateAccountFields();

        cnpjButton.addEventListener('click', async () => {
            const documentValue = normalizeDocument(documentField.value);
            if (documentType !== 'cnpj' || !validCnpj(documentValue)) {
                showFeedback(feedback, 'Informe um CNPJ válido para consultar.', documentField);
                return;
            }
            clearFeedback(registration, feedback);
            const version = ++lookupVersion;
            cnpjButton.disabled = true;
            cnpjButton.textContent = 'Consultando…';
            cnpjResult.hidden = false;
            cnpjResult.removeAttribute('data-state');
            cnpjResult.textContent = 'Consultando a situação cadastral na OpenCNPJ…';
            try {
                const result = await sendJson('backend/controllers/ConsultaCnpjController.php', {
                    documento: documentValue,
                    csrf_token: registration.querySelector('[name="csrf_token"]').value
                });
                if (version !== lookupVersion) return;
                cnpjResult.dataset.state = 'success';
                cnpjResult.textContent = result.message + (result.empresa.razao_social ? ' ' + result.empresa.razao_social : '');
            } catch (error) {
                if (version !== lookupVersion) return;
                cnpjResult.dataset.state = 'error';
                cnpjResult.textContent = error.message;
            } finally {
                if (version === lookupVersion) {
                    cnpjButton.disabled = false;
                    cnpjButton.textContent = 'Consultar CNPJ';
                }
            }
        });

        registration.addEventListener('submit', async event => {
            event.preventDefault();
            clearFeedback(registration, feedback);
            if (!registration.reportValidity()) return;
            const documentValue = normalizeDocument(documentField.value);
            if (!(documentType === 'cpf' ? validCpf(documentValue) : validCnpj(documentValue))) {
                showFeedback(feedback, 'Confira o ' + (documentType === 'cpf' ? 'CPF' : 'CNPJ') + ' informado.', documentField);
                return;
            }
            if (!validPassword(passwordField.value)) {
                showFeedback(feedback, 'Use uma senha com pelo menos 6 caracteres. Se ela for muito longa, escolha uma menor.', passwordField);
                return;
            }
            if (passwordField.value !== confirmationField.value) {
                showFeedback(feedback, 'As senhas precisam ser iguais.', confirmationField);
                return;
            }
            const data = {
                csrf_token: registration.querySelector('[name="csrf_token"]').value,
                tipo: type.value,
                tipo_pessoa: type.value === 'contratante' ? personType.value : 'juridica',
                documento: documentValue,
                nome: nameField.value.trim(),
                email_acesso: document.getElementById('emailAcesso').value.trim(),
                senha: passwordField.value,
                confirmar_senha: confirmationField.value
            };
            submit.disabled = true;
            registration.setAttribute('aria-busy', 'true');
            submit.textContent = documentType === 'cnpj' ? 'Verificando CNPJ e criando conta…' : 'Criando sua conta…';
            try {
                await sendJson('backend/controllers/CadastroEmpresaController.php', data);
                window.location.assign('login.php?status=conta_criada');
            } catch (error) {
                const message = error instanceof TypeError || error instanceof SyntaxError
                    ? 'Não foi possível conectar ao servidor. Seus dados continuam no formulário; tente novamente.'
                    : error.message;
                showFeedback(feedback, message);
            } finally {
                submit.disabled = false;
                submit.textContent = 'Criar conta';
                registration.removeAttribute('aria-busy');
            }
        });
    }

    const login = document.getElementById('formLogin');
    if (login) {
        const documentField = document.getElementById('identificacao');
        const passwordField = document.getElementById('senhaLogin');
        const submit = document.getElementById('submitButtonLogin');
        const feedback = document.getElementById('loginFeedback');
        documentField.addEventListener('input', () => {
            const clean = normalizeDocument(documentField.value);
            documentField.value = formatDocument(documentField.value, /[A-Z]/.test(clean) || clean.length > 11 ? 'cnpj' : 'cpf');
            documentField.removeAttribute('aria-invalid');
        });
        login.addEventListener('submit', async event => {
            event.preventDefault();
            clearFeedback(login, feedback);
            if (!login.reportValidity()) return;
            const documentValue = normalizeDocument(documentField.value);
            if (!/^\d{11}$/.test(documentValue) && !/^[A-Z0-9]{12}\d{2}$/.test(documentValue)) {
                showFeedback(feedback, 'Informe o CPF ou CNPJ completo usado no cadastro.', documentField);
                return;
            }
            if (!validPassword(passwordField.value)) {
                showFeedback(feedback, 'Confira sua senha. Ela precisa ter pelo menos 6 caracteres.', passwordField);
                return;
            }
            submit.disabled = true;
            login.setAttribute('aria-busy', 'true');
            submit.textContent = 'Entrando…';
            try {
                const result = await sendJson('backend/controllers/AuthController.php', {
                    identificacao: documentValue,
                    senha: passwordField.value,
                    csrf_token: login.querySelector('[name="csrf_token"]').value,
                    prestador: login.querySelector('[name="prestador"]')?.value || ''
                });
                const destination = new URL(result.redirect, window.location.href);
                if (destination.origin !== window.location.origin) throw new Error('Destino de acesso inválido. Atualize a página e tente novamente.');
                window.location.assign(destination.href);
            } catch (error) {
                const message = error instanceof TypeError || error instanceof SyntaxError
                    ? 'Não foi possível conectar ao servidor. Tente novamente em instantes.'
                    : error.message;
                showFeedback(feedback, message);
            } finally {
                submit.disabled = false;
                submit.textContent = 'Entrar';
                login.removeAttribute('aria-busy');
            }
        });
    }
});

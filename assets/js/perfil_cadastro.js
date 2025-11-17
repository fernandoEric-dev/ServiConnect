document.addEventListener('DOMContentLoaded', () => {

    // ====================================================
    // 1. GESTÃO DE FORMAÇÃO ACADÊMICA
    // ====================================================

    const formacaoContainer = document.getElementById('formacao-container');
    const addFormacaoBtn = document.getElementById('addFormacaoBtn');
    let formacaoCount = formacaoContainer ? formacaoContainer.children.length : 0; 

    const createFormacaoBlock = (count) => {
        const newIndex = count + 1;
        const div = document.createElement('div');
        div.classList.add('bloco-formacao');
        div.innerHTML = `
            <hr>
            <label for="instituicao_${newIndex}">Instituição de Ensino</label>
            <input type="text" id="instituicao_${newIndex}" name="instituicao[]" placeholder="Nome da Escola/Universidade" required>
            <label for="curso_${newIndex}">Curso / Nível</label>
            <input type="text" id="curso_${newIndex}" name="curso[]" placeholder="Ex: Graduação em Administração" required>
            <label for="periodo_formacao_${newIndex}">Período (Início e Fim)</label>
            <input type="text" id="periodo_formacao_${newIndex}" name="periodo_formacao[]" placeholder="Ex: 2018 - 2022 ou Em curso" required>
            <button type="button" class="remover-formacao-btn">Remover Formação</button>
        `;
        return div;
    };

    if (addFormacaoBtn) {
        addFormacaoBtn.addEventListener('click', () => {
            formacaoCount++;
            const newBlock = createFormacaoBlock(formacaoCount);
            formacaoContainer.appendChild(newBlock);
        });
    }

    if (formacaoContainer) {
        formacaoContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('remover-formacao-btn')) {
                if (formacaoContainer.children.length > 1) { 
                    e.target.closest('.bloco-formacao').remove();
                    formacaoCount--;
                } else {
                    alert('Você deve ter pelo menos uma formação acadêmica.');
                }
            }
        });
    }


    // ====================================================
    // 2. GESTÃO DE OPÇÃO "SEM EXPERIÊNCIA" E ESTADO DOS INPUTS
    // ====================================================

    const semExperienciaCheckbox = document.getElementById('semExperienciaCheckbox');
    const experienciaContent = document.getElementById('experienciaContent');
    
    // Função para alternar a visibilidade e o atributo 'required'
    const toggleExperiencia = () => {
        // Seleciona todos os inputs e textareas DENTRO do container de experiência
        const experienciaInputs = experienciaContent ? experienciaContent.querySelectorAll('input, textarea') : [];
        const isChecked = semExperienciaCheckbox.checked;
        
        // Altera a visibilidade do bloco de conteúdo
        if (isChecked) {
            experienciaContent.style.display = 'none';
        } else {
            experienciaContent.style.display = 'block';
        }

        // Define/Remove o atributo 'required' nos inputs
        experienciaInputs.forEach(input => {
            if (isChecked) {
                input.removeAttribute('required');
            } else {
                input.setAttribute('required', 'required');
            }
        });
    };

    if (semExperienciaCheckbox && experienciaContent) {
        // Adiciona o listener e define o estado inicial
        semExperienciaCheckbox.addEventListener('change', toggleExperiencia);
        toggleExperiencia(); 
    }
    
    
    // ====================================================
    // 3. GESTÃO DE EXPERIÊNCIA PROFISSIONAL (ADICIONAR/REMOVER)
    // ====================================================

    const experienciaContainer = document.getElementById('experiencia-container');
    const addExperienciaBtn = document.getElementById('addExperienciaBtn');
    let experienciaCount = experienciaContainer ? experienciaContainer.children.length : 0;

    const createExperienciaBlock = (count) => {
        const newIndex = count + 1;
        const div = document.createElement('div');
        div.classList.add('bloco-experiencia');
        div.innerHTML = `
            <hr>
            <label for="empresa_${newIndex}">Empresa</label>
            <input type="text" id="empresa_${newIndex}" name="empresa[]" placeholder="Nome da empresa" required>
            <label for="cargo_exp_${newIndex}">Cargo Ocupado</label>
            <input type="text" id="cargo_exp_${newIndex}" name="cargo_exp[]" placeholder="Ex: Agente de Portaria" required>
            <label for="desc_atividades_${newIndex}">Principais Atividades (Resumo)</label>
            <textarea id="desc_atividades_${newIndex}" name="atividades[]" rows="3" placeholder="Descreva suas responsabilidades e conquistas." required></textarea>
            <label for="periodo_exp_${newIndex}">Período</label>
            <input type="text" id="periodo_exp_${newIndex}" name="periodo_exp[]" placeholder="Ex: Jan/2021 - Dez/2023 ou Atual" required>
            <button type="button" class="remover-experiencia-btn">Remover Experiência</button>
        `;
        return div;
    };

    if (addExperienciaBtn) {
        addExperienciaBtn.addEventListener('click', () => {
            experienciaCount++;
            const newBlock = createExperienciaBlock(experienciaCount);
            experienciaContainer.appendChild(newBlock);
            
            // Se o checkbox "Sem Experiência" estiver marcado, remove o 'required' do novo bloco
            if (semExperienciaCheckbox && semExperienciaCheckbox.checked) {
                newBlock.querySelectorAll('input, textarea').forEach(input => input.removeAttribute('required'));
            }
        });
    }

    // Listener para remover blocos de experiência
    if (experienciaContainer) {
        experienciaContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('remover-experiencia-btn')) {
                if (experienciaContainer.children.length > 1) {
                    e.target.closest('.bloco-experiencia').remove();
                    experienciaCount--;
                } else {
                    alert('Você deve ter pelo menos uma experiência profissional.');
                }
            }
        });
    }
});
<?php

// dashboard.php



session_start();



// ⚠️ SEGURANÇA: Garante que o usuário está logado

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['contratante', 'terceirizada', 'admin'])) {

    header('Location: login.php');

    exit;

}



// Lógica de Redirecionamento ADMIN (tira o admin do dashboard principal)

if ($_SESSION['user_role'] === 'admin') {

    header('Location: admin.php');

    exit;

}



// ==========================================================

// 🚨 LÓGICA DE BUSCA DE DADOS REAIS NO BANCO (READ)

// ==========================================================

require_once 'backend/conexao.php';

require_once 'backend/models/UsuarioModel.php';



$user_id = $_SESSION['user_id'];

$cnpj_logado = $_SESSION['user_cnpj'];



$usuarioModel = new UsuarioModel($pdo);



// 1. Busca dados do perfil do usuário logado (Contratante ou Terceirizada)

$stmt = $pdo->prepare("

    SELECT

        u.email, e.*

    FROM usuarios u

    JOIN empresas e ON u.id = e.usuario_id

    WHERE u.id = ?

");

$stmt->execute([$user_id]);

$dados_bd = $stmt->fetch(PDO::FETCH_ASSOC);



// Mapeamento final dos dados para o formulário

if ($dados_bd) {

    $dados_perfil = [

        'nome' => htmlspecialchars($dados_bd['nome_empresa'] ?? 'Empresa'),

        'email' => htmlspecialchars($dados_bd['email']),

        'descricao' => htmlspecialchars($dados_bd['descricao_servicos'] ?? ''),

        'foto_path' => htmlspecialchars($dados_bd['foto_path'] ?? 'img/default_avatar.png'),

        'num_funcionarios' => htmlspecialchars($dados_bd['num_funcionarios'] ?? 0),

        'area_atuacao' => htmlspecialchars($dados_bd['area_atuacao'] ?? $dados_bd['tipo_empresa']),

        'regiao' => htmlspecialchars($dados_bd['regioes_atendidas'] ?? ''),

        'horario' => htmlspecialchars($dados_bd['horario_funcionamento'] ?? ''),

        'cep' => htmlspecialchars($dados_bd['cep'] ?? ''),

        'logradouro' => htmlspecialchars($dados_bd['logradouro'] ?? ''),

        'numero' => htmlspecialchars($dados_bd['numero'] ?? ''),

        'complemento' => htmlspecialchars($dados_bd['complemento'] ?? ''),

        'bairro' => htmlspecialchars($dados_bd['bairro'] ?? ''),

        'cidade' => htmlspecialchars($dados_bd['cidade'] ?? ''),

        'estado' => htmlspecialchars($dados_bd['estado'] ?? ''),

    ];

} else {

    // Fallback de erro

    $dados_perfil = ['nome' => 'Erro ao Carregar', 'email' => 'Erro', 'descricao' => ''];

}



// 2. Busca lista de Terceirizadas se o usuário for Contratante (Para o FEED)



// dashboard.php (Substitua as linhas que buscam as terceirizadas pelo bloco abaixo)



// 2. Busca lista de Terceirizadas se o usuário for Contratante (Para o FEED)

// dashboard.php (Substitua o bloco de busca de Terceirizadas a partir da linha ~65)



// 2. Busca lista de Terceirizadas se o usuário for Contratante (Para o FEED)

$terceirizadas = [];

if ($_SESSION['user_role'] === 'contratante') {

   

    $stmtTerceirizadas = $pdo->prepare("

        SELECT

            u.id as usuario_id,

            e.nome AS nome_empresa,                 -- Nome Real: 'nome'. Alias para o HTML: 'nome_empresa'

            e.descricao AS descricao_servicos,       -- Nome Real: 'descricao'. Alias para o HTML: 'descricao_servicos'

            e.regiao AS regioes_atendidas           -- Nome Real: 'regiao'. Alias para o HTML: 'regioes_atendidas'

            -- A coluna 'foto_path' não foi listada na estrutura do seu BD.

            -- Removemos da busca para evitar o erro Fatal. O HTML usará a imagem padrão.

        FROM usuarios u

        JOIN empresas e ON u.id = e.usuario_id

        WHERE u.tipo_conta = 'empresa' AND e.tipo_empresa = 'terceirizada'

    ");

   

    $stmtTerceirizadas->execute();

    $terceirizadas = $stmtTerceirizadas->fetchAll(PDO::FETCH_ASSOC);

}

// ==========================================================

// ==========================================================

// ==========================================================

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ServiConnect | Dashboard</title>

    <link rel="stylesheet" href="css/style.css">

    <link rel="stylesheet" href="css/dashboard.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

   

</head>

<body>

    <div class="dashboard-header">

        <div class="header-container">

            <h1 class="logo-title">ServiConnect</h1>

           

            <nav class="main-nav">

                <a href="#feed" id="feedLink" class="nav-item nav-active"><i class="fa-solid fa-compass"></i> Feed</a>

                <a href="#perfil" id="perfilLink" class="nav-item"><i class="fa-solid fa-user-circle"></i> Meu Perfil</a>

            </nav>

           

            <div class="user-control">

                <span class="user-display">Bem-vindo, <?php echo htmlspecialchars($dados_perfil['nome']); ?></span>

                <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>

            </div>

        </div>

    </div>



    <main class="main-content-dashboard">

       

        <section id="feed" class="content-section active-section">

            <?php if ($_SESSION['user_role'] === 'contratante'): ?>

                <h2 class="section-title"><i class="fa-solid fa-magnifying-glass"></i> Encontre o Serviço Perfeito</h2>

               

                <div class="search-module widget-card">

                    <p class="search-tip">Empresas disponíveis para contratação:</p>

                   

                    <div class="terceirizadas-list">

                        <?php if (empty($terceirizadas)): ?>

                            <p>Nenhuma empresa terceirizada encontrada no momento.</p>

                        <?php else: ?>

                            <?php foreach ($terceirizadas as $terceirizada): ?>

                                <div class="terceirizada-card widget-card">

                                    <div class="profile-info">

                                        <img src="<?php echo htmlspecialchars($terceirizada['foto_path'] ?? 'img/default_avatar.png'); ?>" alt="Logo" class="terceirizada-logo" style="width: 60px; height: 60px; border-radius: 50%;">

                                        <div class="text-info">

                                            <h4><?php echo htmlspecialchars($terceirizada['nome_empresa']); ?></h4>

                                            <p class="service-area"><i class="fa-solid fa-location-dot"></i> **Região:** <?php echo htmlspecialchars($terceirizada['regioes_atendidas']); ?></p>

                                            <p class="description-short"><?php echo substr(htmlspecialchars($terceirizada['descricao_servicos']), 0, 100); ?>...</p>

                                        </div>

                                    </div>

                                    <a href="solicitacao_orcamento.php?terceirizada_id=<?php echo $terceirizada['usuario_id']; ?>"

                                       class="btn-primary request-btn">

                                        <i class="fa-solid fa-comments-dollar"></i> Solicitar Orçamento

                                    </a>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                </div>



           <?php elseif ($_SESSION['user_role'] === 'terceirizada'): ?>
            <?php
            // 🔑 LÓGICA DE BUSCA DE SOLICITAÇÕES PENDENTES PARA A TERCEIRIZADA
            $terceirizada_id = $_SESSION['user_id'];
            
            $stmtSolicitacoes = $pdo->prepare("
                SELECT 
                    s.id AS solicitacao_id, 
                    s.data_solicitacao,
                    s.descricao_servico, 
                    s.localizacao_servico, 
                    s.area_servico_solicitada,
                    s.numero_funcionarios,
                    e.nome AS nome_contratante,
                    u.email AS email_contratante
                FROM solicitacoes s
                -- Junta com empresas e usuarios para pegar o nome e email do Contratante
                JOIN empresas e ON s.contratante_id = e.usuario_id
                JOIN usuarios u ON s.contratante_id = u.id
                WHERE s.terceirizada_id = :terceirizada_id AND s.status = 'aberta'
                ORDER BY s.data_solicitacao DESC
            ");
            
            $stmtSolicitacoes->execute([':terceirizada_id' => $terceirizada_id]);
            $solicitacoes_recebidas = $stmtSolicitacoes->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <h2 class="section-title"><i class="fa-solid fa-envelope-open-text"></i> Solicitações de Orçamento Recebidas</h2>
            
            <?php if (empty($solicitacoes_recebidas)): ?>
                <div class="widget-card requests-list">
                    <p>🎉 Nenhuma nova solicitação de orçamento pendente no momento.</p>
                    <p class="status-message">Você pode configurar seu perfil e localização clicando na aba 'Meu Perfil'.</p>
                </div>
            <?php else: ?>
                <div class="requests-list">
                    <?php foreach ($solicitacoes_recebidas as $solicitacao): ?>
                        <div class="solicitacao-card widget-card">
                            <h3>Pedido de <?php echo htmlspecialchars($solicitacao['nome_contratante']); ?></h3>
                            <p><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($solicitacao['data_solicitacao'])); ?></p>
                            
                            <div style="margin-top: 10px;">
                                <p><strong>Serviço:</strong> <?php echo htmlspecialchars($solicitacao['area_servico_solicitada']); ?> (<?php echo htmlspecialchars($solicitacao['numero_funcionarios']); ?> Funcionários)</p>
                                <p><strong>Local:</strong> <?php echo htmlspecialchars($solicitacao['localizacao_servico']); ?></p>
                                <p class="description-short">
                                    **Detalhes:** <?php echo substr(htmlspecialchars($solicitacao['descricao_servico']), 0, 150); ?>...
                                </p>
                            </div>
                            
                            <a href="responder_orcamento.php?id=<?php echo $solicitacao['solicitacao_id']; ?>" 
                               class="btn-primary request-btn action-respond">
                                <i class="fa-solid fa-reply"></i> Ver Detalhes e Responder
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        </section>

       

        <section id="perfil" class="content-section" style="display: none;">

            <h2 class="section-title"><i class="fa-solid fa-id-card-clip"></i> Gerenciar Perfil de Serviços</h2>

           

            <form action="backend/controllers/PerfilTerceirizadaController.php" method="post" id="formEdicaoPerfil" enctype="multipart/form-data" class="widget-card">

                <input type="hidden" name="acao" value="atualizar_perfil">

                <input type="hidden" name="foto_atual" value="<?php echo htmlspecialchars($dados_perfil['foto_path']); ?>">





                <h3 style="margin-top: 0;">Dados de Acesso (Não Editáveis Aqui)</h3>

                <div style="background-color: #f0f0f0; padding: 15px; border-radius: 5px; margin-bottom: 20px;">

                    <p><strong>Nome da Empresa:</strong> <?php echo htmlspecialchars($dados_perfil['nome']); ?></p>

                    <p><strong>CNPJ de Acesso:</strong> <?php echo htmlspecialchars($cnpj_logado); ?></p>

                    <p><strong>Email de Acesso:</strong> <?php echo htmlspecialchars($dados_perfil['email']); ?></p>

                </div>

               

                <h3 style="margin-top: 30px;">1. Imagem e Descrição (Perfil LinkedIn-style)</h3>

               

                <div class="input-group">

                    <label for="foto_perfil">Foto / Logotipo da Empresa</label>

                    <img src="<?php echo htmlspecialchars($dados_perfil['foto_path']); ?>" alt="Foto Atual" style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%; margin-bottom: 10px;">

                   

                    <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*">

                </div>



                <div>

                    <label for="texto_perfil">Texto de Perfil / Bio (Como um LinkedIn)</label>

                    <textarea id="texto_perfil" name="texto_perfil" placeholder="Escreva um resumo profissional sobre a sua empresa, seus valores e diferenciais..." rows="7"><?php echo htmlspecialchars($dados_perfil['descricao']); ?></textarea>

                </div>



                <h3 style="margin-top: 40px;">2. Detalhes Operacionais</h3>

               

                <div>

                    <label for="area_atuacao">Serviço Principal / Área de Atuação</label>

                    <input type="text" id="area_atuacao" name="area_atuacao" value="<?php echo htmlspecialchars($dados_perfil['area_atuacao']); ?>" placeholder="Ex: Limpeza, Serviços Gerais, TI, etc.">

                </div>



                <div>

                    <label for="num_funcionarios">Número Estimado de Funcionários</label>

                    <input type="number" id="num_funcionarios" name="num_funcionarios" value="<?php echo htmlspecialchars($dados_perfil['num_funcionarios']); ?>" placeholder="Ex: 50" min="1">

                </div>



                <div>

                    <label for="regiao">Regiões Atendidas</label>

                    <input type="text" id="regiao" name="regiao" value="<?php echo htmlspecialchars($dados_perfil['regiao']); ?>" placeholder="Ex.: São Paulo - Capital, ABC, Campinas">

                </div>



                <div>

                    <label for="horario">Horário de Funcionamento</label>

                    <input type="text" id="horario" name="horario" value="<?php echo htmlspecialchars($dados_perfil['horario']); ?>" placeholder="Ex.: Seg a Sex 08:00 - 18:00">

                </div>





                <h3 style="margin-top: 40px;">3. Localização (Endereço da Sede)</h3>

                <p class="small-text">Sua localização é usada para que contratantes próximos possam te encontrar.</p>

               

                <div><label for="cep_empresa">CEP</label><input type="text" name="cep_empresa" id="cepEmpresa" value="<?php echo htmlspecialchars($dados_perfil['cep']); ?>" placeholder="00000-000" required maxlength="9"></div>

                <div><label for="logradouro_empresa">Rua/Avenida</label><input type="text" name="logradouro_empresa" id="logradouroEmpresa" value="<?php echo htmlspecialchars($dados_perfil['logradouro']); ?>" placeholder="Será preenchido automaticamente" required></div>

               

                <div style="display: flex; gap: 10px;">

                    <div style="flex: 1;"><label for="numero_empresa">Número</label><input type="text" name="numero_empresa" id="numeroEmpresa" value="<?php echo htmlspecialchars($dados_perfil['numero']); ?>" placeholder="Nº" required></div>

                    <div style="flex: 2;"><label for="complemento_empresa">Complemento (opcional)</label><input type="text" name="complemento_empresa" id="complementoEmpresa" value="<?php echo htmlspecialchars($dados_perfil['complemento']); ?>" placeholder="Sala, Andar, etc."></div>

                </div>



                <div><label for="bairro_empresa">Bairro</label><input type="text" name="bairro_empresa" id="bairroEmpresa" value="<?php echo htmlspecialchars($dados_perfil['bairro']); ?>" placeholder="Será preenchido automaticamente" required></div>

                <div style="display: flex; gap: 10px;">

                    <div style="flex: 3;"><label for="cidade_empresa">Cidade</label><input type="text" name="cidade_empresa" id="cidadeEmpresa" value="<?php echo htmlspecialchars($dados_perfil['cidade']); ?>" placeholder="Será preenchido automaticamente" required></div>

                    <div style="flex: 1;"><label for="estado_empresa">Estado (UF)</label><input type="text" name="estado_empresa" id="estadoEmpresa" value="<?php echo htmlspecialchars($dados_perfil['estado']); ?>" placeholder="UF" required maxlength="2"></div>

                </div>





                <button type="submit" class="btn btn-header" style="width: 100%; margin-top: 30px;">

                    <i class="fa-solid fa-cloud-arrow-up"></i> Salvar e Publicar Perfil

                </button>

            </form>

        </section>



    </main>

   

    <script>

        // Lógica JS para alternar as abas (Feed/Perfil)

        document.addEventListener('DOMContentLoaded', () => {

            const feedLink = document.getElementById('feedLink');

            const perfilLink = document.getElementById('perfilLink');

            const feedSection = document.getElementById('feed');

            const perfilSection = document.getElementById('perfil');



            // Função para ler o hash da URL e definir a aba ativa ao carregar

            function loadActiveTab() {

                const hash = window.location.hash;

                if (hash === '#perfil') {

                    switchView('perfil');

                } else {

                    switchView('feed');

                }

            }



            function switchView(view) {

                if (view === 'feed') {

                    feedSection.style.display = 'block';

                    perfilSection.style.display = 'none';

                    feedLink.classList.add('nav-active');

                    perfilLink.classList.remove('nav-active');

                    history.pushState(null, null, '#feed'); // Atualiza a URL sem recarregar

                } else if (view === 'perfil') {

                    feedSection.style.display = 'none';

                    perfilSection.style.display = 'block';

                    perfilLink.classList.add('nav-active');

                    feedLink.classList.remove('nav-active');

                    history.pushState(null, null, '#perfil'); // Atualiza a URL sem recarregar

                }

            }



            feedLink.addEventListener('click', (e) => { e.preventDefault(); switchView('feed'); });

            perfilLink.addEventListener('click', (e) => { e.preventDefault(); switchView('perfil'); });

           

            // Carrega a aba correta ao iniciar

            loadActiveTab();

        });

    </script>

</body>

</html>


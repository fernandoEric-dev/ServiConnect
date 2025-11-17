<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ServiConnect: Conectando empresas a prestadores de serviços terceirizados de forma prática e segura.">
    <title>ServiConnect - Conectando Empresas e Serviços</title>
    
    <!-- CSS principal -->
    <link rel="stylesheet" href="assets/css/index.css">

    <!-- Fonte Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <!-- HEADER -->
    <header>
        <div class="top-bar">
            <div class="container">
                <!-- LOGO -->
                <img src="assets/img/logo.jpg" alt="Logo da ServiConnect" class="logo">
                
                <!-- BARRA DE PESQUISA -->
                <div class="search-area">
                    <input type="text" placeholder="Buscar por serviços (ex: limpeza, segurança)...">
                    <button type="submit" aria-label="Buscar">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>

                <!-- BOTÕES DE AÇÃO -->
                <div class="user-actions">
                    <a href="views/login.php" class="login-link">Entrar</a>
                    <a href="cadastro_empregrado.html" class="btn btn-secondary">Sou Profissional/Buscar Vagas</a>
                    <a href="cadastro_empresa.html" class="btn btn-header">Cadastrar Empresa</a>
                </div>

                <!-- BOTÃO MENU MOBILE -->
                <button class="mobile-nav-toggle" aria-controls="primary-navigation" aria-expanded="false">
                    <span class="sr-only">Abrir Menu</span>
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>

        <!-- MENU -->
        <div class="bottom-bar" id="primary-navigation">
            <div class="container">
                 <nav>
                    <ul>
                        <li><a href="#sobre">Sobre Nós</a></li>
                        <li><a href="#funcionalidades">Funcionalidades</a></li>
                        <li><a href="#como-funciona">Como Funciona</a></li>
                        <li><a href="#contato">Contato</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <!-- HERO -->
    <section class="hero">
        <div class="container">
            <h1>A Conexão Certa para Sua Empresa</h1>
            <p class="frase">Encontre os melhores serviços terceirizados, como limpeza, segurança e muitas outras opções, reunidos em um só lugar.</p>
        </div>
    </section>

    <!-- SOBRE -->
    <section id="sobre">
        <div class="container">
          <h2>Sobre a Servi<span class="highlight">Connect</span></h2>
            <div class="sobre-layout">
                <div class="sobre-texto">
                    <p>Somos a plataforma que conecta empresários de empresas terceirizadas a empresas que necessitam desses serviços. Seja limpeza, segurança ou manutenção, a ServiConnect é o ambiente onde você encontra e contrata prestadoras especializadas de forma prática e segura.</p>
                    <p>Nossa missão é otimizar o processo de contratação, oferecendo um ambiente seguro para negociações, contratos e pagamentos, com total transparência e avaliações que constroem a confiança.</p>
                </div>
                <div class="sobre-imagem">
                    <img src="https://images.pexels.com/photos/3184357/pexels-photo-3184357.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Equipe de negócios colaborando em uma mesa de reunião">
                </div>
            </div>
        </div>
    </section>

    <!-- FUNCIONALIDADES -->
    <section id="funcionalidades">
        <div class="container">
            <h2>Nossas Funcionalidades</h2>
            <div class="funcionalidades-grid">
                <div class="card">
                    <h3>Perfis Empresariais</h3>
                    <p>Crie um perfil detalhado para sua empresa, destacando seus serviços e qualificações.</p>
                </div>
                <div class="card">
                    <h3>Publicação de Demandas</h3>
                    <p>Empresas publicam suas necessidades de serviço de forma clara e objetiva.</p>
                </div>
                <div class="card">
                    <h3>Envio de Propostas</h3>
                    <p>Prestadoras enviam propostas competitivas diretamente pela plataforma.</p>
                </div>
                <div class="card">
                    <h3>Intermediação Segura</h3>
                    <p>Gerenciamos contratos e pagamentos para garantir a segurança de todos.</p>
                </div>
                <div class="card">
                    <h3>Sistema de Avaliações</h3>
                    <p>Construa uma reputação de confiança com avaliações após cada serviço.</p>
                </div>
                 <div class="card">
                    <h3>Suporte Dedicado</h3>
                    <p>Nossa equipe está pronta para ajudar em todas as etapas do processo.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- COMO FUNCIONA -->
    <section id="como-funciona">
        <div class="container">
            <h2>Como Funciona</h2>
            <div class="como-funciona-layout">
                <div class="coluna">
                    <h3>Para Empresas Contratantes</h3>
                    <ol>
                        <li>Cadastre sua empresa e publique sua necessidade.</li>
                        <li>Receba e analise propostas de fornecedores qualificados.</li>
                        <li>Escolha o melhor parceiro e gerencie tudo pela plataforma.</li>
                    </ol>
                </div>
                <div class="coluna">
                    <h3>Para Prestadores de Serviço</h3>
                    <ol>
                        <li>Crie o perfil da sua empresa e detalhe seus serviços.</li>
                        <li>Encontre oportunidades e envie propostas competitivas.</li>
                        <li>Preste o serviço e receba o pagamento de forma segura.</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer id="contato">
        <div class="container">
            <div class="social-icons">
                <a href="#" aria-label="Página da ServiConnect no Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                <a href="#" aria-label="Página da ServiConnect no Instagram"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" aria-label="Página da ServiConnect no LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
            </div>
            <p>Entre em contato conosco: <a href="mailto:contato@serviconnect.com">contato@serviconnect.com</a></p>
            <p>&copy; 2025 ServiConnect. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- SCRIPT -->
    <script src="js/script.js"></script>

</body>
</html>

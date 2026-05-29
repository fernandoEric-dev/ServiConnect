<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ServiConnect: Conectando empresas a prestadores de serviços terceirizados de forma prática e segura.">
    <title>ServiConnect - Conectando Empresas e Serviços </title>
    
    <link rel="stylesheet" href="css/style.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        .top-bar .container {
            justify-content: center !important;
            gap: 50px !important; /* Espaçamento entre o logo, pesquisa e botões */
        }
        
        /* Ajuste para telas menores não quebrarem o layout centralizado */
        @media (max-width: 768px) {
            .top-bar .container {
                flex-direction: column;
                gap: 20px !important;
            }
        }
    </style>
</head>
<body>

    <header>
        <div class="top-bar">
            <div class="container">
                <img src="foto/logo.jpg" alt="Logo da ServiConnect" class="logo">
                
                <div class="search-area">
                    <input type="text" placeholder="Buscar por serviços (ex: limpeza, segurança)...">
                    <button type="submit" aria-label="Buscar">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>

                <div class="user-actions">
                    <a href="login.php" class="login-link">Entrar</a>
                    <a href="cadastro_empresa.php" class="btn btn-header">Cadastrar Empresa</a>
                </div>
                
                </div>
        </div>

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

    <section class="hero">
        <div class="container">
            <h1>A Conexão Certa para Sua Empresa</h1>
            <p class="frase">Encontre os melhores serviços terceirizados, como limpeza, segurança e muitas outras opções, reunidos em um só lugar.</p>
        </div>
    </section>

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

    <script src="js/script.js"></script>

</body>
</html>
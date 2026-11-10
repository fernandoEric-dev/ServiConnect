<?php require_once __DIR__ . '/backend/app.php'; $activeNav=''; ?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Encontre MEIs e empresas terceirizadas, compare serviços e solicite orçamentos na ServiConnect."><title>ServiConnect — Serviços para sua casa ou empresa</title><link rel="stylesheet" href="css/app.css?v=4"><link rel="stylesheet" href="css/inicio.css?v=5"></head><body class="home-page">
<?php require __DIR__ . '/backend/views/app-header.php'; ?>
<main><section class="home-hero"><div class="home-hero-inner"><div><p class="eyebrow">QUEM PRECISA, ENCONTRA QUEM FAZ</p><h1>Serviços para sua casa ou empresa.</h1><p class="hero-description">Encontre MEIs e empresas terceirizadas para jardinagem, limpeza, manutenção e muito mais.</p>
<form action="busca.php" method="get" role="search" aria-label="Buscar serviços" class="hero-search"><div class="field"><label for="homeService">O que você precisa?</label><input type="search" id="homeService" name="q" maxlength="100" placeholder="Ex.: jardinagem ou limpeza"></div><div class="field"><label for="homeLocation">Onde? (opcional)</label><input type="search" id="homeLocation" name="local" maxlength="100" placeholder="Cidade ou região"></div><button type="submit" class="button">Buscar serviços</button></form>
<a href="busca.php" class="hero-all">Explorar todos os prestadores →</a></div>
<div class="hero-guide"><p class="eyebrow">DO PRIMEIRO CONTATO À CONCLUSÃO</p><ol><li><span>01</span><div><strong>Encontre o serviço</strong><p>Selecione por tipo de prestador, serviço e região.</p></div></li><li><span>02</span><div><strong>Confira a proposta</strong><p>Veja o valor, o prazo e tudo o que está incluído.</p></div></li><li><span>03</span><div><strong>Acompanhe cada etapa</strong><p>Converse pelo pedido e mantenha o histórico das combinações.</p></div></li></ol></div></div></section>
<section class="app-container" id="servicos"><div class="page-intro"><p class="eyebrow">PARA DIFERENTES NECESSIDADES</p><h2>Qual serviço você procura?</h2><p>Conheça algumas opções do catálogo de serviços.</p></div><div class="home-categories">
<?php foreach (['jardinagem'=>['Jardim e áreas verdes','Jardinagem, poda e paisagismo.'],'limpeza-residencial'=>['Limpeza e organização','Cuidados para sua casa e sua empresa.'],'eletricista'=>['Reformas e reparos','Eletricidade, hidráulica e manutenção.'],'informatica'=>['Tecnologia','Computadores, redes e suporte de TI.'],'portaria'=>['Empresas e facilities','Portaria, recepção e serviços de apoio.'],'mudancas'=>['Fretes e mudanças','Encontre quem ajuda no seu próximo passo.']] as $service=>[$title,$description]): ?><a class="surface" href="busca.php?servico=<?php echo $service; ?>"><span class="tag"><?php echo order_escape(service_catalog()[$service]); ?></span><h3><?php echo $title; ?></h3><p><?php echo $description; ?></p><span class="category-link">Ver prestadores →</span></a><?php endforeach; ?></div></section>
<section class="home-provider"><div class="app-container home-provider-inner"><div><p class="eyebrow">PARA QUEM OFERECE SERVIÇOS</p><h2>Seu trabalho merece ser encontrado.</h2><p>Crie seu perfil, selecione os serviços que oferece e informe onde atende. Receba solicitações e acompanhe suas propostas em um só lugar.</p></div><a href="cadastro_empresa.php?perfil=mei" class="button">Quero oferecer serviços</a></div></section>
<section class="app-container">
    <div class="page-intro"><h2>Informação clara para combinar melhor</h2></div>
    <div class="home-categories home-highlights">
        <article class="surface">
            <h3>Perfis detalhados</h3>
            <p>Serviços, regiões, formas de atendimento e informações declaradas pelo prestador.</p>
        </article>
        <article class="surface">
            <h3>Localização no mapa</h3>
            <p>Confira a localização comercial publicada pelas empresas. O endereço residencial dos MEIs permanece privado.</p>
        </article>
        <article class="surface">
            <h3>Orçamentos com histórico</h3>
            <p>Propostas, revisões, mensagens e confirmação de conclusão ficam no pedido.</p>
        </article>
    </div>
</section></main>
<footer class="app-footer">ServiConnect · Conectando pessoas e empresas a prestadores de serviços.</footer></body></html>

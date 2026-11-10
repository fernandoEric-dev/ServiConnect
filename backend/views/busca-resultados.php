<?php if (!$search['filters']['errors']): ?>
<?php if (!$search['items']): ?>
    <div class="search-empty"><h3>Nenhum resultado para esta busca</h3><p>Tente um serviço mais amplo, outra cidade ou remova algum filtro. A busca considera os serviços e as regiões informados nos perfis ativos.</p><a class="search-button" href="<?php echo search_escape($searchPath . $searchAnchor); ?>">Ver todos os prestadores</a></div>
<?php else: ?>
    <div class="search-results <?php echo $searchMap ? 'search-results-map' : ''; ?>" aria-label="Resultados da busca" aria-describedby="resumoBusca">
    <?php foreach ($search['items'] as $index=>$provider): ?>
        <article class="search-card" data-provider-id="<?php echo (int)$provider['usuario_id']; ?>">
            <div class="search-card-heading"><img src="<?php echo search_escape($provider['foto_path']); ?>" alt="Foto de <?php echo search_escape($provider['nome_empresa']); ?>" width="64" height="64" loading="lazy"><div><span class="search-type"><?php echo search_escape(account_type_label($provider['tipo_empresa'])); ?></span><h3><?php echo search_escape($provider['nome_empresa']); ?></h3></div></div>
            <p class="search-service"><?php echo search_escape($provider['area_atuacao'] ?: 'Serviços não detalhados no perfil'); ?></p>
            <p class="search-description"><?php echo search_escape(search_excerpt($provider['descricao_servicos']) ?: 'Consulte o perfil para conhecer melhor este prestador.'); ?></p>
            <dl class="search-location"><div><dt>Cidade da sede</dt><dd><?php echo search_escape(trim(($provider['cidade'] ?? '') . (!empty($provider['estado']) ? ' / ' . $provider['estado'] : '')) ?: 'Não informada'); ?></dd></div><div><dt>Atende em</dt><dd><?php echo search_escape($provider['regioes_atendidas'] ?: 'Consulte o prestador'); ?></dd></div></dl>
            <div class="search-card-actions">
                <a class="search-button search-button-secondary" href="<?php echo $searchSignedIn ? 'perfil_empresa.php?id=' : 'login.php?prestador='; ?><?php echo (int)$provider['usuario_id']; ?>"><?php echo $searchSignedIn ? 'Ver perfil' : 'Entrar para ver perfil'; ?></a>
                <?php if ($searchContractor): ?><a class="search-button" href="solicitacao_orcamento.php?terceirizada_id=<?php echo (int)$provider['usuario_id']; ?>">Solicitar orçamento</a><?php endif; ?>
                <?php if ($searchMap && $provider['latitude'] !== null && $provider['longitude'] !== null): ?><button type="button" class="search-map-focus" onclick="focarNoMapa(<?php echo (int)$index; ?>)">Ver no mapa</button><?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
    <?php if (!$searchMap) require __DIR__ . '/busca-paginacao.php'; ?>
<?php endif; ?>
<?php endif; ?>

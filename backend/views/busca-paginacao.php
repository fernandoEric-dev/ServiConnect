<?php if (!$search['filters']['errors'] && $search['pages'] > 1): ?>
<nav class="search-pagination" aria-label="Paginação dos prestadores">
    <?php if ($search['page'] > 1): ?><a rel="prev" href="<?php echo search_escape(search_url($searchPath, $search['filters'], $search['page'] - 1, $searchAnchor)); ?>">Anterior</a><?php endif; ?>
    <?php $lastShown = 0; $pageNumbers = array_unique(array_merge([1, $search['pages']], range(max(1, $search['page'] - 1), min($search['pages'], $search['page'] + 1)))); sort($pageNumbers); foreach ($pageNumbers as $number): ?>
        <?php if ($lastShown && $number > $lastShown + 1): ?><span aria-hidden="true">…</span><?php endif; ?>
        <?php if ($number === $search['page']): ?><span aria-current="page" aria-label="Página <?php echo $number; ?>"><?php echo $number; ?></span><?php else: ?><a aria-label="Página <?php echo $number; ?>" href="<?php echo search_escape(search_url($searchPath, $search['filters'], $number, $searchAnchor)); ?>"><?php echo $number; ?></a><?php endif; ?>
    <?php $lastShown = $number; endforeach; ?>
    <?php if ($search['page'] < $search['pages']): ?><a rel="next" href="<?php echo search_escape(search_url($searchPath, $search['filters'], $search['page'] + 1, $searchAnchor)); ?>">Próxima</a><?php endif; ?>
</nav><p class="search-page-label">Página <?php echo $search['page']; ?> de <?php echo $search['pages']; ?></p>
<?php endif; ?>

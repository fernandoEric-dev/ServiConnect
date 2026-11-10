<?php
// Catálogo de apresentação ao cliente. Não representa enquadramento fiscal/CNAE.
function service_groups(): array {
    return [
        'Casa, jardim e limpeza'=>[
            'jardinagem'=>'Jardinagem', 'poda'=>'Poda de árvores e plantas', 'paisagismo'=>'Paisagismo',
            'limpeza-residencial'=>'Limpeza residencial', 'limpeza-comercial'=>'Limpeza comercial', 'limpeza-pos-obra'=>'Limpeza pós-obra',
            'higienizacao-estofados'=>'Higienização de estofados', 'limpeza-vidros'=>'Limpeza de vidros', 'limpeza-caixa-agua'=>'Limpeza de caixa-d’água',
            'piscinas'=>'Manutenção de piscinas', 'dedetizacao'=>'Controle de pragas e dedetização', 'organizacao'=>'Organização de ambientes',
        ],
        'Reformas e manutenção'=>[
            'eletricista'=>'Instalações e reparos elétricos', 'encanador'=>'Instalações e reparos hidráulicos', 'pedreiro'=>'Alvenaria e pequenos reparos',
            'pintura'=>'Pintura', 'gesso'=>'Gesso e drywall', 'pisos'=>'Instalação de pisos e revestimentos', 'telhados'=>'Reparo de telhados e calhas',
            'impermeabilizacao'=>'Impermeabilização', 'marcenaria'=>'Marcenaria', 'serralheria'=>'Serralheria', 'vidracaria'=>'Vidraçaria',
            'montagem-moveis'=>'Montagem de móveis', 'chaveiro'=>'Chaveiro', 'portoes'=>'Manutenção de portões automáticos',
        ],
        'Equipamentos e tecnologia'=>[
            'ar-condicionado'=>'Instalação e manutenção de ar-condicionado', 'refrigeracao'=>'Refrigeração', 'eletrodomesticos'=>'Conserto de eletrodomésticos',
            'informatica'=>'Manutenção de computadores', 'redes'=>'Redes e Wi-Fi', 'celulares'=>'Reparo de celulares',
            'desenvolvimento-sites'=>'Criação e manutenção de sites', 'sistemas'=>'Desenvolvimento de sistemas', 'suporte-ti'=>'Suporte de TI',
            'cameras'=>'Instalação de câmeras', 'alarmes'=>'Instalação de alarmes', 'energia-solar'=>'Instalação e manutenção de energia solar',
        ],
        'Empresas e facilities'=>[
            'portaria'=>'Portaria e controle de acesso', 'recepcao'=>'Recepção', 'seguranca'=>'Segurança patrimonial',
            'zeladoria'=>'Zeladoria', 'facilities'=>'Gestão de facilities', 'limpeza-industrial'=>'Limpeza industrial',
            'apoio-administrativo'=>'Apoio administrativo', 'contabilidade'=>'Serviços contábeis', 'consultoria'=>'Consultoria empresarial',
            'marketing'=>'Marketing digital', 'design'=>'Design gráfico', 'social-media'=>'Gestão de redes sociais',
        ],
        'Transporte e logística'=>[
            'mudancas'=>'Mudanças', 'fretes'=>'Fretes', 'entregas'=>'Entregas e motofrete', 'montagem-eventos'=>'Montagem de estruturas para eventos',
            'logistica'=>'Apoio logístico', 'transporte-pessoas'=>'Transporte de passageiros',
        ],
        'Eventos e alimentação'=>[
            'buffet'=>'Buffet e alimentação para eventos', 'cozinha'=>'Cozinha e preparo de refeições', 'confeitaria'=>'Confeitaria',
            'garcom'=>'Garçons e atendimento em eventos', 'decoracao-eventos'=>'Decoração de eventos', 'fotografia'=>'Fotografia',
            'video'=>'Filmagem e edição de vídeo', 'som-iluminacao'=>'Som e iluminação', 'producao-eventos'=>'Produção de eventos',
        ],
        'Cuidados e serviços pessoais'=>[
            'cuidador'=>'Cuidados de pessoas', 'pet-sitter'=>'Pet sitter e passeio com animais', 'banho-tosa'=>'Banho e tosa',
            'beleza'=>'Cabelo e beleza', 'estetica'=>'Estética', 'costura'=>'Costura e ajustes', 'lavanderia'=>'Lavanderia e passadoria',
            'aulas'=>'Aulas particulares', 'treinamentos'=>'Treinamentos e capacitação', 'traducao'=>'Tradução e revisão de textos',
        ],
        'Veículos'=>[
            'mecanica'=>'Mecânica automotiva', 'eletrica-auto'=>'Elétrica automotiva', 'lavagem-auto'=>'Lavagem e estética automotiva',
            'borracharia'=>'Borracharia', 'guincho'=>'Guincho e assistência veicular',
        ],
    ];
}
function service_catalog(): array {
    $all = [];
    foreach (service_groups() as $items) $all += $items;
    return $all;
}
function service_aliases(): array {
    return ['jardinagem'=>'jardim jardins grama gramado','poda'=>'arvores plantas','eletricista'=>'eletrica eletrico eletricidade','encanador'=>'hidraulica vazamento cano','pedreiro'=>'pedreiro obra reforma alvenaria','informatica'=>'computador notebook computador ti','ar-condicionado'=>'ar condicionado climatizacao','limpeza-residencial'=>'faxina diarista casa','limpeza-comercial'=>'faxina escritorio empresa','higienizacao-estofados'=>'sofa colchao tapete','cameras'=>'camera cftv monitoramento','manutencao'=>'reparos','montagem-moveis'=>'montador armario guarda roupa'];
}
function service_matching_ids(string $term): array {
    $term = catalog_fold($term);
    if (mb_strlen($term)<3) return [];
    $ids=[];$aliases=service_aliases();
    foreach(service_catalog() as $id=>$label) if(strpos(catalog_fold($id.' '.$label.' '.($aliases[$id]??'')),$term)!==false)$ids[]=$id;
    return $ids;
}
function payment_options(): array {
    return ['pix'=>'Pix','dinheiro'=>'Dinheiro','credito'=>'Cartão de crédito','debito'=>'Cartão de débito','boleto'=>'Boleto','transferencia'=>'Transferência'];
}
function attendance_options(): array {
    return ['domicilio'=>'No local do cliente','sede'=>'Na sede da empresa','remoto'=>'Remoto'];
}
function weekday_options(): array {
    return ['seg'=>'Segunda','ter'=>'Terça','qua'=>'Quarta','qui'=>'Quinta','sex'=>'Sexta','sab'=>'Sábado','dom'=>'Domingo'];
}
function selected_options($json): array {
    $decoded = is_string($json) ? json_decode($json, true) : null;
    return is_array($decoded) ? $decoded : [];
}
function provider_services(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare('SELECT servico_id FROM empresa_servicos WHERE usuario_id=? ORDER BY nome');
    $stmt->execute([$id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
function catalog_fold(string $text): string {
    return strtr(mb_strtolower($text), ['á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a','é'=>'e','ê'=>'e','è'=>'e','í'=>'i','ì'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ú'=>'u','ü'=>'u','ç'=>'c']);
}

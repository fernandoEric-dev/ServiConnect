# Orçamentos, histórico, perfis, visual e mapas

## Entrega

- Pedido de orçamento com serviço selecionado, descrição, frequência, data desejada e local. O formulário aproveita o endereço do próprio contratante; a localização do atendimento fica restrita aos participantes.
- Proteção contra reenvio do mesmo formulário, autoria pela sessão e atualização de situação dentro de transação.
- Proposta com valor total, prazo, pagamento, itens incluídos/excluídos, observações e validade. Cada revisão preserva as condições anteriores no histórico.
- Contratante aceita ou recusa; prestador informa início e finalização; contratante confirma a conclusão ou solicita ajustes. Cancelamentos exigem motivo.
- Histórico compartilhado, mensagens por pedido, paginação e filtros. Acesso restrito ao contratante e ao prestador daquele pedido. O controle de versão impede que um formulário antigo sobrescreva uma atualização.
- Seleção de até 20 serviços de um catálogo de 80 opções, busca por nomes populares, municípios do IBGE, horários, dias, modalidades de atendimento, pagamentos, experiência, site e nota fiscal declarada.
- Nova identidade visual nas páginas principais, painéis, perfis e orçamentos. Formulários funcionam por envio normal; JavaScript acrescenta filtros de seleção, prévia de foto, consulta de CEP e mapas.

## Mapa e localização

O Leaflet 1.9.4 está incluído em `vendor/leaflet`, com licença e imagens. A biblioteca não depende de um CDN para carregar. O mapa usa tiles HTTPS do OpenStreetMap, com atribuição visível, Referer de origem e cache normal do navegador. Não há download em massa ou pré-carregamento de mapas.

A empresa marca o ponto, arrasta o marcador ou informa coordenadas. O botão de localização do dispositivo só chama a geolocalização após clique do próprio usuário. A publicação exige autorização no perfil. Alterar o endereço invalida um ponto anterior até que seja confirmado novamente. A cidade pode ser centralizada com a malha simplificada do IBGE; não se inventa um ponto de empresa a partir do centro da cidade.

MEIs não publicam coordenadas ou endereço residencial. Aparecem por serviços, cidade e região de atendimento. Dados antigos de localização comercial já exibidos são preservados para terceirizadas; o titular pode remover a publicação no editor.

O provedor de tiles pode ser substituído por `map_tile_url` em `config.local.php` ou `MAP_TILE_URL`, utilizando HTTPS. Respeitar as atribuições e os termos do novo provedor.

Referências: [Leaflet](https://leafletjs.com/reference.html), [política de tiles do OpenStreetMap](https://operations.osmfoundation.org/policies/tiles/), [localidades do IBGE](https://servicodados.ibge.gov.br/api/docs/localidades), [malhas geográficas do IBGE](https://servicodados.ibge.gov.br/api/docs/malhas?versao=3).

## Banco de produção

Migração: `database/infinityfree/ATUALIZAR_ETAPAS_4_E_5.sql`. Adiciona campos, `empresa_servicos` e `solicitacoes_eventos`, preservando os registros anteriores e os estados antigos, incluindo vazio e NULL. A atualização é reaplicável. Os cinco pedidos anteriores recebem uma indicação de que o histórico passou a ser registrado; movimentos anteriores não são inventados.

Em 10/11/2026 foi exportado um backup completo antes da aplicação no InfinityFree. Cópia privada em `.local/backups/ServiConnect_backup_2026-11-10_antes_etapas4e5.sql`, fora do Git e do deploy. A migração foi aplicada pelo phpMyAdmin ao banco correto e conferida: 4 usuários, 3 perfis, 5 solicitações e 7 registros arquivados preservados; 9 tabelas após a atualização.

Os arquivos consolidado e de instalação vazia foram atualizados. O banco vazio continua sem dados de demonstração. `tools/build-upgrade.php` gera os três arquivos a partir da migração 005.

## HTTPS

O certificado foi verificado como ativo até 18/01/2027. O servidor redireciona HTTP e `www` para `https://serviconnect.online`, preservando caminho e consulta. As regras são limitadas ao domínio de produção, sem redirecionar o ambiente local.

## Verificação

Suítes locais: `tests/security.php` (47), `tests/accounts.php` (90), `tests/search.php` (59) e `tests/workflow.php` (79): **275 verificações**. A suíte de exportações também confere instalação vazia, atualização/reaplicação, preservação de registros e dos novos estados, eventos e decisão de não publicar o mapa.

Conferência no navegador: seleção de serviços e cidades, gravação de perfil comercial, mapa e marcador, pedido, proposta com condições, aceite e leitura do histórico. O celular foi verificado em 390 px, sem rolagem horizontal. Dados fictícios usados nessa conferência foram removidos do banco local após o teste.

As propostas e as atualizações são registros entre as partes. A plataforma não processa pagamentos, não certifica qualificações nem verifica enquadramento fiscal. Os participantes combinam execução e pagamento diretamente.

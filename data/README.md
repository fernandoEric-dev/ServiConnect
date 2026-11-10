# Municípios

Fonte: [API de localidades do IBGE](https://servicodados.ibge.gov.br/api/docs/localidades), obtida em 10/11/2026 pelo endpoint `https://servicodados.ibge.gov.br/api/v1/localidades/municipios?orderBy=nome`.

São 5.571 municípios em 27 arquivos, um por UF. Cada item preserva o identificador oficial e o nome. O seletor carrega somente o arquivo do estado escolhido, sem enviar endereços residenciais a terceiros e sem depender de consulta externa durante o preenchimento.

O catálogo de **serviços** é mantido separadamente em `backend/catalog.php`: 80 serviços agrupados para facilitar a seleção por clientes. Não é uma classificação fiscal/CNAE.

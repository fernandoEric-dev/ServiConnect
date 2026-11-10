# Consulta obrigatória de CNPJ — OpenCNPJ

O cadastro de MEI, terceirizada e contratante pessoa jurídica consulta a API solicitada: `GET https://api.opencnpj.org/{CNPJ}?datasets=receita`. O filtro solicita apenas a base da Receita. Referências: [documentação](https://opencnpj.org/#/api), [sobre a base](https://opencnpj.org/#/sobre) e [código do projeto](https://github.com/Hitmasu/OpenCNPJ).

## Regra de criação

1. Validar formato/dígitos, modalidade, senha, CSRF e duplicidade.
2. Consultar OpenCNPJ no servidor, com conexão HTTPS verificada e limite de 12 segundos.
3. Exigir HTTP 200, JSON válido, documento correspondente e `situacao_cadastral` igual a `Ativa`.
4. Somente depois da confirmação iniciar a transação que cria usuário e perfil.

CNPJ inexistente (404) ou sem situação ativa gera 422 e não cria conta. Falha de rede, tempo esgotado, limite da API, erro HTTP, resposta incompleta/divergente ou inválida gera 503 e não cria conta; a pessoa pode tentar novamente. A classificação vem da base pública da OpenCNPJ, que pode ter defasagem em relação a uma alteração recente na Receita. A integração confirma existência/situação na base consultada; não é prova de titularidade do CNPJ ou de enquadramento MEI.

O botão **Consultar CNPJ** apresenta a situação e a razão social sem criar conta. O cadastro sempre reconsulta, mesmo após uma consulta positiva. Dados de validação enviados pelo navegador são ignorados. CPF continua com validação local de dígitos e não é enviado à OpenCNPJ. Contas existentes e login não foram alterados.

São enviados apenas CNPJ e identificação técnica da aplicação; e-mail e senha não são enviados à API. A aplicação não armazena o retorno completo nem registra CNPJ/corpo da API nos logs. Destino fixo, sem redirecionamentos, resposta limitada a 256 KiB. Consultas e cadastros compartilham limite de 60 chamadas por IP em 15 minutos, reaproveitando `login_tentativas` com chave própria. Não há mudança de esquema nem nova importação SQL.

## Testes

Com o banco separado de desenvolvimento ligado:

```powershell
pwsh -NoProfile -File .\tools\test-cnpj.ps1
```

O script inicia servidores temporários em `127.0.0.1:8082` (aplicação) e `127.0.0.1:8083` (simulador) e executa **69 verificações específicas**, **90 de contas/perfis** e **47 de segurança**. Os dados fictícios são removidos e os processos temporários encerrados ao terminar. O simulador evita depender de empresas reais para criar contas durante testes. Cobre estados inativos para todas as modalidades, inexistência, falhas, timeout real, resposta exagerada, reconsulta após mudança de situação, flags forjadas, payload legado, CPF e limite por IP.

`OPENCNPJ_TEST_URL` só funciona no servidor PHP embutido (`cli-server`), em desenvolvimento, com banco `serviconnect_dev` local na porta 3307, requisição de loopback e destino de loopback. Apache/FPM/InfinityFree sempre usam `https://api.opencnpj.org`; não existe opção de desligar a consulta obrigatória em produção. Testes e ferramentas estão excluídos do deploy.

Também foi conferida uma consulta HTTPS real pelo PHP local: CNPJ público do Banco do Brasil, resposta 200 e situação `Ativa` (10/11/2026).

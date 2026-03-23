<p align="center">
  <img src="foto/logo.jpg" alt="Logo ServiConnect" width="250">
</p>

<h1 align="center">🤝 ServiConnect</h1>

<p align="center">
  <strong>A plataforma ideal para conectar empresas contratantes a prestadoras de serviços terceirizados de forma prática, segura e transparente.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript">
  <img src="https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white" alt="HTML5">
  <img src="https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white" alt="CSS3">
</p>

---

## 📖 Sobre o Projeto

<p Desenvolvedores</p>

O **ServiConnect** é um sistema web desenvolvido para otimizar e centralizar o processo de contratação de serviços B2B (Business-to-Business). Seja para serviços de limpeza, segurança, manutenção ou TI, a plataforma oferece um ambiente dedicado onde as necessidades encontram as soluções certas.

O sistema conta com três frentes principais de utilizadores, garantindo fluxos de trabalho específicos para **Empresas Contratantes**, **Empresas Terceirizadas** e uma área de **Gestão Administrativa**.

## ✨ Principais Funcionalidades

### 🏢 Para Empresas Contratantes
* **Busca e Filtros:** Encontre facilmente empresas terceirizadas por região e área de atuação.
* **Solicitação de Orçamentos:** Envie pedidos detalhados (número de funcionários, local, descrição) diretamente para os prestadores.
* **Dashboard Exclusivo:** Acompanhe as empresas disponíveis e faça a gestão das suas contratações.

### 💼 Para Empresas Terceirizadas (Prestadoras)
* **Perfil Público (Estilo LinkedIn):** Destaque os seus serviços, adicione uma foto/logótipo, descreva a sua área de atuação e regiões atendidas.
* **Gestão de Pedidos:** Receba notificações de pedidos de orçamento de contratantes interessados.
* **Resposta Rápida:** Envie a sua proposta de valor diretamente pela plataforma ou recuse o pedido se não tiver disponibilidade.

### 🛡️ Para a Administração (Gestão)
* **Painel de Controlo:** Dashboard com métricas totais de utilizadores, empresas e pedidos realizados no sistema.
* **Gestão de Utilizadores:** Bloqueio e desbloqueio de contas por mau uso da plataforma.
* **Conformidade LGPD:** Funcionalidade de "Arquivar" contas (soft delete), guardando os dados de forma segura e inativando o acesso sem perda de histórico.

## 🛠️ Tecnologias Utilizadas

**Frontend:**
* HTML5 & CSS3 (Design Responsivo, CSS Variables, Flexbox/Grid)
* JavaScript (Vanilla)
* Integração com APIs externas (ViaCEP para endereços e OpenCNPJ para validação de empresas)
* Leaflet.js (Para mapas interativos)

**Backend:**
* PHP 8.x (Arquitetura MVC simplificada)
* PDO (PHP Data Objects) para consultas seguras à base de dados
* Sistema de Autenticação seguro com `password_hash`

**Base de Dados:**
* MySQL (Tabelas relacionais: `usuarios`, `empresas`, `solicitacoes`, `usuarios_arquivados`, `administradores`)

**DevOps & CI/CD:**
* GitHub Actions para Deploy Automático via FTP (InfinityFree) e Verificação de Sintaxe PHP (Lint).

## 🚀 Como Executar o Projeto Localmente

Siga os passos abaixo para rodar o projeto na sua máquina local utilizando o XAMPP ou servidor equivalente:

1. **Clone o repositório:**
   ```bash
   git clone [https://github.com/fernandoeric-dev/serviconnect.git](https://github.com/fernandoeric-dev/serviconnect.git)

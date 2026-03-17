# 🚀 Sistema de Gestão de Contratos (SaaS Satélite)

Sistema robusto de gestão de contratos e fornecedores, operando em arquitetura **Satélite (Headless-ready)** com autenticação delegada via **SSO (Single Sign-On)** e auditoria nativa.

---

## 🎨 Interface Moderna (Dashboard Administrative)
- **Tema Slate/Blue:** Layout focado em produtividade e clareza visual.
- **Topbar Fixa:** Acesso rápido a notificações e perfil do usuário.
- **Sidebar Dinâmica:** Menu colapsável com transições suaves e estados ativos.
- **Responsividade:** Design Mobile-first via DaisyUI e Tailwind CSS.

## 🛠️ Stack Tecnológica
- **Linguagem:** PHP 8.1+ (Lógica procedimental otimizada)
- **Framework CSS:** Tailwind CSS 3.4 + DaisyUI 4.7
- **Banco de Dados:** MySQL/MariaDB (PDO com emulação de Prepared Statements desligada para segurança)
- **Segurança:** HMAC-SHA256 (SSO), RBAC (Role-Based Access Control)
- **Iconografia:** Phosphor Icons

## 🔐 Arquitetura de Autenticação e Sessão (SSO)
O sistema opera como um satélite que recebe tokens assinados do **Portal GestorGov** e gerencia sessões de forma centralizada.
- **Mecanismo SSO:** Token Base64 assinado com HMAC-SHA256 validado no `auth_sso.php`.
- **Sessão Centralizada:** Configurada no `config.php` com suporte a `SameSite=Lax` e persistência de 24h, garantindo compatibilidade entre domínios/subpastas.
- **RBAC Robusto:** Controle de acesso baseado em níveis (Admin, Gestor, Consultor) com normalização de tipos e suporte a permissões granulares por módulo.

## 🤖 Orquestração AIOX
Este projeto utiliza o framework **AIOX (AI Orchestration Excellence)** para:
- Manutenção assistida por agentes especializados (@dev, @architect, @qa).
- Documentação técnica viva e sincronizada.
- Automação de tarefas complexas e garantia de padrões de engenharia.

## 📊 Auditoria e Logs
Todas as ações de CRUD são rastreadas via `logger.php`, registrando Usuário, Ação, Tabela, ID e Payload JSON. Scripts AJAX são otimizados para não interferir na integridade da sessão global.

## 🚀 Instalação Rápida

1. Clone o repositório em seu servidor PHP:
   ```bash
   git clone https://github.com/sandropeixoto/Gestao-de-Contratos.git
   ```

2. Configure o banco de dados em `config.php`:
   - Host: `192.185.214.25`
   - Banco: `eventoss_contratos`

3. Certifique-se de que a `SSO_SECRET_KEY` no `config.php` seja idêntica à do seu Portal integrador.

4. Dê permissão de escrita na pasta `uploads/`.

---
*Documento gerado e mantido pelo Agente Orion (AIOX Master Orchestrator).*

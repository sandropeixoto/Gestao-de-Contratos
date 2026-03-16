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

## 🔐 Arquitetura de Autenticação (SSO)
O sistema opera como um satélite que recebe tokens assinados do **Portal GestorGov**.
- **Mecanismo:** Token Base64 assinado com HMAC-SHA256.
- **Segredo:** `SSO_SECRET_KEY` compartilhada entre Portal e Módulo.
- **Controle de Acesso:** Sessão hidratada via Payload JSON (user_id, user_name, user_level).

## 📊 Auditoria e Logs
Todas as ações de CRUD (Create, Update, Delete) são rastreadas automaticamente via função `logSistema()` no arquivo `logger.php`, registrando:
- Usuário, Ação, Tabela, ID do Registro, Detalhes (JSON) e IP de origem.

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

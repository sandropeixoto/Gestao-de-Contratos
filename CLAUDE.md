# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

- **Backend:** PHP 8.1+ procedural, PDO + MySQL/MariaDB
- **Frontend:** Tailwind CSS 3.4 + DaisyUI 4.7, Phosphor Icons, IMask.js (currency/date masking), Chart.js
- **Sem build tools:** Sem npm, composer ou pipeline de CI. PHP é executado diretamente no servidor.

## Desenvolvimento

Sem comandos de build, lint ou testes automatizados. A aplicação roda diretamente em servidor PHP (Apache/Nginx + PHP-FPM).

Requisitos do ambiente:
- PHP 8.1+
- MySQL/MariaDB (banco: `eventoss_contratos`)
- Pasta `uploads/` com permissão de escrita
- `SSO_SECRET_KEY` em `config.php` idêntica ao Portal integrador

## Arquitetura

### Fluxo de Autenticação (SSO)
1. Portal GestorGov envia token Base64 assinado com HMAC-SHA256
2. `auth_sso.php` valida assinatura e cria sessão PHP (24h)
3. Toda página inclui `config.php` (que faz `session_start()`) e depois `auth_module.php` para checar RBAC
4. **AJAX scripts não devem chamar `session_start()`** — herdam via `require_once 'config.php'`. Chamar diretamente corromperia saídas JSON com warnings.

### RBAC (auth_module.php)
Níveis normalizados: `1`/`admin` → Administrador, `2`/`gestor` → Gestor, `consultor` → Consultor, `leitor` → Leitura.
Permissões granulares por módulo ficam na tabela `contratos_permissoes`.

### Padrão de páginas
Cada página PHP segue este padrão:
```php
require_once 'config.php';      // Sessão + PDO
require_once 'auth_module.php'; // RBAC check
require_once 'header.php';      // HTML head + topbar + sidebar
// ... conteúdo da página ...
require_once 'footer.php';
```

### AJAX Endpoints
- `ajax_pncp_fetch.php?id=` — busca contrato no PNCP (portal nacional) e retorna JSON para auto-fill do formulário
- `ajax_prestador.php?q=` — pesquisa fornecedores por CNPJ/nome
- `ajax_prestador_details.php?id=` — retorna dados completos de um fornecedor

### Action Handlers (POST)
- `contracts_action.php` — CRUD de contratos (actions: create, update, delete)
- `prestadores_action.php` — CRUD de fornecedores
- `contratos_anexos_action.php` — upload/exclusão de anexos
- `settings_action.php` — configurações do módulo

### Auditoria
Toda operação CRUD deve chamar `logSistema($acao, $tabela, $id, $detalhes)` via `logger.php`.
Ações válidas: `CREATE`, `UPDATE`, `DELETE`, `LOGIN`.

### Tabelas principais
- `Contratos` — dados do contrato com TACs (filhos via `contrato_pai_id`)
- `Prestador` — fornecedores (lookup por CNPJ)
- `contratos_permissoes` — override de RBAC por usuário
- `logs_auditoria` — rastreabilidade completa

### Relação Contrato/TAC
TACs são contratos filhos: mesma tabela `Contratos`, campo `contrato_pai_id` aponta para o contrato pai. O formulário `contract_form.php` gerencia ambos.

## Integrações Externas

- **PNCP:** API do Portal Nacional de Contratações Públicas — `ajax_pncp_fetch.php` consulta por `pncp_id`
- **Portal GestorGov:** Sistema pai que delega autenticação via SSO token

## Convenções

- Commits seguem Conventional Commits: `feat(scope):`, `fix(scope):`, `docs:`, etc.
- Agentes AIOX (`.github/agents/`) são perfis especializados para manutenção assistida — `@dev`, `@architect`, `@qa`, etc.
- Documentação técnica em `docs/` (ARCHITECTURE.md, MANUAL.md, PRD.md)

# 🏗️ Guia de Arquitetura: Contratos

## 1. Gestão de Sessão e SSO
O sistema gerencia sessões de forma centralizada para garantir que o acesso entre o Portal e o Módulo de Contratos seja contínuo.

### 🔐 Configuração Global (`config.php`)
- **Vida Útil:** 24 horas (`86400s`).
- **Segurança do Cookie:** `HttpOnly=true`, `SameSite=Lax`, `Path='/'`.
- **Início da Sessão:** Centralizado no topo do `config.php` via `session_start()`.

### ⚡ Padrão para Scripts AJAX
Para evitar corrupção de saídas JSON devido a avisos do PHP (Warnings) durante o início de sessão, os scripts AJAX (`ajax_prestador.php`, etc.) **não devem** chamar `session_start()`. Eles herdam a sessão através do `require_once 'config.php'`.

## 2. Camada de Autenticação e RBAC
A lógica de permissões é robusta e normalizada para evitar falhas de tipagem.

### 🛡️ Normalização de Níveis (`auth_module.php`)
O sistema normaliza os níveis de usuário (`user_level`) vindos da sessão para garantir consistência:
- `1` ou `admin` -> Administrador.
- `2` ou `gestor` -> Gestor.
- Além do RBAC global, o sistema consulta `contratos_permissoes` para definir perfis granulares no módulo.

## 3. Camada de Dados (MySQL)
O banco de dados é independente (`eventoss_contratos`).

### 📂 Tabelas Core
- `Contratos`: Dados principais, valores, prazos e metadados.
- `Prestador`: Informações de fornecedores.
- `usuarios`: Sincronização local básica para controle de RBAC.
- `logs_auditoria`: Rastreabilidade completa.
- `contratos_permissoes`: Sobrescrita de nível (fine-grained control) por usuário no módulo.

## 4. Auditoria Dinâmica (logger.php)
Implementado para garantir que toda alteração (CRUD) resulte em um rastro.

```php
logSistema($acao, $tabela, $id_registro, $detalhes = '');
```

- **Ação:** CREATE, UPDATE, DELETE, LOGIN.
- **Detalhes:** Salva o array de dados enviado (`$_POST`) em formato JSON.

## 5. Manutenção e Agentes AIOX
O sistema é mantido via **Framework AIOX**, garantindo que as mudanças sigam padrões SOLID e Clean Code sob a supervisão do agente `aiox-master`.

---
*Status: Finalizado em 16/03/2026.*

# CLAUDE.md — Guia para Assistentes de IA

Este arquivo descreve a arquitetura, convenções e fluxos de desenvolvimento do projeto **Gestao-de-Contratos** para orientar assistentes de IA (Claude, Copilot, Gemini, etc.).

---

## Visão Geral do Projeto

**Gestão de Contratos** é um módulo SaaS satélite de gestão de contratos administrativos, integrado ao portal GestorGov via SSO. Permite o controle completo de contratos, fornecedores, fiscais, documentos e sincronização com a API pública do PNCP (Portal Nacional de Contratações Públicas).

- **Tipo:** Módulo PHP/MySQL standalone com SSO centralizado
- **Arquitetura:** Satélite headless (sem login próprio, autenticado pelo portal pai)
- **Framework de desenvolvimento:** AIOX 2.2.0+ (orquestração baseada em agentes)
- **Público-alvo:** Gestores públicos municipais/estaduais

---

## Stack Tecnológica

| Camada     | Tecnologia                          |
|------------|-------------------------------------|
| Backend    | PHP 8.1+ (procedural)               |
| Banco      | MySQL/MariaDB via PDO               |
| Frontend   | Tailwind CSS 3.4 + DaisyUI 4.7      |
| Ícones     | Phosphor Icons (CDN)                |
| Gráficos   | Chart.js (CDN)                      |
| Máscaras   | IMask.js (CDN)                      |
| JavaScript | Vanilla JS (sem framework)          |
| Dev tools  | Node.js 20+, npm (apenas AIOX core) |

---

## Estrutura de Arquivos

```
Gestao-de-Contratos/
├── config.php                  # Configuração central (DB, sessão, constantes)
├── auth_sso.php                # Receptor SSO com validação HMAC-SHA256
├── auth_module.php             # Normalização de permissões RBAC
├── logger.php                  # Utilitário de auditoria (logs_auditoria)
├── index.php                   # Dashboard principal (KPIs, gráficos, recentes)
├── contratos.php               # Listagem de contratos com filtros
├── contract_form.php           # Formulário de criação/edição de contrato
├── contract_view.php           # Visualização detalhada do contrato
├── prestadores.php             # Listagem de fornecedores
├── prestador_form.php          # Formulário de criação/edição de fornecedor
├── settings.php                # Painel administrativo de configurações
├── contracts_action.php        # Ações POST: create, update, delete contratos
├── prestadores_action.php      # Ações POST: create, update, delete fornecedores
├── contratos_anexos_action.php # Gestão de documentos anexos
├── settings_action.php         # Ações POST: configurações do sistema
├── ajax_prestador.php          # Busca AJAX de fornecedores (GET)
├── ajax_prestador_details.php  # Detalhes completos de fornecedor (GET)
├── ajax_pncp_fetch.php         # Busca dados PNCP da API federal (GET)
├── header.php                  # Layout principal com navegação
├── sidebar_content.php         # Conteúdo da sidebar
├── footer.php                  # Rodapé do layout
├── .env.example                # Template de variáveis de ambiente
├── docs/
│   ├── ARCHITECTURE.md         # Guia técnico de arquitetura
│   ├── PRD.md                  # Documento de requisitos do produto
│   └── MANUAL.md               # Manual do usuário
├── SUGGESTIONS.md              # Roadmap de melhorias
└── .aiox-core/                 # Framework AIOX (não modificar diretamente)
```

---

## Configuração e Ambiente

### config.php

O arquivo `config.php` é o **ponto central de configuração**. Ele deve ser incluído no topo de todas as páginas antes de qualquer output:

```php
require_once 'config.php'; // inclui sessão, PDO ($pdo), constantes
```

Ele configura:
- Conexão PDO com MySQL (sem emulação de prepared statements)
- Sessão centralizada (lifetime 24h, HttpOnly, SameSite=Lax)
- Constantes de permissão RBAC
- Chave SSO

### Variáveis de Ambiente Críticas

```
DB_HOST     = 192.185.214.25
DB_USER     = eventoss_contratos
DB_PASS     = (ver config.php)
DB_NAME     = eventoss_contratos
SSO_SECRET  = GestorGov_Secure_Integration_Token_2026!
```

> **Atenção:** Atualmente as credenciais estão hardcoded em `config.php`. Ao refatorar, mover para variáveis de ambiente reais.

---

## Banco de Dados

### Tabelas Principais

| Tabela                      | Propósito                                    |
|-----------------------------|----------------------------------------------|
| `Contratos`                 | Contratos administrativos (entidade central) |
| `Prestador`                 | Fornecedores/prestadores de serviço          |
| `usuarios`                  | Sincronização local de usuários SSO          |
| `logs_auditoria`            | Trilha de auditoria (CREATE/UPDATE/DELETE)   |
| `contratos_permissoes`      | Permissões granulares por módulo/usuário     |
| `contratos_configuracoes`   | Configurações chave-valor do sistema         |
| `contratos_fiscais_setoriais` | Fiscais setoriais por contrato             |
| `CategoriaContrato`         | Lookup de categorias                         |
| `Modalidade`                | Modalidades de contratação                   |
| `Diretorias`                | Unidades organizacionais                     |
| `contratos_coordenacoes`    | Coordenações vinculadas                      |
| `FontesRecursos`            | Fontes de recursos orçamentários             |
| `TiposDocumentos`           | Tipos de documentos anexáveis               |

### Convenções de Nomenclatura

- **Tabelas:** snake_case (ex: `logs_auditoria`) ou PascalCase (ex: `CategoriaContrato`)
- **Colunas:** PascalCase para entidades (ex: `VigenciaInicio`, `PrestadorId`) ou snake_case para tabelas de suporte
- **FKs:** sufixo `Id` (ex: `PrestadorId`, `ModalidadeId`)

### Campos PNCP em Contratos

```
PncpIdContratacao  -- ID da compra/licitação no PNCP
PncpIdContrato     -- ID do contrato no PNCP
PncpLastSync       -- Timestamp da última sincronização
```

---

## Autenticação e Permissões

### Fluxo SSO

1. Portal GestorGov envia `sso_payload` + `sso_sig` para `auth_sso.php`
2. `auth_sso.php` valida assinatura HMAC-SHA256 com `SSO_SECRET_KEY`
3. Sessão PHP é criada com dados do usuário
4. `auth_module.php` normaliza permissões para o módulo

### Variáveis de Sessão

```php
$_SESSION['user_id']     // ID do usuário
$_SESSION['user_name']   // Nome completo
$_SESSION['user_email']  // E-mail
$_SESSION['user_level']  // Nível global (admin, gestor, consultor, leitor)
```

### Constantes de Permissão RBAC

```php
CONTRATOS_ADMIN      // Acesso total ao módulo
CONTRATOS_GESTOR     // Criação e edição
CONTRATOS_CONSULTOR  // Leitura e exportação
CONTRATOS_LEITOR     // Somente leitura
```

### Verificação de Permissão

```php
require_once 'auth_module.php'; // normaliza permissão efetiva
// Após incluir, verificar antes de operações sensíveis:
if (!in_array($permissao_efetiva, [CONTRATOS_ADMIN, CONTRATOS_GESTOR])) {
    http_response_code(403);
    exit('Acesso negado');
}
```

---

## Padrões de Código PHP

### Estrutura de Página

```php
<?php
require_once 'config.php';      // 1. Configuração e sessão
require_once 'auth_module.php'; // 2. Verificação de permissão
// ... lógica PHP ...
?>
<!DOCTYPE html>
<html>
<?php include 'header.php'; ?> <!-- 3. Layout wrapper -->
<!-- conteúdo -->
<?php include 'footer.php'; ?>
```

### Consultas PDO

```php
// SEMPRE usar prepared statements
$stmt = $pdo->prepare("SELECT * FROM Contratos WHERE Id = :id");
$stmt->execute([':id' => $id]);
$contrato = $stmt->fetch(PDO::FETCH_ASSOC);

// NUNCA interpolar variáveis diretamente em SQL
// ERRADO: $pdo->query("SELECT * FROM Contratos WHERE Id = $id");
```

### Output HTML

```php
// SEMPRE escapar output
echo htmlspecialchars($contrato['Objeto'], ENT_QUOTES, 'UTF-8');
```

### Respostas AJAX

```php
header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $resultado]);
exit;
// Em caso de erro:
http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Mensagem de erro']);
exit;
```

### Auditoria

```php
require_once 'logger.php';
// Registrar operações CRUD
registrarLog($pdo, $user_id, $user_name, 'CREATE', 'Contratos', $novoId, json_encode($dados));
registrarLog($pdo, $user_id, $user_name, 'UPDATE', 'Contratos', $id, json_encode($diff));
registrarLog($pdo, $user_id, $user_name, 'DELETE', 'Contratos', $id, json_encode($contrato));
```

---

## Frontend (Tailwind + DaisyUI)

### Princípios

- **Mobile-first:** usar classes responsivas (`sm:`, `md:`, `lg:`)
- **Tema:** suporte a dark/light via `data-theme` no `<html>`
- **Componentes DaisyUI:** preferir `btn`, `card`, `badge`, `modal`, `table` nativos
- **Ícones:** sempre Phosphor Icons via CDN

### Exemplo de Componente

```html
<!-- Card padrão -->
<div class="card bg-base-100 shadow-md">
  <div class="card-body">
    <h2 class="card-title text-base-content">Título</h2>
    <!-- conteúdo -->
  </div>
</div>

<!-- Badge de status -->
<span class="badge badge-success">Ativo</span>
<span class="badge badge-warning">Expirando</span>
<span class="badge badge-error">Vencido</span>
```

### Máscaras de Valores Monetários

```javascript
// Usar IMask para campos de valor
const maskOptions = {
  mask: 'R$ num',
  blocks: {
    num: {
      mask: Number,
      thousandsSeparator: '.',
      radix: ',',
      scale: 2,
      padFractionalZeros: true,
      signed: false,
    }
  }
};
IMask(document.getElementById('valorMensal'), maskOptions);
```

---

## Integração PNCP

O módulo se integra com a API pública do PNCP (Portal Nacional de Contratações Públicas) via `ajax_pncp_fetch.php`.

### Parâmetros

```
GET ajax_pncp_fetch.php?id={PncpIdContrato}&type={compra|contrato}
```

### Campos Mapeados Automaticamente

- Objeto do contrato
- Valor global e mensal
- Datas de vigência e assinatura
- CNPJ e nome do fornecedor
- Modalidade e categoria

> **Importante:** Ao detectar divergências entre dados locais e PNCP, registrar no log de divergências antes de sobrescrever.

---

## Fluxo de Desenvolvimento com AIOX

O projeto usa o framework AIOX para desenvolvimento orientado por agentes. Os arquivos estão em `.aiox-core/`.

### Comandos AIOX (via npm no `.aiox-core/`)

```bash
cd .aiox-core
npm run build        # Build do framework
npm test             # Suite completa (unit + integration)
npm run test:unit    # Apenas testes unitários
npm run lint         # Validação ESLint
npm run typecheck    # Checagem TypeScript
```

> **Nota:** O diretório `.aiox-core/` é gerenciado pelo framework AIOX. Não modificar diretamente sem entender as implicações do `constitution.md`.

---

## Convenções de Git

### Formato de Commit

```
{type}({scope}): {descrição em português ou inglês}
```

**Types:**
- `feat` — nova funcionalidade
- `fix` — correção de bug
- `refactor` — refatoração sem mudança de comportamento
- `docs` — documentação
- `chore` — manutenção, configs

**Exemplos reais do projeto:**
```
feat(pncp): implement advanced field mapping and auto-calculation
fix(contract-form): correct supplier field ID
refactor(pncp): restrict auto-fill to PNCP Contract ID
docs: add CLAUDE.md with codebase guide
```

### Branches

- `master` — produção
- `claude/{descricao}-{id}` — branches de trabalho do Claude
- Sempre criar branch antes de iniciar trabalho

---

## Segurança — Checklist

Ao modificar ou criar arquivos PHP, verificar:

- [ ] `config.php` incluído no topo (sessão iniciada)
- [ ] `auth_module.php` incluído (permissão verificada)
- [ ] Todas as queries usam PDO prepared statements
- [ ] Todo output HTML passa por `htmlspecialchars()`
- [ ] Respostas AJAX retornam JSON com `Content-Type: application/json`
- [ ] Operações CRUD registradas via `logger.php`
- [ ] Validação RBAC antes de ações sensíveis (create/update/delete)
- [ ] Sanitização de CNPJ via `preg_replace('/[^0-9]/', '', $cnpj)`
- [ ] Valores monetários convertidos corretamente (vírgula → ponto)

---

## Perfis de Usuário

| Perfil             | Permissões                                     |
|--------------------|------------------------------------------------|
| `CONTRATOS_ADMIN`  | CRUD completo + configurações + auditoria       |
| `CONTRATOS_GESTOR` | CRUD de contratos e fornecedores               |
| `CONTRATOS_CONSULTOR` | Leitura + exportação                        |
| `CONTRATOS_LEITOR` | Somente visualização                           |

---

## Pontos de Atenção para IA

1. **Não criar arquivos desnecessários** — o projeto usa PHP procedural; não introduzir OOP ou MVC sem necessidade clara.
2. **Manter padrão de includes** — sempre `config.php` → `auth_module.php` → `logger.php` na ordem correta.
3. **Valores monetários** — campos de valor usam vírgula como decimal no Brasil; converter para ponto antes de salvar no banco.
4. **Datas** — formato banco: `YYYY-MM-DD`; formato exibição: `DD/MM/YYYY`.
5. **CNPJ** — armazenar apenas dígitos no banco; formatar apenas na exibição.
6. **TACs (Termos Aditivos)** — contratos com `PaiId != NULL` são filhos de outro contrato; tratar hierarquia adequadamente.
7. **PNCP divergências** — nunca sobrescrever dados locais sem registrar divergência no log.
8. **`.aiox-core/`** — não modificar este diretório; é o framework de orquestração.
9. **Sessão** — nunca `session_start()` manual; já feito no `config.php`.
10. **PDO** — a variável global `$pdo` é disponibilizada pelo `config.php`; não criar nova conexão.

---

## Referências Rápidas

| Recurso             | Localização                        |
|---------------------|------------------------------------|
| Arquitetura técnica | `docs/ARCHITECTURE.md`             |
| Requisitos produto  | `docs/PRD.md`                      |
| Manual do usuário   | `docs/MANUAL.md`                   |
| Melhorias sugeridas | `SUGGESTIONS.md`                   |
| Config central      | `config.php`                       |
| Regras AIOX         | `.aiox-core/constitution.md`       |
| Guia AIOX           | `.aiox-core/user-guide.md`         |

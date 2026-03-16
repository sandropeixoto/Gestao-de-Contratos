# 🏗️ Guia de Arquitetura: Contratos

## 1. Fluxo de Autenticação SSO (Receptor)
O sistema não possui tela de login local. O acesso é delegado através do arquivo `auth_sso.php`.

### 🔐 Processo HMAC-SHA256
1. O Portal gera um JSON com os dados do usuário (`user_id`, `user_name`, `user_level`, `exp`).
2. O JSON é encodado em **Base64** (`sso_payload`).
3. Uma assinatura é gerada usando `hash_hmac('sha256', $sso_payload, SSO_SECRET_KEY)`.
4. O receptor (`auth_sso.php`) valida a assinatura. Se houver divergência, o acesso é negado.

## 2. Camada de Dados (MySQL)
O banco de dados é independente (`eventoss_contratos`).

### 📂 Tabelas Core
- `Contratos`: Dados principais, valores, prazos e metadados.
- `Prestador`: Informações de fornecedores.
- `usuarios`: Sincronização local básica para controle de RBAC.
- `logs_auditoria`: Rastreabilidade completa.
- `contratos_permissoes`: Sobrescrita de nível (fine-grained control) por usuário no módulo.

## 3. Sistema de Auditoria (logger.php)
Implementado de forma a garantir que toda ação POST resulte em um registro na tabela `logs_auditoria`.

```php
// Assinatura da Função
logSistema($acao, $tabela, $id_registro, $detalhes = '');
```

- **Ação:** CREATE, UPDATE, DELETE, LOGIN.
- **Detalhes:** Salva os dados originais enviados via formulário em formato JSON.

## 4. Estrutura de Diretórios
- `/`: Raiz do sistema (Scripts principais e receptor SSO).
- `uploads/`: Armazenamento de anexos.
- `docs/`: Documentação e PRDs.
- `.aiox-core/`: Núcleo do framework de orquestração AIOX.

---
*Status: Finalizado em 16/03/2026.*

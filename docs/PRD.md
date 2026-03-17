# 📄 PRD: Módulo de Contratos (SaaS Satélite)

## 1. Visão Geral do Produto
O **Contratos** é um módulo de gestão administrativa para controle de fornecedores, contratos vigentes, aditivos e anexos. Ele foi projetado para operar de forma desacoplada, integrando-se a portais de gestão através de protocolos de autenticação segura (SSO).

## 2. Objetivos de Negócio
- **Desacoplamento:** Operar com banco de dados próprio e independente do Portal central.
- **Rastreabilidade:** Garantir que 100% das alterações nos dados sejam auditáveis.
- **Segurança:** Impedir o acesso direto via URL sem uma sessão SSO validada.
- **Eficiência UX:** Oferecer uma interface moderna (Slate/Blue Dashboard) que reduza a carga cognitiva do usuário.

## 3. Personas (RBAC)
O sistema implementa o **Role-Based Access Control** com suporte a:
- **Administrador (1):** Acesso total ao sistema, configurações globais e permissões de usuários.
- **Gestor (2):** Gerencia contratos, fornecedores e visualiza dashboards.
- **Consultor/Técnico:** Realiza leituras avançadas e edições autorizadas de contratos.
- **Leitor:** Acesso apenas para consulta (visualização) de registros.

## 4. Funcionalidades Principais
- **Dashboard:** KPIs em tempo real, contratos a vencer e estatísticas de fornecedores.
- **Gestão de Contratos:** Listagem com filtros, dossiê do contrato (visualização completa), e formulário de CRUD.
- **Gestão de Fornecedores (Prestadores):** Cadastro completo com contatos múltiplos (1-N).
- **Aditivos e Anexos:** Gestão de arquivos físicos vinculados aos contratos.
- **Configurações:** Tabelas auxiliares (Diretorias, Fontes de Recursos, Modalidades).

## 5. Requisitos Não-Funcionais (RNF)
- **Performance:** Carregamento de dashboard inicial em menos de 1.5s.
- **Segurança:** Todas as chaves secretas (SSO_SECRET_KEY) devem estar no `config.php` (não acessível via web).
- **Consistência de Sessão:** O sistema deve garantir que o cookie de sessão seja compatível entre o domínio raiz e subpastas para manter o login SSO.
- **Robustez AJAX:** Scripts de requisição assíncrona devem operar de forma silenciosa quanto à gestão de sessão, evitando a emissão de cabeçalhos redundantes que corrompam saídas JSON.
- **Auditoria:** Gravação de log obrigatória em todas as ações POST.

---
*Status: Atualizado em 17/03/2026 (Orchestrated by Orion).*

# Sugestões de Melhoria: Integração PNCP 👑

Após a implementação bem-sucedida da fase inicial de integração com o Portal Nacional de Contratações Públicas (PNCP), identificamos oportunidades de evolução para aumentar a robustez, segurança e automação do sistema.

## 1. Arquitetura e Performance (Backend)
- **Camada de Cache:** Implementar um cache (ex: Redis ou tabela de cache em banco) para as consultas à API do PNCP. Isso reduz o tempo de resposta e evita bloqueios por *rate limit* do portal do governo em ambientes com muitos usuários.
- **Refatoração para Service Pattern:** Mover a lógica de `ajax_pncp_fetch.php` para uma classe `PncpService.php`. Isso facilitaria o reuso em comandos de linha de comando (CLI) ou outros módulos do sistema.
- **Tratamento de Timeouts:** Adicionar um tratamento mais refinado para quando a API do PNCP estiver instável, exibindo uma mensagem amigável de "Portal temporariamente indisponível".

## 2. Experiência do Usuário (UX/UI)
- **Busca por Órgão:** Permitir que o usuário pesquise contratações apenas digitando o CNPJ do órgão (ou selecionando de uma lista), trazendo os últimos editais publicados para escolha, sem que ele precise saber o ID completo.
- **Importação de Itens:** A API do PNCP permite listar os itens da contratação (`/itens`). Poderíamos adicionar uma funcionalidade para importar a lista de materiais/serviços diretamente para o nosso banco de dados.
- **Download Automático de Anexos:** Criar um botão "Sincronizar Documentos" que busca os PDFs (Contrato assinado, Editais) anexados no PNCP e faz o upload automático para a nossa aba de anexos.

## 3. Integridade e Auditoria
- **Verificador de Divergência (Background):** Criar um *cron job* (tarefa agendada) que percorre os contratos ativos uma vez por semana, consulta o PNCP e gera um alerta caso o valor global ou a vigência tenham sido alterados no portal oficial sem alteração correspondente no sistema local.
- **Histórico de Sincronização:** Manter um log de quem realizou a sincronização e quais campos foram alterados, permitindo o "rollback" para a versão anterior caso o usuário importe dados errados por engano.

## 4. Segurança
- **Sanitização de IDs:** Implementar uma máscara de entrada no campo de ID (JS Mask) para garantir que o usuário sempre digite no formato correto `{CNPJ}-{TIPO}-{SEQ}/{ANO}`.
- **Validação de Fornecedor:** Ao importar, se o CNPJ do fornecedor no PNCP for diferente do selecionado no sistema, exibir um aviso de "Divergência de Fornecedor" para evitar cadastros em contratos errados.

---
*Gerado por Orion, orquestrando a melhoria contínua do sistema.* 🎯

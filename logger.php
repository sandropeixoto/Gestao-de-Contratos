<?php
/**
 * Script de Auditoria de Logs (Extraído do Portal GestorGov)
 * 
 * Este arquivo é a Única Fonte de Verdade para auditoria de ações no sistema.
 * Integrado conforme o Guia de Migração (SSOT) de 16/03/2026.
 */

require_once 'config.php';

if (!function_exists('logSistema')) {
    /**
     * Registra uma ação do usuário no banco de dados para fins de auditoria.
     * 
     * @param string $acao Ex: 'CREATE', 'UPDATE', 'DELETE'
     * @param string $tabela Nome da tabela afetada
     * @param int|string $id_registro ID do registro afetado
     * @param string $detalhes Informações adicionais (JSON ou texto)
     */
    function logSistema($acao, $tabela, $id_registro, $detalhes = '') {
        global $pdo;

        // Recupera o ID do usuário da sessão (compatível com auth_sso.php)
        $id_usuario = $_SESSION['user_id'] ?? 0;
        $usuario_nome = $_SESSION['user_name'] ?? 'Sistema/SSO';
        $ip_usuario = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        try {
            $sql = "INSERT INTO logs_auditoria (id_usuario, usuario_nome, acao, tabela, id_registro, detalhes, ip_origem, data_registro) 
                    VALUES (:id_usuario, :usuario_nome, :acao, :tabela, :id_registro, :detalhes, :ip_origem, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_usuario' => $id_usuario,
                ':usuario_nome' => $usuario_nome,
                ':acao' => strtoupper($acao),
                ':tabela' => $tabela,
                ':id_registro' => $id_registro,
                ':detalhes' => $detalhes,
                ':ip_origem' => $ip_usuario
            ]);
        } catch (PDOException $e) {
            // Em caso de falha no log, não trava o sistema principal, apenas reporta no log de erro do servidor
            error_log("Erro ao registrar log de auditoria: " . $e->getMessage());
        }
    }
}
?>

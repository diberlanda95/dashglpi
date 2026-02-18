<?php
// db.php - Configuração de Conexão Segura

// --- CORREÇÃO PRINCIPAL: Apenas o IP, sem "http://" ---
$host = '192.168.18.30'; 
$db   = 'glpi_db';
$user = 'glpi_user';
// A senha está entre aspas simples, o que é ÓTIMO porque ela tem um cifrão ($)
$pass = 'Inx91$megsrd'; 
$charset = 'utf8mb4';

// Configurações do PDO
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    // Define timeout para não travar o dashboard se o banco cair
    PDO::ATTR_TIMEOUT            => 5, 
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Garante que o navegador entenda que é um JSON de erro
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    
    http_response_code(500);
    
    // Dica: Para testes, descomente a linha abaixo para ver o erro real. 
    // Em produção, deixe a mensagem genérica.
    // echo json_encode(['error' => 'SQL Error: ' . $e->getMessage()]);
    
    echo json_encode(['error' => 'Erro de conexão com o banco de dados. Verifique IP e Senha.']);
    exit;
}
?>
<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$db = getenv('DB_NAME');
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

$conn = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $conn->query("SELECT * FROM produtos ORDER BY id DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $stmt = $conn->prepare("INSERT INTO produtos(nome, quantidade, preco) VALUES(:nome, :quantidade, :preco)");
    $stmt->execute([
        ':nome' => $data['nome'],
        ':quantidade' => $data['quantidade'],
        ':preco' => $data['preco']
    ]);

    echo json_encode(["message" => "Produto criado"]);
}

if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);

    $stmt = $conn->prepare("UPDATE produtos SET nome=:nome, quantidade=:quantidade, preco=:preco WHERE id=:id");
    $stmt->execute([
        ':id' => $data['id'],
        ':nome' => $data['nome'],
        ':quantidade' => $data['quantidade'],
        ':preco' => $data['preco']
    ]);

    echo json_encode(["message" => "Produto actualizado"]);
}

if ($method === 'DELETE') {
    parse_str($_SERVER['QUERY_STRING'], $params);

    $stmt = $conn->prepare("DELETE FROM produtos WHERE id=:id");
    $stmt->execute([':id' => $params['id']]);

    echo json_encode(["message" => "Produto removido"]);
}
?>
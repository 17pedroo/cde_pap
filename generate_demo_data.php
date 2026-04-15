<?php
require __DIR__ . "/includes/config.php";

date_default_timezone_set('Europe/Lisbon');
$weekStart = date('Y-m-d 00:00:00', strtotime('monday this week'));
$tomorrow = date('Y-m-d 23:59:59', strtotime('tomorrow'));

function create_user(PDO $pdo, string $role, ?string $studentNumber, string $name, string $plainPass, string $qrSecret): int {
    if ($studentNumber !== null) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE student_number = ? LIMIT 1");
        $stmt->execute([$studentNumber]);
        $existing = $stmt->fetchColumn();
        if ($existing) {
            return (int)$existing;
        }
    }

    $hash = password_hash($plainPass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (role, student_number, name, password_hash, qr_secret) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$role, $studentNumber, $name, $hash, $qrSecret]);
    return (int)$pdo->lastInsertId();
}

function create_product(PDO $pdo, string $name, int $priceCents, string $category, bool $active = true): int {
    $stmt = $pdo->prepare("SELECT id FROM products WHERE name = ? LIMIT 1");
    $stmt->execute([$name]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        return (int)$existing;
    }
    $stmt = $pdo->prepare("INSERT INTO products (name, price_cents, category, is_active) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $priceCents, $category, $active ? 1 : 0]);
    return (int)$pdo->lastInsertId();
}

function ensure_wallet(PDO $pdo, int $userId, int $balanceCents): void {
    $stmt = $pdo->prepare("SELECT user_id FROM wallets WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    if ($stmt->fetchColumn()) {
        $update = $pdo->prepare("UPDATE wallets SET balance_cents = ? WHERE user_id = ?");
        $update->execute([$balanceCents, $userId]);
    } else {
        $insert = $pdo->prepare("INSERT INTO wallets (user_id, balance_cents) VALUES (?, ?)");
        $insert->execute([$userId, $balanceCents]);
    }
}

function add_wallet_transaction(PDO $pdo, int $userId, string $type, int $amountCents, string $description, ?string $createdAt = null): void {
    $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount_cents, description, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $type, $amountCents, $description, $createdAt ?? date('Y-m-d H:i:s')]);
}

function add_access_log(PDO $pdo, int $userId, string $action, int $scannedBy, ?string $createdAt = null, ?string $notes = null): void {
    $stmt = $pdo->prepare("INSERT INTO access_logs (user_id, action, scanned_by_user_id, scanned_at, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $scannedBy, $createdAt ?? date('Y-m-d H:i:s'), $notes]);
}

function add_canteen_ticket(PDO $pdo, int $studentId, string $ticketType, string $status, ?int $scannedBy, ?string $notes = null, ?string $reservedAt = null): void {
    $stmt = $pdo->prepare("INSERT INTO canteen_tickets (student_id, ticket_type, status, scanned_by_user_id, notes, reserved_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$studentId, $ticketType, $status, $scannedBy, $notes, $reservedAt ?? date('Y-m-d H:i:s')]);
}

function randomDate(string $start, string $end): string {
    $min = strtotime($start);
    $max = strtotime($end);
    $date = rand($min, $max);
    return date('Y-m-d H:i:s', $date);
}

$studentNames = [
    'Ana Silva', 'Beatriz Costa', 'Bruno Oliveira', 'Catarina Santos',
    'Diogo Sousa', 'Duarte Ferreira', 'Elisa Rocha', 'Filipe Marques',
    'Inês Lima', 'João Carvalho', 'Jorge Martins', 'Júlia Pereira',
    'Laura Moreira', 'Leonor Rocha', 'Luís Nunes', 'Margarida Alves',
    'Maria Pires', 'Miguel Azevedo', 'Mariana Carvalho', 'Matilde Fonseca',
    'Nuno Ribeiro', 'Patrícia Correia', 'Pedro Teixeira', 'Rafael Lopes',
    'Rita Monteiro', 'Sara Araújo', 'Sofia Almeida', 'Tiago Campos', 'Vasco Vieira'
];

$guardianNames = [
    'Carlos Rodrigues', 'Helena Machado', 'Marta Ramos', 'Paulo Neves',
    'Rosa Araújo', 'Sandra Duarte'
];

$productItems = [
    ['Sandes Mista', 220, 'refeição'],
    ['Salada de Atum', 250, 'refeição'],
    ['Sopa de Legumes', 180, 'refeição'],
    ['Água Pequena', 100, 'bebida'],
    ['Sumo de Laranja', 130, 'bebida'],
    ['Iogurte Grego', 140, 'lanche'],
    ['Maçã', 120, 'lanche'],
    ['Pão de Leite', 90, 'lanche'],
    ['Leite Chocolateado', 150, 'bebida'],
    ['Bolo de Chocolate', 160, 'lanche']
];

$startNumber = 10001;
$studentIds = [];
foreach ($studentNames as $index => $name) {
    $studentNumber = (string)($startNumber + $index);
    $studentIds[] = create_user($pdo, 'student', $studentNumber, $name, '1234', 'secret-' . $studentNumber);
}

$staffStmt = $pdo->prepare("SELECT id FROM users WHERE role IN ('staff','admin') LIMIT 1");
$staffStmt->execute();
$staffId = $staffStmt->fetchColumn();
if (!$staffId) {
    $staffId = create_user($pdo, 'staff', null, 'Portaria Demo', 'portaria123', 'portaria-secret');
}

$guardianIds = [];
foreach ($guardianNames as $index => $name) {
    $guardianKey = 'G' . str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT);
    $guardianIds[] = create_user($pdo, 'guardian', $guardianKey, $name, '1234', 'guardian-' . $guardianKey);
}

$guardianStudents = [
    [0, 1, 2],
    [3, 4, 5],
    [6, 7, 8],
    [9, 10, 11],
    [12, 13, 14],
    [15, 16, 17, 18, 19]
];

foreach ($guardianStudents as $guardianIndex => $students) {
    $guardianId = $guardianIds[$guardianIndex % count($guardianIds)];
    foreach ($students as $studentIndex) {
        $studentId = $studentIds[$studentIndex];
        $pdo->prepare("INSERT IGNORE INTO guardian_students (guardian_id, student_id) VALUES (?, ?)")->execute([$guardianId, $studentId]);
    }
}

$productRows = [];
foreach ($productItems as $item) {
    $productRows[] = [
        'id' => create_product($pdo, $item[0], $item[1], $item[2]),
        'name' => $item[0],
        'price_cents' => $item[1]
    ];
}

foreach ($studentIds as $index => $studentId) {
    $balance = rand(200, 2000);
    ensure_wallet($pdo, $studentId, $balance);

    add_wallet_transaction($pdo, $studentId, 'topup', 1500, 'Carregamento inicial de demonstração', randomDate($weekStart, $tomorrow));
    $product = $productRows[array_rand($productRows)];
    add_wallet_transaction(
        $pdo,
        $studentId,
        'purchase',
        -$product['price_cents'],
        'Compra bar: ' . $product['name'],
        randomDate($weekStart, $tomorrow)
    );
    if ($index % 3 === 0) {
        add_wallet_transaction($pdo, $studentId, 'adjustment', rand(-150, 150), 'Ajuste manual de saldo', randomDate($weekStart, $tomorrow));
    }
}

$statusOptions = ['active', 'scanned', 'cancelled'];
foreach ($studentIds as $studentId) {
    for ($i = 0; $i < 2; $i++) {
        $status = $statusOptions[array_rand($statusOptions)];
        add_canteen_ticket(
            $pdo,
            $studentId,
            'almoco',
            $status,
            $status === 'active' ? null : $staffId,
            $status === 'cancelled' ? 'Senha cancelada automaticamente' : 'Pedido de refeição para demonstração',
            randomDate($weekStart, $tomorrow)
        );
    }
}

$actions = ['IN', 'OUT'];
foreach ($studentIds as $studentId) {
    $last = null;
    for ($i = 0; $i < 6; $i++) {
        $action = $actions[($i + 1) % 2];
        add_access_log($pdo, $studentId, $action, $staffId, randomDate($weekStart, $tomorrow), $action === 'OUT' ? 'Saída do recinto' : 'Entrada no recinto');
        $last = $action;
    }
}

echo "<h2>Dados de demonstração criados com sucesso ✅</h2>\n";
echo "<p>Foram criados " . count($studentIds) . " alunos, " . count($guardianIds) . " encarregados, 1 funcionário de portaria e produtos de bar/cantina.</p>\n";
echo "<p>Login dos alunos: número de aluno = 10001.." . (10000 + count($studentIds)) . " e palavra-passe = 1234</p>\n";
echo "<p>Login do staff de portaria: utilizador existente ou criado com palavra-passe = portaria123</p>\n";

<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/includes/layout.php";

require_staff();

$feedback = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST["action"] ?? "";

  if ($action === "create") {
    $name = trim($_POST["name"] ?? "");
    $price = str_replace([",", "€"], [".", ""], trim($_POST["price"] ?? ""));
    $category = trim($_POST["category"] ?? "");
    $is_active = isset($_POST["is_active"]) ? 1 : 0;

    if ($name === "" || $price === "" || !is_numeric($price)) {
      $feedback = ["type" => "danger", "text" => "Preencha corretamente o nome e o preço do produto."];
    } else {
      $price_cents = (int) round((float) $price * 100);
      $stmt = $pdo->prepare("INSERT INTO products (name, price_cents, category, is_active) VALUES (?, ?, ?, ?)");
      $stmt->execute([$name, $price_cents, $category, $is_active]);
      $feedback = ["type" => "success", "text" => "Produto criado com sucesso."];
    }
  }

  if ($action === "toggle") {
    $id = (int) ($_POST["id"] ?? 0);
    $stmt = $pdo->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    $feedback = ["type" => "success", "text" => "Estado de produto atualizado."];
  }

  if ($action === "update") {
    $id = (int) ($_POST["id"] ?? 0);
    $name = trim($_POST["name"] ?? "");
    $price = str_replace([",", "€"], [".", ""], trim($_POST["price"] ?? ""));
    $category = trim($_POST["category"] ?? "");
    $is_active = isset($_POST["is_active"]) ? 1 : 0;

    if ($id < 1 || $name === "" || $price === "" || !is_numeric($price)) {
      $feedback = ["type" => "danger", "text" => "Preencha corretamente os dados do produto."];
    } else {
      $price_cents = (int) round((float) $price * 100);
      $stmt = $pdo->prepare("UPDATE products SET name = ?, price_cents = ?, category = ?, is_active = ? WHERE id = ?");
      $stmt->execute([$name, $price_cents, $category, $is_active, $id]);
      $feedback = ["type" => "success", "text" => "Produto atualizado com sucesso."];
    }
  }
}

$products = $pdo->query("SELECT id, name, price_cents, category, is_active FROM products ORDER BY category, name")->fetchAll(PDO::FETCH_ASSOC);

page_header("Gestão de Produtos");
?>

<div class="mb-3">
  <h4 class="mb-0">Gestão de Produtos</h4>
  <div class="text-muted">Adicione, edite e ative/desative produtos do bar e buffet.</div>
</div>

<?php if ($feedback): ?>
  <div class="alert alert-<?= htmlspecialchars($feedback["type"]) ?>">
    <?= htmlspecialchars($feedback["text"]) ?>
  </div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-12 col-xl-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Novo produto</h5>
        <form method="post">
          <input type="hidden" name="action" value="create">
          <div class="mb-3">
            <label class="form-label">Nome</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Preço (€)</label>
            <input type="text" name="price" class="form-control" placeholder="Ex: 1.30" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Categoria</label>
            <input type="text" name="category" class="form-control" placeholder="meal, snack, drink">
          </div>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="is_active" id="activeNew" checked>
            <label class="form-check-label" for="activeNew">Ativo</label>
          </div>
          <button class="btn btn-primary w-100">Criar produto</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-7">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Produtos existentes</h5>
        <?php if (!$products): ?>
          <div class="alert alert-warning">Não há produtos cadastrados.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>Nome</th>
                  <th>Preço</th>
                  <th>Categoria</th>
                  <th>Status</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($products as $product): ?>
                  <tr>
                    <td><?= htmlspecialchars($product["name"]) ?></td>
                    <td><?= number_format($product["price_cents"] / 100, 2, ',', '.') ?> €</td>
                    <td><?= htmlspecialchars($product["category"]) ?></td>
                    <td>
                      <?php if ($product["is_active"]): ?>
                        <span class="badge text-bg-success">Ativo</span>
                      <?php else: ?>
                        <span class="badge text-bg-secondary">Inativo</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-nowrap">
                      <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#edit-<?= $product["id"] ?>">Editar</button>
                      <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $product["id"] ?>">
                        <button class="btn btn-sm btn-outline-<?= $product["is_active"] ? 'warning' : 'success' ?>"><?= $product["is_active"] ? 'Desativar' : 'Ativar' ?></button>
                      </form>
                    </td>
                  </tr>
                  <tr class="collapse" id="edit-<?= $product["id"] ?>">
                    <td colspan="5">
                      <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $product["id"] ?>">
                        <div class="col-12 col-md-4">
                          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product["name"]) ?>" required>
                        </div>
                        <div class="col-12 col-md-2">
                          <input type="text" name="price" class="form-control" value="<?= number_format($product["price_cents"] / 100, 2, ',', '.') ?>" required>
                        </div>
                        <div class="col-12 col-md-3">
                          <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($product["category"]) ?>">
                        </div>
                        <div class="col-12 col-md-2 d-flex align-items-center">
                          <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="active-<?= $product["id"] ?>" <?= $product["is_active"] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="active-<?= $product["id"] ?>">Ativo</label>
                          </div>
                        </div>
                        <div class="col-12 col-md-1 text-end">
                          <button class="btn btn-sm btn-primary w-100">Salvar</button>
                        </div>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php page_footer(); ?>

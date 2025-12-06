<?php
require_once '../config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php?message=' . urlencode('ID invalide') . '&type=danger');
    exit;
}

$pdo = getConnection();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php?message=' . urlencode('Utilisateur non trouvé') . '&type=danger');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'utilisateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0"><i class="bi bi-person-badge"></i> Détails de l'Utilisateur</h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3 fw-bold">ID:</div>
                            <div class="col-md-9"><?= $user['id'] ?></div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-3 fw-bold">Email:</div>
                            <div class="col-md-9"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-3 fw-bold">Rôle:</div>
                            <div class="col-md-9">
                                <span class="badge bg-<?= 
                                    $user['role'] === 'admin' ? 'danger' : 
                                    ($user['role'] === 'editor' ? 'warning' : 
                                    ($user['role'] === 'author' ? 'info' : 'secondary')) 
                                ?> fs-6">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-3 fw-bold">Créé le:</div>
                            <div class="col-md-9"><?= date('d/m/Y à H:i:s', strtotime($user['created_at'])) ?></div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-3 fw-bold">Modifié le:</div>
                            <div class="col-md-9"><?= date('d/m/Y à H:i:s', strtotime($user['updated_at'])) ?></div>
                        </div>

                        <hr>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour à la liste
                            </a>
                            <div>
                                <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-warning">
                                    <i class="bi bi-pencil"></i> Modifier
                                </a>
                                <a href="delete.php?id=<?= $user['id'] ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                    <i class="bi bi-trash"></i> Supprimer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
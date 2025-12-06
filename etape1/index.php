<?php
    require_once '../config.php';

    // Récupération de tous les utilisateurs
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT id, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();

    // Message de succès/erreur
    $message = isset($_GET['message']) ? cleanInput($_GET['message']) : '';
    $type = isset($_GET['type']) ? cleanInput($_GET['type']) : 'success';
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TP CRUD PHP/MySQL - Étape 1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col">
                <h1><i class="bi bi-people-fill"></i> Gestion des Utilisateurs</h1>
                <p class="text-muted">Étape 1 : CRUD avec fichiers séparés</p>
            </div>
            <div class="col text-end">
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nouvel Utilisateur
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $type ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-body">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Créé le</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    Aucun utilisateur trouvé
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $user['role'] === 'admin' ? 'danger' : 
                                            ($user['role'] === 'editor' ? 'warning' : 
                                            ($user['role'] === 'author' ? 'info' : 'secondary')) 
                                        ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                    <td class="text-end">
                                        <a href="view.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-info" title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete.php?id=<?= $user['id'] ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="Supprimer"
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
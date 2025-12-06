<?php
    require_once '../config.php';

    $pdo = getConnection();
    $action = $_GET['action'] ?? 'list';
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $message = '';
    $type = 'success';
    $errors = [];

    // Traitement des actions POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postAction = $_POST['action'] ?? '';
        
        switch ($postAction) {
            case 'create':
                $email = cleanInput($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                $role = cleanInput($_POST['role'] ?? 'guest');
                
                if (empty($email) || !validateEmail($email)) {
                    $errors[] = "Email invalide";
                }
                if (empty($password) || strlen($password) < 6) {
                    $errors[] = "Mot de passe invalide (min 6 caractères)";
                }
                if ($password !== $confirm_password) {
                    $errors[] = "Les mots de passe ne correspondent pas";
                }
                
                if (empty($errors)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (:email, :password, :role)");
                        $stmt->execute([
                            'email' => $email,
                            'password' => hashPassword($password),
                            'role' => $role
                        ]);
                        header('Location: index.php?message=' . urlencode('Utilisateur créé avec succès'));
                        exit;
                    } catch (PDOException $e) {
                        $errors[] = $e->getCode() == 23000 ? "Email déjà utilisé" : "Erreur de création";
                    }
                }
                $action = 'create';
                break;
                
            case 'update':
                $id = (int)$_POST['id'];
                $email = cleanInput($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                $role = cleanInput($_POST['role'] ?? 'guest');
                
                if (empty($email) || !validateEmail($email)) {
                    $errors[] = "Email invalide";
                }
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $errors[] = "Mot de passe invalide (min 6 caractères)";
                    }
                    if ($password !== $confirm_password) {
                        $errors[] = "Les mots de passe ne correspondent pas";
                    }
                }
                
                if (empty($errors)) {
                    try {
                        if (!empty($password)) {
                            $stmt = $pdo->prepare("UPDATE users SET email = :email, password = :password, role = :role WHERE id = :id");
                            $stmt->execute([
                                'email' => $email,
                                'password' => hashPassword($password),
                                'role' => $role,
                                'id' => $id
                            ]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE users SET email = :email, role = :role WHERE id = :id");
                            $stmt->execute([
                                'email' => $email,
                                'role' => $role,
                                'id' => $id
                            ]);
                        }
                        header('Location: index.php?message=' . urlencode('Utilisateur modifié avec succès'));
                        exit;
                    } catch (PDOException $e) {
                        $errors[] = $e->getCode() == 23000 ? "Email déjà utilisé" : "Erreur de modification";
                    }
                }
                $action = 'edit';
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                    $stmt->execute(['id' => $id]);
                    header('Location: index.php?message=' . urlencode('Utilisateur supprimé avec succès'));
                    exit;
                } catch (PDOException $e) {
                    header('Location: index.php?message=' . urlencode('Erreur de suppression') . '&type=danger');
                    exit;
                }
                break;
        }
    }

    // Récupération des données selon l'action
    $user = null;
    $users = [];

    switch ($action) {
        case 'list':
            $stmt = $pdo->query("SELECT id, email, role, created_at FROM users ORDER BY created_at DESC");
            $users = $stmt->fetchAll();
            $message = $_GET['message'] ?? '';
            $type = $_GET['type'] ?? 'success';
            break;
            
        case 'view':
        case 'edit':
            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $user = $stmt->fetch();
                if (!$user) {
                    header('Location: index.php?message=' . urlencode('Utilisateur non trouvé') . '&type=danger');
                    exit;
                }
            } else {
                header('Location: index.php?message=' . urlencode('ID invalide') . '&type=danger');
                exit;
            }
            break;
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TP CRUD PHP/MySQL - Étape 2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col">
                <h1><i class="bi bi-people-fill"></i> Gestion des Utilisateurs</h1>
                <p class="text-muted">Étape 2 : CRUD dans un seul fichier</p>
            </div>
            <?php if ($action === 'list'): ?>
                <div class="col text-end">
                    <a href="?action=create" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Nouvel Utilisateur
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $type ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <!-- Liste des utilisateurs -->
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
                                    <td colspan="5" class="text-center text-muted">Aucun utilisateur trouvé</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?= $u['id'] ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $u['role'] === 'admin' ? 'danger' : 
                                                ($u['role'] === 'editor' ? 'warning' : 
                                                ($u['role'] === 'author' ? 'info' : 'secondary')) 
                                            ?>">
                                                <?= ucfirst($u['role']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                                        <td class="text-end">
                                            <a href="?action=view&id=<?= $u['id'] ?>" class="btn btn-sm btn-info" title="Voir">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-warning" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($action === 'create'): ?>
            <!-- Formulaire de création -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0"><i class="bi bi-person-plus"></i> Créer un Utilisateur</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="create">
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Mot de passe *</label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           required minlength="6">
                                    <div class="form-text">Au moins 6 caractères</div>
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" required>
                                </div>

                                <div class="mb-3">
                                    <label for="role" class="form-label">Rôle *</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="guest">Guest</option>
                                        <option value="author">Author</option>
                                        <option value="editor">Editor</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="?action=list" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Retour
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Créer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($action === 'view'): ?>
            <!-- Affichage détaillé -->
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
                                <a href="?action=list" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Retour
                                </a>
                                <div>
                                    <a href="?action=edit&id=<?= $user['id'] ?>" class="btn btn-warning">
                                        <i class="bi bi-pencil"></i> Modifier
                                    </a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="bi bi-trash"></i> Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($action === 'edit'): ?>
            <!-- Formulaire de modification -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-header bg-warning">
                            <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Modifier l'Utilisateur</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="password" name="password" minlength="6">
                                    <div class="form-text">Laissez vide pour conserver le mot de passe actuel</div>
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>

                                <div class="mb-3">
                                    <label for="role" class="form-label">Rôle *</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <?php 
                                        $currentRole = $_POST['role'] ?? $user['role'];
                                        foreach (['guest', 'author', 'editor', 'admin'] as $r): 
                                        ?>
                                            <option value="<?= $r ?>" <?= $currentRole === $r ? 'selected' : '' ?>>
                                                <?= ucfirst($r) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="?action=list" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Retour
                                    </a>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-check-circle"></i> Modifier
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
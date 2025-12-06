<?php
require_once '../config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

if ($id <= 0) {
    header('Location: index.php?message=' . urlencode('ID invalide') . '&type=danger');
    exit;
}

$pdo = getConnection();

// Récupération de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php?message=' . urlencode('Utilisateur non trouvé') . '&type=danger');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = cleanInput($_POST['role'] ?? 'guest');
    
    // Validation
    if (empty($email)) {
        $errors[] = "L'email est requis";
    } elseif (!validateEmail($email)) {
        $errors[] = "L'email n'est pas valide";
    }
    
    // Validation du mot de passe seulement s'il est fourni
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Les mots de passe ne correspondent pas";
        }
    }
    
    if (!in_array($role, ['guest', 'admin', 'author', 'editor'])) {
        $errors[] = "Rôle invalide";
    }
    
    // Si pas d'erreurs, mise à jour
    if (empty($errors)) {
        try {
            if (!empty($password)) {
                // Mise à jour avec nouveau mot de passe
                $sql = "UPDATE users SET email = :email, password = :password, role = :role WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'email' => $email,
                    'password' => hashPassword($password),
                    'role' => $role,
                    'id' => $id
                ]);
            } else {
                // Mise à jour sans changer le mot de passe
                $sql = "UPDATE users SET email = :email, role = :role WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'email' => $email,
                    'role' => $role,
                    'id' => $id
                ]);
            }
            
            header('Location: index.php?message=' . urlencode('Utilisateur modifié avec succès') . '&type=success');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = "Cet email est déjà utilisé";
            } else {
                $errors[] = "Erreur lors de la modification : " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'utilisateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-warning">
                        <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Modifier l'Utilisateur</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= $error ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Nouveau mot de passe</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password"
                                       minlength="6">
                                <div class="form-text">Laissez vide pour conserver le mot de passe actuel</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password">
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Rôle *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <?php 
                                    $currentRole = $_POST['role'] ?? $user['role'];
                                    $roles = ['guest', 'author', 'editor', 'admin'];
                                    foreach ($roles as $r): 
                                    ?>
                                        <option value="<?= $r ?>" <?= $currentRole === $r ? 'selected' : '' ?>>
                                            <?= ucfirst($r) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php" class="btn btn-secondary">
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
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
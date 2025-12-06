<?php
    require_once '../config.php';

    $errors = [];
    $success = false;

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
        
        if (empty($password)) {
            $errors[] = "Le mot de passe est requis";
        } elseif (strlen($password) < 6) {
            $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
        }
        
        if ($password !== $confirm_password) {
            $errors[] = "Les mots de passe ne correspondent pas";
        }
        
        if (!in_array($role, ['guest', 'admin', 'author', 'editor'])) {
            $errors[] = "Rôle invalide";
        }
        
        // Si pas d'erreurs, insertion
        if (empty($errors)) {
            try {
                $pdo = getConnection();
                $sql = "INSERT INTO users (email, password, role) VALUES (:email, :password, :role)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'email' => $email,
                    'password' => hashPassword($password),
                    'role' => $role
                ]);
                
                header('Location: index.php?message=' . urlencode('Utilisateur créé avec succès') . '&type=success');
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $errors[] = "Cet email est déjà utilisé";
                } else {
                    $errors[] = "Erreur lors de la création : " . $e->getMessage();
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
    <title>Créer un utilisateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-person-plus"></i> Créer un Utilisateur</h4>
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
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe *</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       required
                                       minlength="6">
                                <div class="form-text">Au moins 6 caractères</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Rôle *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="guest" <?= ($_POST['role'] ?? '') === 'guest' ? 'selected' : '' ?>>Guest</option>
                                    <option value="author" <?= ($_POST['role'] ?? '') === 'author' ? 'selected' : '' ?>>Author</option>
                                    <option value="editor" <?= ($_POST['role'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor</option>
                                    <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php" class="btn btn-secondary">
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
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
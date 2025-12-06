<?php
require_once '../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getConnection();

try {
    switch ($method) {
        case 'GET':
            // Récupérer tous les utilisateurs ou un seul
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $stmt = $pdo->prepare("SELECT id, email, role, created_at, updated_at FROM users WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $user = $stmt->fetch();
                
                if ($user) {
                    echo json_encode(['success' => true, 'data' => $user]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
                }
            } else {
                $stmt = $pdo->query("SELECT id, email, role, created_at FROM users ORDER BY created_at DESC");
                $users = $stmt->fetchAll();
                echo json_encode(['success' => true, 'data' => $users]);
            }
            break;
            
        case 'POST':
            // Créer un nouvel utilisateur
            $data = json_decode(file_get_contents('php://input'), true);
            
            $email = cleanInput($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $role = cleanInput($data['role'] ?? 'guest');
            
            // Validation
            if (empty($email) || !validateEmail($email)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Email invalide']);
                break;
            }
            
            if (empty($password) || strlen($password) < 6) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Mot de passe invalide (min 6 caractères)']);
                break;
            }
            
            if (!in_array($role, ['guest', 'admin', 'author', 'editor'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Rôle invalide']);
                break;
            }
            
            try {
                $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (:email, :password, :role)");
                $stmt->execute([
                    'email' => $email,
                    'password' => hashPassword($password),
                    'role' => $role
                ]);
                
                $newId = $pdo->lastInsertId();
                $stmt = $pdo->prepare("SELECT id, email, role, created_at FROM users WHERE id = :id");
                $stmt->execute(['id' => $newId]);
                $user = $stmt->fetch();
                
                http_response_code(201);
                echo json_encode(['success' => true, 'message' => 'Utilisateur créé avec succès', 'data' => $user]);
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
                }
            }
            break;
            
        case 'PUT':
            // Modifier un utilisateur existant
            $data = json_decode(file_get_contents('php://input'), true);
            
            $id = (int)($data['id'] ?? 0);
            $email = cleanInput($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $role = cleanInput($data['role'] ?? 'guest');
            
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID invalide']);
                break;
            }
            
            // Validation
            if (empty($email) || !validateEmail($email)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Email invalide']);
                break;
            }
            
            if (!empty($password) && strlen($password) < 6) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Mot de passe invalide (min 6 caractères)']);
                break;
            }
            
            if (!in_array($role, ['guest', 'admin', 'author', 'editor'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Rôle invalide']);
                break;
            }
            
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
                
                if ($stmt->rowCount() > 0) {
                    $stmt = $pdo->prepare("SELECT id, email, role, created_at, updated_at FROM users WHERE id = :id");
                    $stmt->execute(['id' => $id]);
                    $user = $stmt->fetch();
                    
                    echo json_encode(['success' => true, 'message' => 'Utilisateur modifié avec succès', 'data' => $user]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
                }
            }
            break;
            
        case 'DELETE':
            // Supprimer un utilisateur
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? $_GET['id'] ?? 0);
            
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID invalide']);
                break;
            }
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>
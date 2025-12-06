<?php
  require_once '../config.php';

  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

  if ($id <= 0) {
      header('Location: index.php?message=' . urlencode('ID invalide') . '&type=danger');
      exit;
  }

  $pdo = getConnection();

  // Vérification que l'utilisateur existe
  $stmt = $pdo->prepare("SELECT id, email FROM users WHERE id = :id");
  $stmt->execute(['id' => $id]);
  $user = $stmt->fetch();

  if (!$user) {
      header('Location: index.php?message=' . urlencode('Utilisateur non trouvé') . '&type=danger');
      exit;
  }

  // Suppression
  try {
      $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
      $stmt->execute(['id' => $id]);
      
      header('Location: index.php?message=' . urlencode('Utilisateur supprimé avec succès') . '&type=success');
      exit;
  } catch (PDOException $e) {
      header('Location: index.php?message=' . urlencode('Erreur lors de la suppression : ' . $e->getMessage()) . '&type=danger');
      exit;
  }
?>
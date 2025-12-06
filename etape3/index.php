<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TP CRUD PHP/MySQL - Étape 3 (AJAX)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .loading {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }
        .loading.show {
            display: block;
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9998;
        }
        .overlay.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="overlay" id="overlay"></div>
    <div class="loading" id="loading">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Chargement...</span>
        </div>
    </div>

    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col">
                <h1><i class="bi bi-people-fill"></i> Gestion des Utilisateurs</h1>
                <p class="text-muted">Étape 3 : CRUD avec AJAX (sans rechargement de page)</p>
            </div>
            <div class="col text-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openCreateModal()">
                    <i class="bi bi-plus-circle"></i> Nouvel Utilisateur
                </button>
            </div>
        </div>

        <div id="alertContainer"></div>

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
                    <tbody id="usersTableBody">
                        <tr>
                            <td colspan="5" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal pour créer/modifier un utilisateur -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nouvel Utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm">
                        <input type="hidden" id="userId" value="">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe <span id="passwordRequired">*</span></label>
                            <input type="password" class="form-control" id="password" minlength="6">
                            <div class="form-text" id="passwordHelp">Au moins 6 caractères</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirmer le mot de passe <span id="confirmRequired">*</span></label>
                            <input type="password" class="form-control" id="confirmPassword">
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Rôle *</label>
                            <select class="form-select" id="role" required>
                                <option value="guest">Guest</option>
                                <option value="author">Author</option>
                                <option value="editor">Editor</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="saveUser()">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour voir les détails -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Détails de l'Utilisateur</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewModalBody">
                    <!-- Contenu dynamique -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const API_URL = 'api.php';
        let userModal;
        let viewModal;

        document.addEventListener('DOMContentLoaded', () => {
            userModal = new bootstrap.Modal(document.getElementById('userModal'));
            viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
            loadUsers();
        });

        function showLoading() {
            document.getElementById('loading').classList.add('show');
            document.getElementById('overlay').classList.add('show');
        }

        function hideLoading() {
            document.getElementById('loading').classList.remove('show');
            document.getElementById('overlay').classList.remove('show');
        }

        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertContainer.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        function getRoleBadgeClass(role) {
            const badges = {
                'admin': 'danger',
                'editor': 'warning',
                'author': 'info',
                'guest': 'secondary'
            };
            return badges[role] || 'secondary';
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        async function loadUsers() {
            try {
                const response = await fetch(API_URL);
                const result = await response.json();
                
                const tbody = document.getElementById('usersTableBody');
                
                if (result.success && result.data.length > 0) {
                    tbody.innerHTML = result.data.map(user => `
                        <tr>
                            <td>${user.id}</td>
                            <td>${user.email}</td>
                            <td>
                                <span class="badge bg-${getRoleBadgeClass(user.role)}">
                                    ${user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                                </span>
                            </td>
                            <td>${formatDate(user.created_at)}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-info" onclick="viewUser(${user.id})" title="Voir">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="editUser(${user.id})" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})" title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Aucun utilisateur trouvé</td></tr>';
                }
            } catch (error) {
                console.error('Erreur:', error);
                showAlert('Erreur lors du chargement des utilisateurs', 'danger');
            }
        }

        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Nouvel Utilisateur';
            document.getElementById('userId').value = '';
            document.getElementById('userForm').reset();
            document.getElementById('password').required = true;
            document.getElementById('confirmPassword').required = true;
            document.getElementById('passwordRequired').style.display = 'inline';
            document.getElementById('confirmRequired').style.display = 'inline';
            document.getElementById('passwordHelp').textContent = 'Au moins 6 caractères';
        }

        async function viewUser(id) {
            try {
                showLoading();
                const response = await fetch(`${API_URL}?id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    const user = result.data;
                    document.getElementById('viewModalBody').innerHTML = `
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">ID:</div>
                            <div class="col-md-8">${user.id}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Email:</div>
                            <div class="col-md-8">${user.email}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Rôle:</div>
                            <div class="col-md-8">
                                <span class="badge bg-${getRoleBadgeClass(user.role)} fs-6">
                                    ${user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                                </span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Créé le:</div>
                            <div class="col-md-8">${formatDate(user.created_at)}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Modifié le:</div>
                            <div class="col-md-8">${formatDate(user.updated_at)}</div>
                        </div>
                    `;
                    viewModal.show();
                } else {
                    showAlert(result.message, 'danger');
                }
            } catch (error) {
                console.error('Erreur:', error);
                showAlert('Erreur lors de la récupération des détails', 'danger');
            } finally {
                hideLoading();
            }
        }

        async function editUser(id) {
            try {
                showLoading();
                const response = await fetch(`${API_URL}?id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    const user = result.data;
                    document.getElementById('modalTitle').textContent = 'Modifier l\'Utilisateur';
                    document.getElementById('userId').value = user.id;
                    document.getElementById('email').value = user.email;
                    document.getElementById('password').value = '';
                    document.getElementById('confirmPassword').value = '';
                    document.getElementById('role').value = user.role;
                    
                    document.getElementById('password').required = false;
                    document.getElementById('confirmPassword').required = false;
                    document.getElementById('passwordRequired').style.display = 'none';
                    document.getElementById('confirmRequired').style.display = 'none';
                    document.getElementById('passwordHelp').textContent = 'Laissez vide pour conserver le mot de passe actuel';
                    
                    userModal.show();
                } else {
                    showAlert(result.message, 'danger');
                }
            } catch (error) {
                console.error('Erreur:', error);
                showAlert('Erreur lors de la récupération des données', 'danger');
            } finally {
                hideLoading();
            }
        }

        async function saveUser() {
            const id = document.getElementById('userId').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const role = document.getElementById('role').value;
            
            // Validation
            if (!email || !role) {
                showAlert('Veuillez remplir tous les champs requis', 'warning');
                return;
            }
            
            if (id === '') {
                // Création - mot de passe requis
                if (!password) {
                    showAlert('Le mot de passe est requis', 'warning');
                    return;
                }
            }
            
            if (password && password.length < 6) {
                showAlert('Le mot de passe doit contenir au moins 6 caractères', 'warning');
                return;
            }
            
            if (password !== confirmPassword) {
                showAlert('Les mots de passe ne correspondent pas', 'warning');
                return;
            }
            
            const data = { email, role };
            if (password) {
                data.password = password;
            }
            
            try {
                showLoading();
                let response;
                
                if (id === '') {
                    // Création
                    response = await fetch(API_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                } else {
                    // Modification
                    data.id = parseInt(id);
                    response = await fetch(API_URL, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                }
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    userModal.hide();
                    loadUsers();
                } else {
                    showAlert(result.message, 'danger');
                }
            } catch (error) {
                console.error('Erreur:', error);
                showAlert('Erreur lors de l\'enregistrement', 'danger');
            } finally {
                hideLoading();
            }
        }

        async function deleteUser(id) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
                return;
            }
            
            try {
                showLoading();
                const response = await fetch(API_URL, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    loadUsers();
                } else {
                    showAlert(result.message, 'danger');
                }
            } catch (error) {
                console.error('Erreur:', error);
                showAlert('Erreur lors de la suppression', 'danger');
            } finally {
                hideLoading();
            }
        }
    </script>
</body>
</html>
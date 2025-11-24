// assets/modules/dashboard/users.js

const apiUrl = '/dashboard/users';
let currentEditingUserId = null;

// DOM Elements
const createModal = document.getElementById('users-create-modal');
const editModal = document.getElementById('users-edit-modal');
const deleteModal = document.getElementById('users-delete-modal');
const resetPasswordModal = document.getElementById('users-reset-password-modal');

const createUserForm = document.getElementById('createUserForm');
const editUserForm = document.getElementById('editUserForm');
const resetPasswordForm = document.getElementById('resetPasswordForm');

// Helper function to get fresh CSRF token
function getCsrfToken() {
    const csrfTemplate = document.getElementById('csrf-template');
    if (csrfTemplate) {
        const input = csrfTemplate.querySelector('input[name*="csrf"]') || 
                     csrfTemplate.querySelector('input[name*="token"]');
        if (input) {
            return {
                name: input.name,
                value: input.value
            };
        }
    }
    return null;
}

// Init
document.addEventListener('DOMContentLoaded', () => {
    setupEventListeners();
    setupModalClosers();
    setupFormHandlers();
});

function setupEventListeners() {
    // Add user button
    const addUserBtn = document.getElementById('addUserBtn');
    if (addUserBtn) {
        addUserBtn.addEventListener('click', openCreateModal);
    }

    // Edit user buttons (all rows)
    document.querySelectorAll('.editBtn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const userId = btn.dataset.userId;
            openEditModal(userId);
        });
    });

    // Delete button in edit modal
    const deleteUserBtn = document.getElementById('deleteUserBtn');
    if (deleteUserBtn) {
        deleteUserBtn.addEventListener('click', openDeleteConfirmModal);
    }

    // Change password button in edit modal
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', openResetPasswordModal);
    }
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', confirmDelete);
    }
}

function setupModalClosers() {
    // Close modals on data-close button click
    document.querySelectorAll('[data-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.close;
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.close();
            }
        });
    });
}

function setupFormHandlers() {
    // Create form submission
    const submitCreateBtn = document.getElementById('submitCreateBtn');
    if (submitCreateBtn) {
        submitCreateBtn.addEventListener('click', submitCreateUser);
    }

    // Edit form submission
    const submitEditBtn = document.getElementById('submitEditBtn');
    if (submitEditBtn) {
        submitEditBtn.addEventListener('click', submitEditUser);
    }

    // Reset password form submission
    const submitResetPasswordBtn = document.getElementById('submitResetPasswordBtn');
    if (submitResetPasswordBtn) {
        submitResetPasswordBtn.addEventListener('click', submitResetPassword);
    }
}

/* ================================
   MODAL OPENING FUNCTIONS
   ================================ */

function openCreateModal() {
    createUserForm.reset();
    createUserForm.querySelector('#create_role').value = 'employee';
    
    // Update CSRF token
    const csrfToken = getCsrfToken();
    if (csrfToken) {
        let csrfInput = createUserForm.querySelector('input[name*="csrf"]') || 
                       createUserForm.querySelector('input[name*="token"]');
        if (csrfInput) {
            csrfInput.value = csrfToken.value;
        }
    }
    
    createModal.showModal();
}

function openEditModal(userId) {
    currentEditingUserId = userId;
    
    // Find user data from table
    const row = document.querySelector(`tr[data-user-id="${userId}"]`);
    if (!row) return;

    const id = row.querySelector('.col-id')?.textContent?.trim();
    const username = row.querySelector('.col-name')?.textContent?.trim();
    const email = row.querySelector('.col-email')?.textContent?.trim();
    const role = row.querySelector('.col-role')?.textContent?.trim();

    // Update modal header with username
    document.getElementById('user-name').textContent = username ?? '';

    // Populate form
    editUserForm.querySelector('#edit_userId').value = id ?? '';
    editUserForm.querySelector('#edit_username').value = username;
    editUserForm.querySelector('#edit_email').value = email;
    editUserForm.querySelector('#edit_role').value = role;

    // Update CSRF token
    const csrfToken = getCsrfToken();
    if (csrfToken) {
        let csrfInput = editUserForm.querySelector('input[name*="csrf"]') || 
                       editUserForm.querySelector('input[name*="token"]');
        if (csrfInput) {
            csrfInput.value = csrfToken.value;
        }
    }

    editModal.showModal();
}

function openDeleteConfirmModal() {
    if (!currentEditingUserId) return;

    const row = document.querySelector(`tr[data-user-id="${currentEditingUserId}"]`);
    if (!row) return;

    const username = row.querySelector('.col-name')?.textContent?.trim();
    document.getElementById('delete-user-name').textContent = username;

    editModal.close();
    deleteModal.showModal();
}

function openResetPasswordModal() {
    if (!currentEditingUserId) return;

    const row = document.querySelector(`tr[data-user-id="${currentEditingUserId}"]`);
    if (!row) return;

    const username = row.querySelector('.col-name')?.textContent?.trim();
    document.getElementById('reset-user-name').textContent = username;
    document.getElementById('reset_userId').value = currentEditingUserId;
    resetPasswordForm.querySelector('#reset_new_password').value = '';
    resetPasswordForm.querySelector('#reset_confirm_password').value = '';

    // Update CSRF token
    const csrfToken = getCsrfToken();
    if (csrfToken) {
        let csrfInput = resetPasswordForm.querySelector('input[name*="csrf"]') || 
                       resetPasswordForm.querySelector('input[name*="token"]');
        if (csrfInput) {
            csrfInput.value = csrfToken.value;
        }
    }

    editModal.close();
    resetPasswordModal.showModal();
}

/* ================================
   FORM SUBMISSION FUNCTIONS
   ================================ */

function submitCreateUser() {
    const username = createUserForm.querySelector('#create_username').value?.trim();
    const email = createUserForm.querySelector('#create_email').value?.trim();
    const password = createUserForm.querySelector('#create_password').value?.trim();
    const role = createUserForm.querySelector('#create_role').value;

    if (!username || !email || !password) {
        alert('Veuillez remplir tous les champs obligatoires.');
        return;
    }

    // Create a fresh form with current CSRF token
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/dashboard/users/create';

    // Add fresh CSRF token
    const csrfToken = getCsrfToken();
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = csrfToken.name;
        csrfInput.value = csrfToken.value;
        form.appendChild(csrfInput);
    }

    // Add form data
    ['username', 'email', 'password', 'role'].forEach(field => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = field;
        if (field === 'username') input.value = username;
        else if (field === 'email') input.value = email;
        else if (field === 'password') input.value = password;
        else if (field === 'role') input.value = role;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}

function submitEditUser() {
    const username = editUserForm.querySelector('#edit_username').value?.trim();
    const email = editUserForm.querySelector('#edit_email').value?.trim();
    const role = editUserForm.querySelector('#edit_role').value;
    const id = editUserForm.querySelector('#edit_userId').value;

    if (!username || !email) {
        alert('Veuillez remplir tous les champs obligatoires.');
        return;
    }

    // Create a fresh form with current CSRF token
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/dashboard/users/update';

    // Add fresh CSRF token
    const csrfToken = getCsrfToken();
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = csrfToken.name;
        csrfInput.value = csrfToken.value;
        form.appendChild(csrfInput);
    }

    // Add form data
    ['id', 'username', 'email', 'role'].forEach(field => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = field;
        if (field === 'id') input.value = id;
        else if (field === 'username') input.value = username;
        else if (field === 'email') input.value = email;
        else if (field === 'role') input.value = role;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}

function confirmDelete() {
    if (!currentEditingUserId) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/dashboard/users/delete';

    // Add fresh CSRF token
    const csrfToken = getCsrfToken();
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = csrfToken.name;
        csrfInput.value = csrfToken.value;
        form.appendChild(csrfInput);
    }

    // Add user ID
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'user_ids[]';
    input.value = currentEditingUserId;
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
}

function submitResetPassword() {
    const newPassword = resetPasswordForm.querySelector('#reset_new_password').value.trim();
    const confirmPassword = resetPasswordForm.querySelector('#reset_confirm_password').value.trim();

    if (!newPassword) {
        alert('Veuillez entrer un mot de passe.');
        return;
    }

    if (newPassword !== confirmPassword) {
        alert('Les mots de passe ne correspondent pas.');
        return;
    }

    if (newPassword.length < 6) {
        alert('Le mot de passe doit contenir au moins 6 caractères.');
        return;
    }

    // Create form for password reset
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/dashboard/users/reset-password';

    // Add fresh CSRF token
    const csrfToken = getCsrfToken();
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = csrfToken.name;
        csrfInput.value = csrfToken.value;
        form.appendChild(csrfInput);
    }

    // Add user ID and password
    const userIdInput = document.createElement('input');
    userIdInput.type = 'hidden';
    userIdInput.name = 'id';
    userIdInput.value = currentEditingUserId;
    form.appendChild(userIdInput);

    const passwordInput = document.createElement('input');
    passwordInput.type = 'hidden';
    passwordInput.name = 'password';
    passwordInput.value = newPassword;
    form.appendChild(passwordInput);

    document.body.appendChild(form);
    form.submit();
}

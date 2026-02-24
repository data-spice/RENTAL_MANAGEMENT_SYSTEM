// Role selection
const roleButtons = document.querySelectorAll('.role-btn');
const registerSection = document.querySelector('.register-section'); // Get the register section
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const forgotPassword = document.getElementById('forgotPassword');
const toggleText = document.getElementById('toggleText');
const toggleFormLink = document.getElementById('toggleForm');
const formTitle = document.getElementById('formTitle');
let isLoginForm = true;
let selectedRole = 'tenant';

const phoneNumberGroup = document.getElementById('phone-number-group');

function updateFormVisibility(role) {
    if (role === 'tenant') {
        registerForm.classList.add('hidden');
        registerSection.classList.add('hidden'); // Hide the entire register section
        forgotPassword.classList.remove('hidden'); // Ensure forgot password is visible for login
        loginForm.classList.remove('hidden'); // Ensure login form is visible
        isLoginForm = true; // Force to login view
        formTitle.textContent = 'Welcome to RMS';
        phoneNumberGroup.classList.add('hidden');
    } else {
        registerSection.classList.remove('hidden'); // Show register section for other roles
        // Re-evaluate current form state for non-tenant roles
        if (isLoginForm) {
            loginForm.classList.remove('hidden');
            registerForm.classList.add('hidden');
            forgotPassword.classList.remove('hidden');
            toggleText.textContent = "Don't have an account?";
            toggleFormLink.textContent = 'Register';
            formTitle.textContent = 'Welcome to RMS';
        } else {
            loginForm.classList.add('hidden');
            registerForm.classList.remove('hidden');
            forgotPassword.classList.add('hidden');
            toggleText.textContent = 'Already have an account?';
            toggleFormLink.textContent = 'Login';
            formTitle.textContent = 'Create Account';
        }

        if (role === 'caretaker') {
            phoneNumberGroup.classList.remove('hidden');
        } else {
            phoneNumberGroup.classList.add('hidden');
        }
    }
}

roleButtons.forEach(button => {
    button.addEventListener('click', function() {
        roleButtons.forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        selectedRole = this.dataset.role;
        document.getElementById('loginRole').value = selectedRole;
        document.getElementById('registerRole').value = selectedRole;
        updateFormVisibility(selectedRole);
    });
});

// Initial state setup
// Check selectedRole after initialization. Assume 'tenant' is default if not set
if (document.getElementById('loginRole').value === 'tenant') {
    selectedRole = 'tenant';
} else {
    selectedRole = 'landlord'; // Or whatever your default non-tenant role is
}
updateFormVisibility(selectedRole);

// Toggle between login and register forms
toggleFormLink.addEventListener('click', function(e) {
    e.preventDefault();
    
    if (isLoginForm) {
        loginForm.classList.add('hidden');
        registerForm.classList.remove('hidden');
        forgotPassword.classList.add('hidden');
        toggleText.textContent = 'Already have an account?';
        toggleFormLink.textContent = 'Login';
        formTitle.textContent = 'Create Account';
    } else {
        registerForm.classList.add('hidden');
        loginForm.classList.remove('hidden');
        forgotPassword.classList.remove('hidden');
        toggleText.textContent = "Don't have an account?";
        toggleFormLink.textContent = 'Register';
        formTitle.textContent = 'Welcome to RMS';
    }
    
    isLoginForm = !isLoginForm;
});

// Handle URL parameters for messages
const urlParams = new URLSearchParams(window.location.search);
const toast = document.getElementById('toast-notification');

if (urlParams.has('success')) {
    showToast(urlParams.get('success'), 'success');
}
if (urlParams.has('error')) {
    showToast(urlParams.get('error'), 'error');
}

function showToast(message, type) {
    toast.textContent = decodeURIComponent(message);
    toast.className = 'toast-notification show ' + (type === 'error' ? 'error' : '');

    setTimeout(() => {
        toast.className = toast.className.replace('show', '');
    }, 3000);
}

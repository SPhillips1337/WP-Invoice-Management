(function() {
    'use strict';

    function init() {
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const messageBox = document.getElementById('authMessageBox');
        const subtitle = document.getElementById('authSubtitle');

        const showRegisterLink = document.getElementById('showRegisterLink');
        const showLoginLink = document.getElementById('showLoginLink');

        if (!loginForm && !registerForm) return;

        function showMessage(message, isError = true) {
            messageBox.replaceChildren();
            messageBox.textContent = message;
            messageBox.className = 'auth-message-box ' + (isError ? 'error' : 'success');
            messageBox.style.display = 'block';
        }

        function hideMessage() {
            messageBox.style.display = 'none';
            messageBox.replaceChildren();
        }

        if (showRegisterLink && registerForm) {
            showRegisterLink.addEventListener('click', (e) => {
                e.preventDefault();
                hideMessage();
                loginForm.style.display = 'none';
                registerForm.style.display = 'flex';
                subtitle.textContent = 'Create an account to start managing invoices';
            });
        }

        if (showLoginLink && registerForm) {
            showLoginLink.addEventListener('click', (e) => {
                e.preventDefault();
                hideMessage();
                registerForm.style.display = 'none';
                loginForm.style.display = 'flex';
                subtitle.textContent = 'Log in to manage your invoices';
            });
        }

        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                hideMessage();

                const username = loginForm.querySelector('[name="username"]').value.trim();
                const password = loginForm.querySelector('[name="password"]').value;
                const submitBtn = document.getElementById('loginSubmitBtn');

                if (!username || !password) {
                    showMessage('Please fill in all fields.');
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.textContent = 'Logging in...';

                try {
                    const response = await fetch(WP_INVOICE_API.root + '/auth/login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': WP_INVOICE_API.nonce
                        },
                        body: JSON.stringify({ username, password })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Login failed.');
                    }

                    showMessage('Success! Redirecting...', false);
                    window.location.href = WP_INVOICE_API.redirectUrl;
                } catch (err) {
                    showMessage(err.message || 'An error occurred during login.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Log In';
                }
            });
        }

        if (registerForm) {
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                hideMessage();

                const username = registerForm.querySelector('[name="username"]').value.trim();
                const email = registerForm.querySelector('[name="email"]').value.trim();
                const password = registerForm.querySelector('[name="password"]').value;
                const confirmPassword = registerForm.querySelector('[name="confirm_password"]').value;
                const submitBtn = document.getElementById('registerSubmitBtn');

                if (!username || !email || !password || !confirmPassword) {
                    showMessage('All registration fields are required.');
                    return;
                }

                if (password.length < 8) {
                    showMessage('Password must be at least 8 characters long.');
                    return;
                }

                if (password !== confirmPassword) {
                    showMessage('Passwords do not match.');
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.textContent = 'Registering...';

                try {
                    const response = await fetch(WP_INVOICE_API.root + '/auth/register', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': WP_INVOICE_API.nonce
                        },
                        body: JSON.stringify({
                            username,
                            email,
                            password,
                            confirm_password: confirmPassword
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Registration failed.');
                    }

                    showMessage('Registration successful! Redirecting...', false);
                    window.location.href = WP_INVOICE_API.redirectUrl;
                } catch (err) {
                    showMessage(err.message || 'An error occurred during registration.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Register';
                }
            });
        }

        // Check hash or URL action parameter to show registration form directly
        const urlParams = new URLSearchParams(window.location.search);
        if (window.location.hash === '#register' || urlParams.get('action') === 'register') {
            if (showRegisterLink && registerForm) {
                hideMessage();
                loginForm.style.display = 'none';
                registerForm.style.display = 'flex';
                subtitle.textContent = 'Create an account to start managing invoices';
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

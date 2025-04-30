import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Create a global Alpine store for auth
Alpine.store('auth', {
    isAuthenticated: window.isAuthenticated
});

Alpine.start();

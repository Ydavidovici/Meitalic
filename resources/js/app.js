import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// — Auth store —
Alpine.store('auth', {
    isAuthenticated: window.isAuthenticated
});

// — Dashboard store —
Alpine.store('dashboard', {
    devMetricsVisible: false,
    activeModal:      null,

    toggleDevMetrics() {
        this.devMetricsVisible = ! this.devMetricsVisible;
    },
    openModal(name) {
        this.activeModal = name;
        window.dispatchEvent(new CustomEvent('open-modal',{ detail: name }));
    },
    closeModal(name) {
        if (this.activeModal === name) {
            this.activeModal = null;
            window.dispatchEvent(new CustomEvent('close-modal',{ detail: name }));
        }
    }
});

// — Admin component —
function adminDashboard() {
    return {
        devMetricsVisible: Alpine.store('dashboard').devMetricsVisible,
        openModal(name)    { Alpine.store('dashboard').openModal(name) },
        closeModal(name)   { Alpine.store('dashboard').closeModal(name) },
        selectedOrders:    [],
        dateFilter:        'all',
        statusFilter:      [],

        matchesDate(range) {
            return this.dateFilter === 'all' || range === this.dateFilter;
        },
        matchesStatus(status) {
            return this.statusFilter.length === 0 || this.statusFilter.includes(status);
        },
        toggleAll(e) {
            const checked = e.target.checked;
            this.selectedOrders = checked
                ? Array.from(document.querySelectorAll('tbody input[type="checkbox"]')).map(el => el.value)
                : [];
        },
        singleMark(id,status) {
            fetch(`/admin/orders/${id}/status`, {
                method:'PATCH',
                headers:{
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status })
            }).then(() => location.reload());
        },
        markBulk(status) {
            if (! this.selectedOrders.length) return;
            fetch('/admin/orders/bulk-update', {
                method:'POST',
                headers:{
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ ids:this.selectedOrders, status })
            }).then(() => location.reload());
        }
    };
}

Alpine.data('adminDashboard', adminDashboard);
Alpine.start();

// — global form validator + submitter —
window.validateAndSubmit = function(formEl) {
    const required = formEl.querySelectorAll('[required]');
    for (const field of required) {
        if (! String(field.value).trim()) {
            const label = formEl.querySelector(`label[for="${field.id}"]`)?.innerText || field.name;
            alert(`${label} is required.`);
            field.focus();
            return;
        }
    }
    formEl.submit();
};

// — AJAX filters —
document.addEventListener('DOMContentLoaded', () => {
    ['filters-form','admin-filters-form'].forEach(formId => {
        const form = document.getElementById(formId);
        if (! form) return;

        form.addEventListener('submit', async e => {
            e.preventDefault();
            const params = new URLSearchParams(new FormData(form));
            const url    = formId === 'filters-form' ? `/products?${params}` : `/admin?${params}`;
            const resp   = await fetch(url, { headers:{ 'X-Requested-With':'XMLHttpRequest' } });
            const html   = await resp.text();
            const parser = new DOMParser();
            const doc    = parser.parseFromString(html,'text/html');
            const newGrid= doc.getElementById(formId==='filters-form'?'product-grid':'admin-product-grid');
            if (newGrid) {
                const old = document.getElementById(newGrid.id);
                old.replaceWith(newGrid);
                window.Alpine.initTree(newGrid);
                history.pushState(null,'',url);
            }
        });
    });
});

import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.store('auth', {
    isAuthenticated: window.isAuthenticated
});

// add modal support
Alpine.store('dashboard', {
    devMetricsVisible: false,
    activeModal: null,

    toggleDevMetrics() {
        this.devMetricsVisible = ! this.devMetricsVisible;
    },
    openModal(name) {
        this.activeModal = name;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: name }));
    },
    closeModal(name) {
        if (this.activeModal === name) {
            this.activeModal = null;
            window.dispatchEvent(new CustomEvent('close-modal', { detail: name }));
        }
    }
});

function adminDashboard() {
    return {
        // from store
        devMetricsVisible: Alpine.store('dashboard').devMetricsVisible,

        // KPI helpers
        openKpi(name) {
            Alpine.store('dashboard').openModal(name);
        },
        closeKpi(name) {
            Alpine.store('dashboard').closeModal(name);
        },

        // existing order mgmt...
        selectedOrders: [],
        dateFilter: 'all',
        statusFilter: [],

        matchesDate(orderRange) {
            return this.dateFilter === 'all' || orderRange === this.dateFilter;
        },
        matchesStatus(orderStatus) {
            return this.statusFilter.length === 0 || this.statusFilter.includes(orderStatus);
        },
        toggleAll(event) {
            const checked = event.target.checked;
            this.selectedOrders = checked
                ? Array.from(document.querySelectorAll('tbody input[type="checkbox"]')).map(el => el.value)
                : [];
        },
        singleMark(id, status) {
            fetch(`/admin/orders/${id}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status })
            }).then(() => location.reload());
        },
        markBulk(status) {
            if (!this.selectedOrders.length) return;
            fetch('/admin/orders/bulk-update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    ids: this.selectedOrders,
                    status
                })
            }).then(() => location.reload());
        }
    };
}

Alpine.data('adminDashboard', adminDashboard);
Alpine.start();

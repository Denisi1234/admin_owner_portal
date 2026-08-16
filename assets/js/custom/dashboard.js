document.addEventListener('DOMContentLoaded', function() {
    const config = window.DASHBOARD_CONFIG || {};
    const apiToken = config.apiToken || '';
    const userRole = config.userRole || 'admin';

    const rangeSelect = document.getElementById('dash-date-range');
    const customContainer = document.getElementById('dash-custom-range');
    const btnApplyCustom = document.getElementById('btn-dash-apply-range');

    function formatCurrency(amount) {
        return 'TSh ' + Math.round(amount || 0).toLocaleString();
    }

    if (rangeSelect) {
        rangeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                if (customContainer) customContainer.classList.remove('d-none');
            } else {
                if (customContainer) customContainer.classList.add('d-none');
                fetchDashboardStats(this.value);
            }
        });
    }

    if (btnApplyCustom) {
        btnApplyCustom.addEventListener('click', function() {
            const from = document.getElementById('dash-date-from').value;
            const to = document.getElementById('dash-date-to').value;
            if (!from || !to) {
                alert('Please select both From and To dates.');
                return;
            }
            fetchDashboardStats('custom', from, to);
        });
    }

    async function fetchDashboardStats(range = '30_days', dateFrom = null, dateTo = null) {
        let endpoint = 'http://127.0.0.1:8000/api/admin/dashboard-stats';
        if (userRole !== 'admin') {
            endpoint = 'http://127.0.0.1:8000/api/finance/overview';
        }

        const url = new URL(endpoint);
        url.searchParams.append('date_range', range);
        if (range === 'custom') {
            if (dateFrom) url.searchParams.append('date_from', dateFrom);
            if (dateTo) url.searchParams.append('date_to', dateTo);
        }

        try {
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${apiToken}`
                }
            });
            if (!res.ok) throw new Error('Backend dashboard stats error');
            const json = await res.json();

            if (userRole === 'admin') {
                const o = json.owners || {};
                const l = json.lodges || {};
                const r = json.rooms || {};
                const c = json.customers || {};
                const b = json.bookings || {};
                const f = json.financials || {};

                const totalOwnersEl = document.getElementById('stat-total-owners');
                if (totalOwnersEl) totalOwnersEl.textContent = (o.total || 0).toLocaleString();
                const activeOwnersEl = document.getElementById('stat-active-owners');
                if (activeOwnersEl) activeOwnersEl.textContent = (o.active || 0).toLocaleString();
                const suspendedOwnersEl = document.getElementById('stat-suspended-owners');
                if (suspendedOwnersEl) suspendedOwnersEl.textContent = (o.suspended || 0).toLocaleString();

                const totalLodgesEl = document.getElementById('stat-total-lodges');
                if (totalLodgesEl) totalLodgesEl.textContent = (l.total || 0).toLocaleString();
                const approvedLodgesEl = document.getElementById('stat-approved-lodges');
                if (approvedLodgesEl) approvedLodgesEl.textContent = (l.approved || 0).toLocaleString();
                const pendingLodgesEl = document.getElementById('stat-pending-lodges');
                if (pendingLodgesEl) pendingLodgesEl.textContent = (l.pending || 0).toLocaleString();

                const totalRoomsEl = document.getElementById('stat-total-rooms');
                if (totalRoomsEl) totalRoomsEl.textContent = (r.total || 0).toLocaleString();
                const totalCustomersEl = document.getElementById('stat-total-customers');
                if (totalCustomersEl) totalCustomersEl.textContent = (c.total || 0).toLocaleString();

                const totalBookingsEl = document.getElementById('stat-total-bookings');
                if (totalBookingsEl) totalBookingsEl.textContent = (b.total || 0).toLocaleString();
                const confirmedBookingsEl = document.getElementById('stat-confirmed-bookings');
                if (confirmedBookingsEl) confirmedBookingsEl.textContent = (b.confirmed || 0).toLocaleString();
                const cancelledBookingsEl = document.getElementById('stat-cancelled-bookings');
                if (cancelledBookingsEl) cancelledBookingsEl.textContent = (b.cancelled || 0).toLocaleString();

                const grossValueEl = document.getElementById('stat-gross-value');
                if (grossValueEl) grossValueEl.textContent = formatCurrency(f.gross_booking_value);
                const platformFeeEl = document.getElementById('stat-platform-fee');
                if (platformFeeEl) platformFeeEl.textContent = formatCurrency(f.platform_commission);
                const ownerNetEl = document.getElementById('stat-owner-net');
                if (ownerNetEl) ownerNetEl.textContent = formatCurrency(f.owner_earnings);
            } else {
                const cards = json.summary_cards || {};
                const grossValueEl = document.getElementById('stat-gross-value');
                if (grossValueEl) grossValueEl.textContent = formatCurrency(cards.gross_booking_value);
                const platformFeeEl = document.getElementById('stat-platform-fee');
                if (platformFeeEl) platformFeeEl.textContent = formatCurrency(cards.platform_commission);
                const ownerNetEl = document.getElementById('stat-owner-net');
                if (ownerNetEl) ownerNetEl.textContent = formatCurrency(cards.total_owner_earnings);
            }
        } catch (e) {
            console.error('Failed to load dashboard metrics from backend', e);
        }
    }

    // Initial load
    fetchDashboardStats('30_days');
});

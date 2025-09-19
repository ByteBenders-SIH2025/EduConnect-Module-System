/**
 * Identity Card Module JavaScript
 * Handles all client-side functionality for the Identity Card management system
 */

class IdentityCardManager {
    constructor() {
        this.currentPage = 1;
        this.currentFilters = {};
        this.selectedCards = [];
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadStudents();
        this.initializeDataTable();
    }

    bindEvents() {
        // Search and filter events
        document.getElementById('search')?.addEventListener('input', this.debounce(this.handleSearch.bind(this), 300));
        document.getElementById('status')?.addEventListener('change', this.handleFilterChange.bind(this));
        document.getElementById('card_type')?.addEventListener('change', this.handleFilterChange.bind(this));

        // Modal events
        document.addEventListener('click', this.handleModalClick.bind(this));
        document.addEventListener('keydown', this.handleKeydown.bind(this));

        // Form events
        document.getElementById('cardForm')?.addEventListener('submit', this.handleFormSubmit.bind(this));
        document.getElementById('editForm')?.addEventListener('submit', this.handleEditSubmit.bind(this));

        // Bulk actions
        document.getElementById('selectAll')?.addEventListener('change', this.toggleSelectAll.bind(this));
    }

    // Event Handlers
    handleSearch(event) {
        this.currentFilters.search = event.target.value;
        this.applyFilters();
    }

    handleFilterChange(event) {
        this.currentFilters[event.target.id] = event.target.value;
        this.applyFilters();
    }

    handleModalClick(event) {
        if (event.target.classList.contains('modal')) {
            this.closeModal(event.target);
        }
    }

    handleKeydown(event) {
        if (event.key === 'Escape') {
            const openModal = document.querySelector('.modal[style*="block"]');
            if (openModal) {
                this.closeModal(openModal);
            }
        }
    }

    handleFormSubmit(event) {
        event.preventDefault();
        this.saveCard();
    }

    handleEditSubmit(event) {
        event.preventDefault();
        this.saveEdit();
    }

    // API Methods
    async fetchCards(page = 1, filters = {}) {
        try {
            const params = new URLSearchParams({
                page: page,
                limit: 10,
                ...filters
            });

            const response = await fetch(`/api/v1/identity-cards?${params}`);
            const data = await response.json();

            if (data.success) {
                this.updateTable(data.data);
                this.updatePagination(data.pagination);
                this.currentPage = page;
            } else {
                this.showNotification('Failed to fetch cards: ' + data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error fetching cards: ' + error.message, 'error');
        }
    }

    async createCard(cardData) {
        try {
            const response = await fetch('/api/v1/identity-cards', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(cardData)
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Identity card created successfully!', 'success');
                this.closeModal();
                this.refreshTable();
            } else {
                this.showNotification('Failed to create card: ' + data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error creating card: ' + error.message, 'error');
        }
    }

    async updateCard(id, cardData) {
        try {
            const response = await fetch(`/api/v1/identity-cards/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(cardData)
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Identity card updated successfully!', 'success');
                this.closeModal();
                this.refreshTable();
            } else {
                this.showNotification('Failed to update card: ' + data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error updating card: ' + error.message, 'error');
        }
    }

    async deleteCard(id) {
        if (!confirm('Are you sure you want to delete this identity card?')) {
            return;
        }

        try {
            const response = await fetch(`/api/v1/identity-cards/${id}`, {
                method: 'DELETE'
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Identity card deleted successfully!', 'success');
                this.refreshTable();
            } else {
                this.showNotification('Failed to delete card: ' + data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error deleting card: ' + error.message, 'error');
        }
    }

    async generateCard(id) {
        try {
            this.showLoading();
            
            const response = await fetch(`/api/v1/identity-cards/${id}/generate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Identity card generated successfully!', 'success');
                // Open download link
                window.open(data.data.download_url, '_blank');
                this.refreshTable();
            } else {
                this.showNotification('Failed to generate card: ' + data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error generating card: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    async loadStudents() {
        try {
            const response = await fetch('/api/v1/students');
            const data = await response.json();

            if (data.success) {
                this.populateStudentSelect(data.data);
            }
        } catch (error) {
            console.error('Error loading students:', error);
        }
    }

    // UI Methods
    openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Create Identity Card';
        document.getElementById('cardForm').reset();
        document.getElementById('cardId').value = '';
        document.getElementById('cardModal').style.display = 'block';
    }

    openEditModal(cardId) {
        this.loadCardForEdit(cardId);
        document.getElementById('modalTitle').textContent = 'Edit Identity Card';
        document.getElementById('cardModal').style.display = 'block';
    }

    closeModal(modal = null) {
        if (modal) {
            modal.style.display = 'none';
        } else {
            const openModal = document.querySelector('.modal[style*="block"]');
            if (openModal) {
                openModal.style.display = 'none';
            }
        }
    }

    viewCard(id) {
        window.location.href = `/modules/identity-cards/view/${id}`;
    }

    editCard(id) {
        window.location.href = `/modules/identity-cards/edit/${id}`;
    }

    generateCard(id) {
        window.location.href = `/modules/identity-cards/generate/${id}`;
    }

    deleteCard(id) {
        this.deleteCard(id);
    }

    // Form Methods
    saveCard() {
        const formData = new FormData(document.getElementById('cardForm'));
        const cardData = Object.fromEntries(formData);

        // Validate required fields
        if (!cardData.student_id || !cardData.card_type || !cardData.valid_from || !cardData.valid_until) {
            this.showNotification('Please fill in all required fields', 'error');
            return;
        }

        // Validate dates
        if (new Date(cardData.valid_from) >= new Date(cardData.valid_until)) {
            this.showNotification('Valid until date must be after valid from date', 'error');
            return;
        }

        this.createCard(cardData);
    }

    saveEdit() {
        const formData = new FormData(document.getElementById('editForm'));
        const cardData = Object.fromEntries(formData);
        const cardId = document.getElementById('editCardId').value;

        this.updateCard(cardId, cardData);
    }

    async loadCardForEdit(cardId) {
        try {
            const response = await fetch(`/api/v1/identity-cards/${cardId}`);
            const data = await response.json();

            if (data.success) {
                const card = data.data;
                document.getElementById('editCardId').value = card.id;
                document.getElementById('editCardType').value = card.card_type;
                document.getElementById('editStatus').value = card.status;
                document.getElementById('editValidFrom').value = card.valid_from;
                document.getElementById('editValidUntil').value = card.valid_until;
                document.getElementById('editNotes').value = card.notes || '';
            }
        } catch (error) {
            this.showNotification('Error loading card data: ' + error.message, 'error');
        }
    }

    // Filter and Search Methods
    applyFilters() {
        this.currentPage = 1;
        this.fetchCards(this.currentPage, this.currentFilters);
    }

    clearFilters() {
        this.currentFilters = {};
        document.getElementById('search').value = '';
        document.getElementById('status').value = '';
        document.getElementById('card_type').value = '';
        this.applyFilters();
    }

    changePage(page) {
        this.fetchCards(page, this.currentFilters);
    }

    // Table Methods
    initializeDataTable() {
        // Initialize any data table functionality
        this.fetchCards();
    }

    updateTable(cards) {
        const tbody = document.querySelector('#identityCardsTable tbody');
        if (!tbody) return;

        if (cards.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="no-data">
                        <div class="no-data-content">
                            <i class="fas fa-id-card"></i>
                            <p>No identity cards found</p>
                            <button class="btn btn-primary" onclick="identityCardManager.openCreateModal()">
                                Create First Card
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = cards.map(card => this.createTableRow(card)).join('');
    }

    createTableRow(card) {
        const isExpired = new Date(card.valid_until) < new Date();
        const isExpiringSoon = new Date(card.valid_until) < new Date(Date.now() + 30 * 24 * 60 * 60 * 1000);
        
        return `
            <tr data-card-id="${card.id}">
                <td>
                    <input type="checkbox" class="card-checkbox" value="${card.id}" onchange="identityCardManager.toggleCardSelection(${card.id})">
                </td>
                <td>
                    <div class="card-number">
                        <strong>${this.escapeHtml(card.card_number)}</strong>
                    </div>
                </td>
                <td>
                    <div class="student-info">
                        <div class="student-avatar">
                            ${card.student_photo ? 
                                `<img src="${this.escapeHtml(card.student_photo)}" alt="${this.escapeHtml(card.student_name)}">` :
                                `<div class="avatar-placeholder">${this.getInitials(card.student_name)}</div>`
                            }
                        </div>
                        <div class="student-details">
                            <strong>${this.escapeHtml(card.student_name)}</strong>
                            <small>${this.escapeHtml(card.student_email)}</small>
                        </div>
                    </div>
                </td>
                <td>${this.escapeHtml(card.student_number)}</td>
                <td>
                    <span class="class-badge">
                        ${this.escapeHtml(card.class_name || 'N/A')}
                    </span>
                </td>
                <td>
                    <span class="type-badge type-${card.card_type}">
                        ${this.capitalize(card.card_type)}
                    </span>
                </td>
                <td>
                    <span class="status-badge status-${card.status}">
                        ${this.capitalize(card.status)}
                    </span>
                </td>
                <td>${this.formatDate(card.valid_from)}</td>
                <td>
                    <span class="validity-date ${isExpired ? 'expired' : (isExpiringSoon ? 'expiring-soon' : '')}">
                        ${this.formatDate(card.valid_until)}
                        ${isExpired ? '<i class="fas fa-exclamation-triangle" title="Expired"></i>' : 
                          (isExpiringSoon ? '<i class="fas fa-clock" title="Expiring Soon"></i>' : '')}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-info" onclick="identityCardManager.viewCard(${card.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-success" onclick="identityCardManager.generateCard(${card.id})" 
                                title="Generate PDF" ${card.status === 'generated' ? 'disabled' : ''}>
                            <i class="fas fa-file-pdf"></i>
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="identityCardManager.editCard(${card.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="identityCardManager.deleteCard(${card.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    updatePagination(pagination) {
        const paginationContainer = document.querySelector('.pagination');
        if (!paginationContainer || pagination.total_pages <= 1) {
            if (paginationContainer) {
                paginationContainer.style.display = 'none';
            }
            return;
        }

        paginationContainer.style.display = 'flex';
        
        const infoElement = paginationContainer.querySelector('.pagination-info');
        const controlsElement = paginationContainer.querySelector('.pagination-controls');

        if (infoElement) {
            const start = ((pagination.current_page - 1) * pagination.per_page) + 1;
            const end = Math.min(pagination.current_page * pagination.per_page, pagination.total);
            infoElement.textContent = `Showing ${start} to ${end} of ${pagination.total} entries`;
        }

        if (controlsElement) {
            let controlsHTML = `
                <button class="btn btn-sm" onclick="identityCardManager.changePage(1)" 
                        ${pagination.current_page == 1 ? 'disabled' : ''}>
                    <i class="fas fa-angle-double-left"></i>
                </button>
                <button class="btn btn-sm" onclick="identityCardManager.changePage(${pagination.current_page - 1})"
                        ${!pagination.has_prev ? 'disabled' : ''}>
                    <i class="fas fa-angle-left"></i>
                </button>
            `;

            const startPage = Math.max(1, pagination.current_page - 2);
            const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

            for (let i = startPage; i <= endPage; i++) {
                controlsHTML += `
                    <button class="btn btn-sm ${i == pagination.current_page ? 'active' : ''}" 
                            onclick="identityCardManager.changePage(${i})">
                        ${i}
                    </button>
                `;
            }

            controlsHTML += `
                <button class="btn btn-sm" onclick="identityCardManager.changePage(${pagination.current_page + 1})"
                        ${!pagination.has_next ? 'disabled' : ''}>
                    <i class="fas fa-angle-right"></i>
                </button>
                <button class="btn btn-sm" onclick="identityCardManager.changePage(${pagination.total_pages})"
                        ${pagination.current_page == pagination.total_pages ? 'disabled' : ''}>
                    <i class="fas fa-angle-double-right"></i>
                </button>
            `;

            controlsElement.innerHTML = controlsHTML;
        }
    }

    // Selection Methods
    toggleSelectAll() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const cardCheckboxes = document.querySelectorAll('.card-checkbox');
        
        cardCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
            this.toggleCardSelection(parseInt(checkbox.value));
        });
    }

    toggleCardSelection(cardId) {
        const index = this.selectedCards.indexOf(cardId);
        if (index > -1) {
            this.selectedCards.splice(index, 1);
        } else {
            this.selectedCards.push(cardId);
        }
        this.updateBulkActions();
    }

    updateBulkActions() {
        const bulkButtons = document.querySelectorAll('.table-actions .btn');
        const hasSelection = this.selectedCards.length > 0;
        
        bulkButtons.forEach(button => {
            button.disabled = !hasSelection;
        });
    }

    // Bulk Actions
    bulkAction(action) {
        if (this.selectedCards.length === 0) {
            this.showNotification('Please select cards to perform this action', 'error');
            return;
        }

        const actionText = {
            'activate': 'activate',
            'deactivate': 'deactivate',
            'delete': 'delete'
        }[action];

        if (!confirm(`Are you sure you want to ${actionText} ${this.selectedCards.length} selected card(s)?`)) {
            return;
        }

        this.performBulkAction(action, this.selectedCards);
    }

    async performBulkAction(action, cardIds) {
        try {
            this.showLoading();
            
            const response = await fetch('/api/v1/identity-cards/bulk', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: action,
                    card_ids: cardIds
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification(`Successfully ${action}d ${cardIds.length} card(s)`, 'success');
                this.selectedCards = [];
                this.refreshTable();
            } else {
                this.showNotification('Failed to perform bulk action: ' + data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error performing bulk action: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    // Utility Methods
    populateStudentSelect(students) {
        const select = document.getElementById('student_id');
        if (!select) return;

        select.innerHTML = '<option value="">Select Student</option>';
        students.forEach(student => {
            const option = document.createElement('option');
            option.value = student.id;
            option.textContent = `${student.name} (${student.student_id})`;
            select.appendChild(option);
        });
    }

    refreshTable() {
        this.fetchCards(this.currentPage, this.currentFilters);
    }

    showLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'flex';
        }
    }

    hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        // Add to page
        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    getNotificationIcon(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    // Helper Methods
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    capitalize(text) {
        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    getInitials(name) {
        return name.split(' ').map(word => word.charAt(0)).join('').toUpperCase().substring(0, 2);
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Export functions for global access
function openCreateModal() {
    identityCardManager.openCreateModal();
}

function applyFilters() {
    identityCardManager.applyFilters();
}

function clearFilters() {
    identityCardManager.clearFilters();
}

function changePage(page) {
    identityCardManager.changePage(page);
}

function toggleSelectAll() {
    identityCardManager.toggleSelectAll();
}

function bulkAction(action) {
    identityCardManager.bulkAction(action);
}

function viewCard(id) {
    identityCardManager.viewCard(id);
}

function editCard(id) {
    identityCardManager.editCard(id);
}

function generateCard(id) {
    identityCardManager.generateCard(id);
}

function deleteCard(id) {
    identityCardManager.deleteCard(id);
}

function closeModal() {
    identityCardManager.closeModal();
}

function exportCards() {
    // Implement export functionality
    const params = new URLSearchParams(identityCardManager.currentFilters);
    window.open(`/api/v1/identity-cards/export?${params}`, '_blank');
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.identityCardManager = new IdentityCardManager();
});

// Add notification styles
const notificationStyles = `
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 3000;
        min-width: 300px;
        max-width: 500px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-left: 4px solid #3b82f6;
        animation: slideIn 0.3s ease-out;
    }

    .notification-success {
        border-left-color: #16a34a;
    }

    .notification-error {
        border-left-color: #dc2626;
    }

    .notification-warning {
        border-left-color: #d97706;
    }

    .notification-info {
        border-left-color: #0ea5e9;
    }

    .notification-content {
        display: flex;
        align-items: center;
        padding: 15px;
        gap: 10px;
    }

    .notification-content i {
        font-size: 18px;
    }

    .notification-success .notification-content i {
        color: #16a34a;
    }

    .notification-error .notification-content i {
        color: #dc2626;
    }

    .notification-warning .notification-content i {
        color: #d97706;
    }

    .notification-info .notification-content i {
        color: #0ea5e9;
    }

    .notification-content span {
        flex: 1;
        color: #374151;
        font-size: 14px;
    }

    .notification-close {
        background: none;
        border: none;
        color: #9ca3af;
        cursor: pointer;
        padding: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.2s;
    }

    .notification-close:hover {
        background-color: #f3f4f6;
        color: #374151;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;

// Add styles to head
const styleSheet = document.createElement('style');
styleSheet.textContent = notificationStyles;
document.head.appendChild(styleSheet);

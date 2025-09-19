/**
 * Template Module JavaScript
 * Copy and modify this file for your new module
 */

class TemplateModuleManager {
    constructor() {
        this.currentPage = 1;
        this.currentFilters = {};
        this.selectedItems = [];
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadData();
    }

    bindEvents() {
        // Search and filter events
        document.getElementById('searchInput')?.addEventListener('input', 
            this.debounce(this.handleSearch.bind(this), 300));
        document.getElementById('statusFilter')?.addEventListener('change', 
            this.handleFilterChange.bind(this));

        // Modal events
        document.addEventListener('click', this.handleModalClick.bind(this));
        document.addEventListener('keydown', this.handleKeydown.bind(this));

        // Form events
        document.getElementById('itemForm')?.addEventListener('submit', 
            this.handleFormSubmit.bind(this));

        // Bulk actions
        document.getElementById('selectAllItems')?.addEventListener('change', 
            this.toggleSelectAll.bind(this));
    }

    // Event Handlers
    handleSearch(event) {
        this.currentFilters.search = event.target.value;
        this.applyFilters();
    }

    handleFilterChange(event) {
        this.currentFilters[event.target.id.replace('Filter', '')] = event.target.value;
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
        this.saveItem();
    }

    // API Methods
    async fetchItems(page = 1, filters = {}) {
        try {
            const params = new URLSearchParams({
                page: page,
                limit: 10,
                ...filters
            });

            const response = await fetch(`/api/v1/template?${params}`);
            const data = await response.json();

            if (data.success) {
                this.updateTable(data.data);
                this.updatePagination(data.pagination);
                this.currentPage = page;
            } else {
                this.showNotification('Failed to fetch items: ' + data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error fetching items: ' + error.message, 'error');
        }
    }

    async createItem(itemData) {
        try {
            const response = await fetch('/api/v1/template', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(itemData)
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Item created successfully!', 'success');
                this.closeModal();
                this.refreshTable();
            } else {
                this.showNotification('Failed to create item: ' + data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error creating item: ' + error.message, 'error');
        }
    }

    async updateItem(id, itemData) {
        try {
            const response = await fetch(`/api/v1/template/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(itemData)
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Item updated successfully!', 'success');
                this.closeModal();
                this.refreshTable();
            } else {
                this.showNotification('Failed to update item: ' + data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error updating item: ' + error.message, 'error');
        }
    }

    async deleteItem(id) {
        if (!confirm('Are you sure you want to delete this item?')) {
            return;
        }

        try {
            const response = await fetch(`/api/v1/template/${id}`, {
                method: 'DELETE'
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Item deleted successfully!', 'success');
                this.refreshTable();
            } else {
                this.showNotification('Failed to delete item: ' + data.message, 'error');
            }
        } catch (error) {
            this.showNotification('Error deleting item: ' + error.message, 'error');
        }
    }

    // UI Methods
    openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Create Template Item';
        document.getElementById('itemForm').reset();
        document.getElementById('itemId').value = '';
        document.getElementById('itemModal').style.display = 'block';
    }

    openEditModal(itemId) {
        this.loadItemForEdit(itemId);
        document.getElementById('modalTitle').textContent = 'Edit Template Item';
        document.getElementById('itemModal').style.display = 'block';
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

    viewItem(id) {
        this.loadItemForView(id);
        document.getElementById('viewModal').style.display = 'block';
    }

    editItem(id) {
        this.openEditModal(id);
    }

    deleteItem(id) {
        this.deleteItem(id);
    }

    // Form Methods
    saveItem() {
        const formData = new FormData(document.getElementById('itemForm'));
        const itemData = Object.fromEntries(formData);

        // Validate required fields
        if (!itemData.name) {
            this.showNotification('Please fill in all required fields', 'error');
            return;
        }

        const itemId = document.getElementById('itemId').value;
        if (itemId) {
            this.updateItem(itemId, itemData);
        } else {
            this.createItem(itemData);
        }
    }

    async loadItemForEdit(itemId) {
        try {
            const response = await fetch(`/api/v1/template/${itemId}`);
            const data = await response.json();

            if (data.success) {
                const item = data.data;
                document.getElementById('itemId').value = item.id;
                document.getElementById('itemName').value = item.name;
                document.getElementById('itemDescription').value = item.description || '';
                document.getElementById('itemStatus').value = item.status;
            }
        } catch (error) {
            this.showNotification('Error loading item data: ' + error.message, 'error');
        }
    }

    async loadItemForView(itemId) {
        try {
            const response = await fetch(`/api/v1/template/${itemId}`);
            const data = await response.json();

            if (data.success) {
                const item = data.data;
                document.getElementById('viewModalBody').innerHTML = `
                    <div class="item-details">
                        <h4>${this.escapeHtml(item.name)}</h4>
                        <p><strong>Description:</strong> ${this.escapeHtml(item.description || 'No description')}</p>
                        <p><strong>Status:</strong> <span class="status-badge status-${item.status}">${item.status}</span></p>
                        <p><strong>Created:</strong> ${new Date(item.created_at).toLocaleDateString()}</p>
                        <p><strong>Updated:</strong> ${new Date(item.updated_at).toLocaleDateString()}</p>
                    </div>
                `;
            }
        } catch (error) {
            this.showNotification('Error loading item data: ' + error.message, 'error');
        }
    }

    // Filter and Search Methods
    applyFilters() {
        this.currentPage = 1;
        this.fetchItems(this.currentPage, this.currentFilters);
    }

    clearFilters() {
        this.currentFilters = {};
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = '';
        this.applyFilters();
    }

    changePage(page) {
        this.fetchItems(page, this.currentFilters);
    }

    // Table Methods
    updateTable(items) {
        const tbody = document.querySelector('#templateItemsTable tbody');
        if (!tbody) return;

        if (items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="no-data">
                        <div class="no-data-content">
                            <i class="fas fa-cube"></i>
                            <p>No items found</p>
                            <button class="btn btn-primary" onclick="templateModuleManager.openCreateModal()">
                                Create First Item
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = items.map(item => this.createTableRow(item)).join('');
    }

    createTableRow(item) {
        return `
            <tr data-item-id="${item.id}">
                <td>
                    <input type="checkbox" class="item-checkbox" value="${item.id}" 
                           onchange="templateModuleManager.toggleItemSelection(${item.id})">
                </td>
                <td>
                    <strong>${this.escapeHtml(item.name)}</strong>
                </td>
                <td>${this.escapeHtml(item.description || 'No description')}</td>
                <td>
                    <span class="status-badge status-${item.status}">
                        ${this.capitalize(item.status)}
                    </span>
                </td>
                <td>${new Date(item.created_at).toLocaleDateString()}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-info" onclick="templateModuleManager.viewItem(${item.id})" 
                                title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="templateModuleManager.editItem(${item.id})" 
                                title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="templateModuleManager.deleteItem(${item.id})" 
                                title="Delete">
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
                <button class="btn btn-sm" onclick="templateModuleManager.changePage(1)" 
                        ${pagination.current_page == 1 ? 'disabled' : ''}>
                    <i class="fas fa-angle-double-left"></i>
                </button>
                <button class="btn btn-sm" onclick="templateModuleManager.changePage(${pagination.current_page - 1})"
                        ${!pagination.has_prev ? 'disabled' : ''}>
                    <i class="fas fa-angle-left"></i>
                </button>
            `;

            const startPage = Math.max(1, pagination.current_page - 2);
            const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

            for (let i = startPage; i <= endPage; i++) {
                controlsHTML += `
                    <button class="btn btn-sm ${i == pagination.current_page ? 'active' : ''}" 
                            onclick="templateModuleManager.changePage(${i})">
                        ${i}
                    </button>
                `;
            }

            controlsHTML += `
                <button class="btn btn-sm" onclick="templateModuleManager.changePage(${pagination.current_page + 1})"
                        ${!pagination.has_next ? 'disabled' : ''}>
                    <i class="fas fa-angle-right"></i>
                </button>
                <button class="btn btn-sm" onclick="templateModuleManager.changePage(${pagination.total_pages})"
                        ${pagination.current_page == pagination.total_pages ? 'disabled' : ''}>
                    <i class="fas fa-angle-double-right"></i>
                </button>
            `;

            controlsElement.innerHTML = controlsHTML;
        }
    }

    // Selection Methods
    toggleSelectAll() {
        const selectAllCheckbox = document.getElementById('selectAllItems');
        const itemCheckboxes = document.querySelectorAll('.item-checkbox');
        
        itemCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
            this.toggleItemSelection(parseInt(checkbox.value));
        });
    }

    toggleItemSelection(itemId) {
        const index = this.selectedItems.indexOf(itemId);
        if (index > -1) {
            this.selectedItems.splice(index, 1);
        } else {
            this.selectedItems.push(itemId);
        }
        this.updateBulkActions();
    }

    updateBulkActions() {
        const bulkButtons = document.querySelectorAll('.bulk-actions .btn');
        const hasSelection = this.selectedItems.length > 0;
        
        bulkButtons.forEach(button => {
            button.disabled = !hasSelection;
        });
    }

    // Utility Methods
    refreshTable() {
        this.fetchItems(this.currentPage, this.currentFilters);
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
        const container = document.getElementById('notificationContainer') || document.body;
        container.appendChild(notification);

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

    // Load initial data
    loadData() {
        this.fetchItems();
    }
}

// Export functions for global access
function openCreateModal() {
    templateModuleManager.openCreateModal();
}

function applyFilters() {
    templateModuleManager.applyFilters();
}

function clearFilters() {
    templateModuleManager.clearFilters();
}

function changePage(page) {
    templateModuleManager.changePage(page);
}

function toggleSelectAll() {
    templateModuleManager.toggleSelectAll();
}

function viewItem(id) {
    templateModuleManager.viewItem(id);
}

function editItem(id) {
    templateModuleManager.editItem(id);
}

function deleteItem(id) {
    templateModuleManager.deleteItem(id);
}

function closeModal() {
    templateModuleManager.closeModal();
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.templateModuleManager = new TemplateModuleManager();
});

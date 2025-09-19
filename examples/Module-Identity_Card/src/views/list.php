<?php
// Identity Card List View
$cards = $data['cards'] ?? [];
$pagination = $data['pagination'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="identity-card-module">
    <div class="module-header">
        <div class="header-content">
            <h2><i class="fas fa-id-card"></i> Identity Card Management</h2>
            <p>Manage student and staff identity cards</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i> Create New Card
            </button>
            <button class="btn btn-secondary" onclick="exportCards()">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <div class="filter-row">
            <div class="filter-group">
                <label for="search">Search:</label>
                <input type="text" id="search" placeholder="Search by card number, student name..." 
                       value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            </div>
            <div class="filter-group">
                <label for="status">Status:</label>
                <select id="status">
                    <option value="">All Status</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="generated" <?= ($filters['status'] ?? '') === 'generated' ? 'selected' : '' ?>>Generated</option>
                    <option value="expired" <?= ($filters['status'] ?? '') === 'expired' ? 'selected' : '' ?>>Expired</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="card_type">Card Type:</label>
                <select id="card_type">
                    <option value="">All Types</option>
                    <option value="student" <?= ($filters['card_type'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                    <option value="staff" <?= ($filters['card_type'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option>
                    <option value="visitor" <?= ($filters['card_type'] ?? '') === 'visitor' ? 'selected' : '' ?>>Visitor</option>
                </select>
            </div>
            <div class="filter-actions">
                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-search"></i> Filter
                </button>
                <button class="btn btn-secondary" onclick="clearFilters()">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-section">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-id-card"></i>
            </div>
            <div class="stat-content">
                <h3><?= $data['stats']['total_cards'] ?? 0 ?></h3>
                <p>Total Cards</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon active">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?= $data['stats']['active_cards'] ?? 0 ?></h3>
                <p>Active Cards</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?= $data['stats']['pending_cards'] ?? 0 ?></h3>
                <p>Pending Cards</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon expired">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?= $data['stats']['expired_cards'] ?? 0 ?></h3>
                <p>Expired Cards</p>
            </div>
        </div>
    </div>

    <!-- Cards Table -->
    <div class="table-section">
        <div class="table-header">
            <h3>Identity Cards</h3>
            <div class="table-actions">
                <button class="btn btn-sm btn-secondary" onclick="bulkAction('activate')">
                    <i class="fas fa-check"></i> Activate Selected
                </button>
                <button class="btn btn-sm btn-warning" onclick="bulkAction('deactivate')">
                    <i class="fas fa-pause"></i> Deactivate Selected
                </button>
                <button class="btn btn-sm btn-danger" onclick="bulkAction('delete')">
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table" id="identityCardsTable">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                        </th>
                        <th>Card Number</th>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Class</th>
                        <th>Card Type</th>
                        <th>Status</th>
                        <th>Valid From</th>
                        <th>Valid Until</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cards)): ?>
                        <tr>
                            <td colspan="10" class="no-data">
                                <div class="no-data-content">
                                    <i class="fas fa-id-card"></i>
                                    <p>No identity cards found</p>
                                    <button class="btn btn-primary" onclick="openCreateModal()">
                                        Create First Card
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cards as $card): ?>
                            <tr data-card-id="<?= $card['id'] ?>">
                                <td>
                                    <input type="checkbox" class="card-checkbox" value="<?= $card['id'] ?>">
                                </td>
                                <td>
                                    <div class="card-number">
                                        <strong><?= htmlspecialchars($card['card_number']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <div class="student-info">
                                        <div class="student-avatar">
                                            <?php if (!empty($card['student_photo'])): ?>
                                                <img src="<?= htmlspecialchars($card['student_photo']) ?>" 
                                                     alt="<?= htmlspecialchars($card['student_name']) ?>">
                                            <?php else: ?>
                                                <div class="avatar-placeholder">
                                                    <?= strtoupper(substr($card['student_name'], 0, 2)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="student-details">
                                            <strong><?= htmlspecialchars($card['student_name']) ?></strong>
                                            <small><?= htmlspecialchars($card['student_email']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($card['student_number']) ?></td>
                                <td>
                                    <span class="class-badge">
                                        <?= htmlspecialchars($card['class_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="type-badge type-<?= $card['card_type'] ?>">
                                        <?= ucfirst($card['card_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $card['status'] ?>">
                                        <?= ucfirst($card['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($card['valid_from'])) ?></td>
                                <td>
                                    <?php 
                                    $validUntil = strtotime($card['valid_until']);
                                    $isExpired = $validUntil < time();
                                    $isExpiringSoon = $validUntil < strtotime('+30 days');
                                    ?>
                                    <span class="validity-date <?= $isExpired ? 'expired' : ($isExpiringSoon ? 'expiring-soon' : '') ?>">
                                        <?= date('M d, Y', $validUntil) ?>
                                        <?php if ($isExpired): ?>
                                            <i class="fas fa-exclamation-triangle" title="Expired"></i>
                                        <?php elseif ($isExpiringSoon): ?>
                                            <i class="fas fa-clock" title="Expiring Soon"></i>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-info" onclick="viewCard(<?= $card['id'] ?>)" 
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-success" onclick="generateCard(<?= $card['id'] ?>)" 
                                                title="Generate PDF" <?= $card['status'] === 'generated' ? 'disabled' : '' ?>>
                                            <i class="fas fa-file-pdf"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary" onclick="editCard(<?= $card['id'] ?>)" 
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteCard(<?= $card['id'] ?>)" 
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
            <div class="pagination">
                <div class="pagination-info">
                    Showing <?= (($pagination['current_page'] - 1) * $pagination['per_page']) + 1 ?> 
                    to <?= min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) ?> 
                    of <?= $pagination['total'] ?> entries
                </div>
                <div class="pagination-controls">
                    <button class="btn btn-sm" onclick="changePage(1)" 
                            <?= $pagination['current_page'] == 1 ? 'disabled' : '' ?>>
                        <i class="fas fa-angle-double-left"></i>
                    </button>
                    <button class="btn btn-sm" onclick="changePage(<?= $pagination['current_page'] - 1 ?>)"
                            <?= !$pagination['has_prev'] ? 'disabled' : '' ?>>
                        <i class="fas fa-angle-left"></i>
                    </button>
                    
                    <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                        <button class="btn btn-sm <?= $i == $pagination['current_page'] ? 'active' : '' ?>" 
                                onclick="changePage(<?= $i ?>)">
                            <?= $i ?>
                        </button>
                    <?php endfor; ?>
                    
                    <button class="btn btn-sm" onclick="changePage(<?= $pagination['current_page'] + 1 ?>)"
                            <?= !$pagination['has_next'] ? 'disabled' : '' ?>>
                        <i class="fas fa-angle-right"></i>
                    </button>
                    <button class="btn btn-sm" onclick="changePage(<?= $pagination['total_pages'] ?>)"
                            <?= $pagination['current_page'] == $pagination['total_pages'] ? 'disabled' : '' ?>>
                        <i class="fas fa-angle-double-right"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="cardModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Create Identity Card</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="cardForm">
                <input type="hidden" id="cardId" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_id">Student *</label>
                        <select id="student_id" name="student_id" required>
                            <option value="">Select Student</option>
                            <!-- Options will be loaded dynamically -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="card_type">Card Type *</label>
                        <select id="card_type" name="card_type" required>
                            <option value="">Select Type</option>
                            <option value="student">Student</option>
                            <option value="staff">Staff</option>
                            <option value="visitor">Visitor</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="valid_from">Valid From *</label>
                        <input type="date" id="valid_from" name="valid_from" required>
                    </div>
                    <div class="form-group">
                        <label for="valid_until">Valid Until *</label>
                        <input type="date" id="valid_until" name="valid_until" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Additional notes..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveCard()">Save Card</button>
        </div>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3>Identity Card Details</h3>
            <span class="close" onclick="closeViewModal()">&times;</span>
        </div>
        <div class="modal-body" id="viewModalBody">
            <!-- Content will be loaded dynamically -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeViewModal()">Close</button>
            <button class="btn btn-success" onclick="generateCardFromView()">
                <i class="fas fa-file-pdf"></i> Generate PDF
            </button>
        </div>
    </div>
</div>

<script>
// JavaScript functions will be handled by identity.js
</script>

<?php
// Identity Card View Details
$card = $data['card'] ?? null;
$student = $data['student'] ?? null;
$activity = $data['activity'] ?? [];
?>

<div class="identity-card-view">
    <?php if ($card && $student): ?>
        <div class="view-header">
            <div class="header-content">
                <div class="breadcrumb">
                    <a href="/modules/identity-cards">Identity Cards</a>
                    <span class="separator">/</span>
                    <span>View Card</span>
                </div>
                <h1>
                    <i class="fas fa-id-card"></i>
                    Identity Card Details
                </h1>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="goBack()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button class="btn btn-primary" onclick="editCard()">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-success" onclick="generateCard()">
                    <i class="fas fa-file-pdf"></i> Generate PDF
                </button>
                <button class="btn btn-danger" onclick="deleteCard()">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>

        <div class="view-content">
            <div class="view-sidebar">
                <!-- Card Status -->
                <div class="status-section">
                    <div class="status-header">
                        <h3>Card Status</h3>
                        <span class="status-badge status-<?= $card['status'] ?>">
                            <?= ucfirst($card['status']) ?>
                        </span>
                    </div>
                    
                    <div class="status-details">
                        <div class="status-item">
                            <label>Card Number:</label>
                            <span class="card-number"><?= htmlspecialchars($card['card_number']) ?></span>
                        </div>
                        <div class="status-item">
                            <label>Card Type:</label>
                            <span class="type-badge type-<?= $card['card_type'] ?>">
                                <?= ucfirst($card['card_type']) ?>
                            </span>
                        </div>
                        <div class="status-item">
                            <label>Created:</label>
                            <span><?= date('M d, Y H:i', strtotime($card['created_at'])) ?></span>
                        </div>
                        <div class="status-item">
                            <label>Last Updated:</label>
                            <span><?= date('M d, Y H:i', strtotime($card['updated_at'])) ?></span>
                        </div>
                        <?php if ($card['generated_at']): ?>
                            <div class="status-item">
                                <label>Generated:</label>
                                <span><?= date('M d, Y H:i', strtotime($card['generated_at'])) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Validity Information -->
                <div class="validity-section">
                    <h3>Validity Period</h3>
                    <div class="validity-details">
                        <div class="validity-item">
                            <label>Valid From:</label>
                            <span><?= date('M d, Y', strtotime($card['valid_from'])) ?></span>
                        </div>
                        <div class="validity-item">
                            <label>Valid Until:</label>
                            <?php 
                            $validUntil = strtotime($card['valid_until']);
                            $isExpired = $validUntil < time();
                            $isExpiringSoon = $validUntil < strtotime('+30 days');
                            $daysLeft = ceil(($validUntil - time()) / (60 * 60 * 24));
                            ?>
                            <span class="validity-date <?= $isExpired ? 'expired' : ($isExpiringSoon ? 'expiring-soon' : '') ?>">
                                <?= date('M d, Y', $validUntil) ?>
                                <?php if ($isExpired): ?>
                                    <small class="expired-text">(Expired)</small>
                                <?php elseif ($isExpiringSoon): ?>
                                    <small class="expiring-text">(<?= $daysLeft ?> days left)</small>
                                <?php else: ?>
                                    <small class="valid-text">(<?= $daysLeft ?> days left)</small>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="validity-progress">
                            <?php
                            $totalDays = ceil((strtotime($card['valid_until']) - strtotime($card['valid_from'])) / (60 * 60 * 24));
                            $usedDays = $totalDays - $daysLeft;
                            $progressPercent = min(100, max(0, ($usedDays / $totalDays) * 100));
                            ?>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $progressPercent ?>%"></div>
                            </div>
                            <small>Validity Progress: <?= round($progressPercent) ?>%</small>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="actions-section">
                    <h3>Quick Actions</h3>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-primary" onclick="editCard()">
                            <i class="fas fa-edit"></i> Edit Card
                        </button>
                        <button class="btn btn-sm btn-success" onclick="generateCard()">
                            <i class="fas fa-file-pdf"></i> Generate PDF
                        </button>
                        <button class="btn btn-sm btn-info" onclick="duplicateCard()">
                            <i class="fas fa-copy"></i> Duplicate
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="renewCard()">
                            <i class="fas fa-sync"></i> Renew
                        </button>
                    </div>
                </div>
            </div>

            <div class="view-main">
                <!-- Student Information -->
                <div class="info-section">
                    <div class="section-header">
                        <h2>Student Information</h2>
                        <div class="student-avatar-large">
                            <?php if (!empty($student['photo'])): ?>
                                <img src="<?= htmlspecialchars($student['photo']) ?>" 
                                     alt="<?= htmlspecialchars($student['name']) ?>">
                            <?php else: ?>
                                <div class="avatar-placeholder-large">
                                    <?= strtoupper(substr($student['name'], 0, 2)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-group">
                            <h4>Personal Details</h4>
                            <div class="info-item">
                                <label>Full Name:</label>
                                <span><?= htmlspecialchars($student['name']) ?></span>
                            </div>
                            <div class="info-item">
                                <label>Student ID:</label>
                                <span><?= htmlspecialchars($student['student_id']) ?></span>
                            </div>
                            <div class="info-item">
                                <label>Date of Birth:</label>
                                <span><?= date('M d, Y', strtotime($student['date_of_birth'])) ?></span>
                            </div>
                            <div class="info-item">
                                <label>Gender:</label>
                                <span><?= ucfirst($student['gender']) ?></span>
                            </div>
                        </div>
                        
                        <div class="info-group">
                            <h4>Academic Information</h4>
                            <div class="info-item">
                                <label>Class:</label>
                                <span><?= htmlspecialchars($student['class_name'] ?? 'N/A') ?></span>
                            </div>
                            <div class="info-item">
                                <label>Department:</label>
                                <span><?= htmlspecialchars($student['department_name'] ?? 'N/A') ?></span>
                            </div>
                            <div class="info-item">
                                <label>Academic Year:</label>
                                <span><?= htmlspecialchars($student['academic_year'] ?? 'N/A') ?></span>
                            </div>
                            <div class="info-item">
                                <label>Roll Number:</label>
                                <span><?= htmlspecialchars($student['roll_number'] ?? 'N/A') ?></span>
                            </div>
                        </div>
                        
                        <div class="info-group">
                            <h4>Contact Information</h4>
                            <div class="info-item">
                                <label>Email:</label>
                                <span>
                                    <a href="mailto:<?= htmlspecialchars($student['email']) ?>">
                                        <?= htmlspecialchars($student['email']) ?>
                                    </a>
                                </span>
                            </div>
                            <div class="info-item">
                                <label>Phone:</label>
                                <span>
                                    <a href="tel:<?= htmlspecialchars($student['phone']) ?>">
                                        <?= htmlspecialchars($student['phone']) ?>
                                    </a>
                                </span>
                            </div>
                            <div class="info-item">
                                <label>Address:</label>
                                <span><?= htmlspecialchars($student['address']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Details -->
                <div class="info-section">
                    <h2>Card Details</h2>
                    <div class="info-grid">
                        <div class="info-group">
                            <h4>Card Information</h4>
                            <div class="info-item">
                                <label>Card Number:</label>
                                <span class="card-number-large"><?= htmlspecialchars($card['card_number']) ?></span>
                            </div>
                            <div class="info-item">
                                <label>Card Type:</label>
                                <span class="type-badge type-<?= $card['card_type'] ?>">
                                    <?= ucfirst($card['card_type']) ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <label>Status:</label>
                                <span class="status-badge status-<?= $card['status'] ?>">
                                    <?= ucfirst($card['status']) ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <label>Notes:</label>
                                <span><?= htmlspecialchars($card['notes'] ?? 'No notes') ?></span>
                            </div>
                        </div>
                        
                        <div class="info-group">
                            <h4>Validity Information</h4>
                            <div class="info-item">
                                <label>Valid From:</label>
                                <span><?= date('M d, Y', strtotime($card['valid_from'])) ?></span>
                            </div>
                            <div class="info-item">
                                <label>Valid Until:</label>
                                <span class="validity-date <?= $isExpired ? 'expired' : ($isExpiringSoon ? 'expiring-soon' : '') ?>">
                                    <?= date('M d, Y', $validUntil) ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <label>Duration:</label>
                                <span><?= $totalDays ?> days</span>
                            </div>
                            <div class="info-item">
                                <label>Days Remaining:</label>
                                <span class="<?= $isExpired ? 'expired' : ($isExpiringSoon ? 'expiring-soon' : '') ?>">
                                    <?= $daysLeft ?> days
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Log -->
                <?php if (!empty($activity)): ?>
                    <div class="info-section">
                        <h2>Activity Log</h2>
                        <div class="activity-timeline">
                            <?php foreach ($activity as $log): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-<?= $log['icon'] ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-header">
                                            <h4><?= htmlspecialchars($log['action']) ?></h4>
                                            <span class="activity-time">
                                                <?= date('M d, Y H:i', strtotime($log['created_at'])) ?>
                                            </span>
                                        </div>
                                        <p><?= htmlspecialchars($log['description']) ?></p>
                                        <?php if (!empty($log['user_name'])): ?>
                                            <small>By: <?= htmlspecialchars($log['user_name']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Card Preview -->
                <div class="info-section">
                    <h2>Card Preview</h2>
                    <div class="card-preview-container">
                        <div class="card-preview-mini">
                            <div class="card-front-mini">
                                <div class="card-header-mini">
                                    <div class="institution-logo-mini">
                                        <img src="/assets/images/logodefault.jpeg" alt="Logo">
                                    </div>
                                    <div class="institution-info-mini">
                                        <h3>EduConnect</h3>
                                        <p>Educational Institution</p>
                                    </div>
                                </div>
                                
                                <div class="card-body-mini">
                                    <div class="student-photo-mini">
                                        <?php if (!empty($student['photo'])): ?>
                                            <img src="<?= htmlspecialchars($student['photo']) ?>" 
                                                 alt="<?= htmlspecialchars($student['name']) ?>">
                                        <?php else: ?>
                                            <div class="photo-placeholder-mini">
                                                <?= strtoupper(substr($student['name'], 0, 2)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="student-info-mini">
                                        <div class="info-row-mini">
                                            <strong><?= htmlspecialchars($student['name']) ?></strong>
                                        </div>
                                        <div class="info-row-mini">
                                            <small>ID: <?= htmlspecialchars($student['student_id']) ?></small>
                                        </div>
                                        <div class="info-row-mini">
                                            <small><?= htmlspecialchars($student['class_name'] ?? 'N/A') ?></small>
                                        </div>
                                        <div class="info-row-mini">
                                            <small>Valid: <?= date('M d, Y', strtotime($card['valid_until'])) ?></small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer-mini">
                                    <div class="card-number-mini">
                                        <?= htmlspecialchars($card['card_number']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="error-section">
            <div class="error-content">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Card Not Found</h3>
                <p>The requested identity card could not be found.</p>
                <button class="btn btn-primary" onclick="goBack()">
                    <i class="fas fa-arrow-left"></i> Back to List
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Identity Card</h3>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="editForm">
                <input type="hidden" id="editCardId" value="<?= $card['id'] ?? '' ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="editCardType">Card Type:</label>
                        <select id="editCardType" name="card_type">
                            <option value="student" <?= ($card['card_type'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                            <option value="staff" <?= ($card['card_type'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="visitor" <?= ($card['card_type'] ?? '') === 'visitor' ? 'selected' : '' ?>>Visitor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editStatus">Status:</label>
                        <select id="editStatus" name="status">
                            <option value="pending" <?= ($card['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="active" <?= ($card['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="expired" <?= ($card['status'] ?? '') === 'expired' ? 'selected' : '' ?>>Expired</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="editValidFrom">Valid From:</label>
                        <input type="date" id="editValidFrom" name="valid_from" 
                               value="<?= $card['valid_from'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="editValidUntil">Valid Until:</label>
                        <input type="date" id="editValidUntil" name="valid_until" 
                               value="<?= $card['valid_until'] ?? '' ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="editNotes">Notes:</label>
                    <textarea id="editNotes" name="notes" rows="3"><?= htmlspecialchars($card['notes'] ?? '') ?></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveEdit()">Save Changes</button>
        </div>
    </div>
</div>

<script>
// View page JavaScript functions
let currentCard = <?= json_encode($card) ?>;
let currentStudent = <?= json_encode($student) ?>;

function goBack() {
    window.history.back();
}

function editCard() {
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function saveEdit() {
    const formData = new FormData(document.getElementById('editForm'));
    const data = Object.fromEntries(formData);
    
    fetch(`/api/v1/identity-cards/${currentCard.id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Card updated successfully!', 'success');
            closeEditModal();
            location.reload();
        } else {
            showNotification('Failed to update card: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error updating card: ' + error.message, 'error');
    });
}

function generateCard() {
    window.location.href = `/modules/identity-cards/generate/${currentCard.id}`;
}

function deleteCard() {
    if (confirm('Are you sure you want to delete this identity card?')) {
        fetch(`/api/v1/identity-cards/${currentCard.id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Card deleted successfully!', 'success');
                window.location.href = '/modules/identity-cards';
            } else {
                showNotification('Failed to delete card: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error deleting card: ' + error.message, 'error');
        });
    }
}

function duplicateCard() {
    // Create a copy of the current card
    const newCardData = {
        student_id: currentCard.student_id,
        card_type: currentCard.card_type,
        valid_from: new Date().toISOString().split('T')[0],
        valid_until: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        status: 'pending',
        notes: 'Duplicated from card #' + currentCard.card_number
    };
    
    fetch('/api/v1/identity-cards', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(newCardData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Card duplicated successfully!', 'success');
            window.location.href = `/modules/identity-cards/view/${data.data.id}`;
        } else {
            showNotification('Failed to duplicate card: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error duplicating card: ' + error.message, 'error');
    });
}

function renewCard() {
    // Extend the validity period
    const newValidUntil = new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    
    fetch(`/api/v1/identity-cards/${currentCard.id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            valid_until: newValidUntil,
            status: 'active'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Card renewed successfully!', 'success');
            location.reload();
        } else {
            showNotification('Failed to renew card: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error renewing card: ' + error.message, 'error');
    });
}

function showNotification(message, type) {
    // Show notification to user
    console.log(`${type.toUpperCase()}: ${message}`);
}
</script>

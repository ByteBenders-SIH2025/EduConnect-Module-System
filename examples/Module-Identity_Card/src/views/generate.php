<?php
// Identity Card Generation View
$card = $data['card'] ?? null;
$student = $data['student'] ?? null;
$template = $data['template'] ?? 'default';
?>

<div class="identity-card-generator">
    <div class="generator-header">
        <div class="header-content">
            <h2><i class="fas fa-id-card"></i> Generate Identity Card</h2>
            <p>Create and customize identity card for <?= htmlspecialchars($student['name'] ?? 'Student') ?></p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="goBack()">
                <i class="fas fa-arrow-left"></i> Back to List
            </button>
            <button class="btn btn-primary" onclick="previewCard()">
                <i class="fas fa-eye"></i> Preview
            </button>
            <button class="btn btn-success" onclick="generatePDF()">
                <i class="fas fa-file-pdf"></i> Generate PDF
            </button>
        </div>
    </div>

    <?php if ($card && $student): ?>
        <div class="generator-content">
            <div class="generator-sidebar">
                <!-- Card Information -->
                <div class="info-section">
                    <h3>Card Information</h3>
                    <div class="info-item">
                        <label>Card Number:</label>
                        <span><?= htmlspecialchars($card['card_number']) ?></span>
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
                        <label>Valid From:</label>
                        <span><?= date('M d, Y', strtotime($card['valid_from'])) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Valid Until:</label>
                        <span><?= date('M d, Y', strtotime($card['valid_until'])) ?></span>
                    </div>
                </div>

                <!-- Student Information -->
                <div class="info-section">
                    <h3>Student Information</h3>
                    <div class="student-avatar-large">
                        <?php if (!empty($student['photo'])): ?>
                            <img src="<?= htmlspecialchars($student['photo']) ?>" 
                                 alt="<?= htmlspecialchars($student['name']) ?>" 
                                 id="studentPhoto">
                        <?php else: ?>
                            <div class="avatar-placeholder-large" id="studentPhotoPlaceholder">
                                <?= strtoupper(substr($student['name'], 0, 2)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <label>Name:</label>
                        <span><?= htmlspecialchars($student['name']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Student ID:</label>
                        <span><?= htmlspecialchars($student['student_id']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Class:</label>
                        <span><?= htmlspecialchars($student['class_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-item">
                        <label>Department:</label>
                        <span><?= htmlspecialchars($student['department_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-item">
                        <label>Email:</label>
                        <span><?= htmlspecialchars($student['email']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Phone:</label>
                        <span><?= htmlspecialchars($student['phone']) ?></span>
                    </div>
                </div>

                <!-- Template Settings -->
                <div class="info-section">
                    <h3>Template Settings</h3>
                    <div class="form-group">
                        <label for="templateSelect">Template:</label>
                        <select id="templateSelect" onchange="changeTemplate()">
                            <option value="default" <?= $template === 'default' ? 'selected' : '' ?>>Default</option>
                            <option value="modern" <?= $template === 'modern' ? 'selected' : '' ?>>Modern</option>
                            <option value="classic" <?= $template === 'classic' ? 'selected' : '' ?>>Classic</option>
                            <option value="minimal" <?= $template === 'minimal' ? 'selected' : '' ?>>Minimal</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cardSize">Card Size:</label>
                        <select id="cardSize" onchange="changeCardSize()">
                            <option value="standard">Standard (85.6 x 53.98 mm)</option>
                            <option value="large">Large (105 x 74 mm)</option>
                            <option value="small">Small (66 x 42 mm)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="orientation">Orientation:</label>
                        <select id="orientation" onchange="changeOrientation()">
                            <option value="landscape">Landscape</option>
                            <option value="portrait">Portrait</option>
                        </select>
                    </div>
                </div>

                <!-- Customization Options -->
                <div class="info-section">
                    <h3>Customization</h3>
                    <div class="form-group">
                        <label for="primaryColor">Primary Color:</label>
                        <input type="color" id="primaryColor" value="#2563eb" onchange="updateColors()">
                    </div>
                    <div class="form-group">
                        <label for="secondaryColor">Secondary Color:</label>
                        <input type="color" id="secondaryColor" value="#1e40af" onchange="updateColors()">
                    </div>
                    <div class="form-group">
                        <label for="textColor">Text Color:</label>
                        <input type="color" id="textColor" value="#1f2937" onchange="updateColors()">
                    </div>
                    <div class="form-group">
                        <label for="fontSize">Font Size:</label>
                        <input type="range" id="fontSize" min="8" max="16" value="12" onchange="updateFontSize()">
                        <span id="fontSizeValue">12px</span>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="showQRCode" checked onchange="toggleQRCode()">
                            Show QR Code
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="showBarcode" onchange="toggleBarcode()">
                            Show Barcode
                        </label>
                    </div>
                </div>
            </div>

            <div class="generator-main">
                <!-- Card Preview -->
                <div class="card-preview-section">
                    <h3>Card Preview</h3>
                    <div class="preview-container">
                        <div class="card-preview" id="cardPreview">
                            <div class="card-front">
                                <div class="card-header">
                                    <div class="institution-logo">
                                        <img src="/assets/images/logodefault.jpeg" alt="Institution Logo">
                                    </div>
                                    <div class="institution-info">
                                        <h2>EduConnect</h2>
                                        <p>Educational Institution</p>
                                    </div>
                                </div>
                                
                                <div class="card-body">
                                    <div class="student-photo-section">
                                        <div class="student-photo">
                                            <?php if (!empty($student['photo'])): ?>
                                                <img src="<?= htmlspecialchars($student['photo']) ?>" 
                                                     alt="<?= htmlspecialchars($student['name']) ?>">
                                            <?php else: ?>
                                                <div class="photo-placeholder">
                                                    <?= strtoupper(substr($student['name'], 0, 2)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="student-info-section">
                                        <div class="info-row">
                                            <label>Name:</label>
                                            <span><?= htmlspecialchars($student['name']) ?></span>
                                        </div>
                                        <div class="info-row">
                                            <label>ID:</label>
                                            <span><?= htmlspecialchars($student['student_id']) ?></span>
                                        </div>
                                        <div class="info-row">
                                            <label>Class:</label>
                                            <span><?= htmlspecialchars($student['class_name'] ?? 'N/A') ?></span>
                                        </div>
                                        <div class="info-row">
                                            <label>Department:</label>
                                            <span><?= htmlspecialchars($student['department_name'] ?? 'N/A') ?></span>
                                        </div>
                                        <div class="info-row">
                                            <label>Valid Until:</label>
                                            <span><?= date('M d, Y', strtotime($card['valid_until'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer">
                                    <div class="card-number">
                                        <?= htmlspecialchars($card['card_number']) ?>
                                    </div>
                                    <div class="qr-code" id="qrCodeContainer">
                                        <!-- QR Code will be generated here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generation Options -->
                <div class="generation-options">
                    <h3>Generation Options</h3>
                    <div class="options-grid">
                        <div class="option-group">
                            <label>
                                <input type="checkbox" id="includeBack" checked>
                                Include Back Side
                            </label>
                        </div>
                        <div class="option-group">
                            <label>
                                <input type="checkbox" id="includeWatermark" checked>
                                Include Watermark
                            </label>
                        </div>
                        <div class="option-group">
                            <label>
                                <input type="checkbox" id="highQuality" checked>
                                High Quality
                            </label>
                        </div>
                        <div class="option-group">
                            <label>
                                <input type="checkbox" id="includeSignature">
                                Include Signature
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="outputFormat">Output Format:</label>
                        <select id="outputFormat">
                            <option value="pdf">PDF</option>
                            <option value="png">PNG Image</option>
                            <option value="jpg">JPG Image</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="copies">Number of Copies:</label>
                        <input type="number" id="copies" min="1" max="10" value="1">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="generation-actions">
                    <button class="btn btn-primary" onclick="previewCard()">
                        <i class="fas fa-eye"></i> Preview Card
                    </button>
                    <button class="btn btn-success" onclick="generatePDF()">
                        <i class="fas fa-file-pdf"></i> Generate PDF
                    </button>
                    <button class="btn btn-info" onclick="downloadImage()">
                        <i class="fas fa-download"></i> Download Image
                    </button>
                    <button class="btn btn-warning" onclick="printCard()">
                        <i class="fas fa-print"></i> Print Card
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="error-section">
            <div class="error-content">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Card Not Found</h3>
                <p>The requested identity card could not be found or the student information is missing.</p>
                <button class="btn btn-primary" onclick="goBack()">
                    <i class="fas fa-arrow-left"></i> Back to List
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Preview Modal -->
<div id="previewModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3>Card Preview</h3>
            <span class="close" onclick="closePreviewModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="preview-container-large" id="previewContainer">
                <!-- Preview content will be loaded here -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePreviewModal()">Close</button>
            <button class="btn btn-primary" onclick="generateFromPreview()">
                <i class="fas fa-file-pdf"></i> Generate PDF
            </button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-content">
        <div class="spinner"></div>
        <p>Generating identity card...</p>
    </div>
</div>

<script>
// Card generation JavaScript functions
let currentCard = <?= json_encode($card) ?>;
let currentStudent = <?= json_encode($student) ?>;

// Initialize the generator
document.addEventListener('DOMContentLoaded', function() {
    initializeGenerator();
    generateQRCode();
});

function initializeGenerator() {
    // Set default values
    document.getElementById('valid_from').value = currentCard.valid_from;
    document.getElementById('valid_until').value = currentCard.valid_until;
    
    // Update preview
    updateCardPreview();
}

function changeTemplate() {
    const template = document.getElementById('templateSelect').value;
    updateCardPreview();
}

function changeCardSize() {
    const size = document.getElementById('cardSize').value;
    const preview = document.getElementById('cardPreview');
    
    preview.className = `card-preview size-${size}`;
}

function changeOrientation() {
    const orientation = document.getElementById('orientation').value;
    const preview = document.getElementById('cardPreview');
    
    preview.classList.toggle('portrait', orientation === 'portrait');
    preview.classList.toggle('landscape', orientation === 'landscape');
}

function updateColors() {
    const primaryColor = document.getElementById('primaryColor').value;
    const secondaryColor = document.getElementById('secondaryColor').value;
    const textColor = document.getElementById('textColor').value;
    
    const preview = document.getElementById('cardPreview');
    preview.style.setProperty('--primary-color', primaryColor);
    preview.style.setProperty('--secondary-color', secondaryColor);
    preview.style.setProperty('--text-color', textColor);
}

function updateFontSize() {
    const fontSize = document.getElementById('fontSize').value;
    document.getElementById('fontSizeValue').textContent = fontSize + 'px';
    
    const preview = document.getElementById('cardPreview');
    preview.style.fontSize = fontSize + 'px';
}

function toggleQRCode() {
    const showQR = document.getElementById('showQRCode').checked;
    const qrContainer = document.getElementById('qrCodeContainer');
    
    qrContainer.style.display = showQR ? 'block' : 'none';
}

function toggleBarcode() {
    const showBarcode = document.getElementById('showBarcode').checked;
    // Implement barcode toggle logic
}

function updateCardPreview() {
    // Update the card preview with current settings
    const template = document.getElementById('templateSelect').value;
    const preview = document.getElementById('cardPreview');
    
    preview.className = `card-preview template-${template}`;
}

function generateQRCode() {
    const qrContainer = document.getElementById('qrCodeContainer');
    const qrData = JSON.stringify({
        card_number: currentCard.card_number,
        student_id: currentStudent.student_id,
        valid_until: currentCard.valid_until
    });
    
    // Generate QR code (using a QR code library)
    // This is a placeholder - in production, use a proper QR code library
    qrContainer.innerHTML = `<div class="qr-placeholder">QR Code</div>`;
}

function previewCard() {
    // Show preview modal
    document.getElementById('previewModal').style.display = 'block';
    
    // Copy current preview to modal
    const preview = document.getElementById('cardPreview').innerHTML;
    document.getElementById('previewContainer').innerHTML = preview;
}

function generatePDF() {
    showLoading();
    
    // Collect generation options
    const options = {
        template: document.getElementById('templateSelect').value,
        size: document.getElementById('cardSize').value,
        orientation: document.getElementById('orientation').value,
        includeBack: document.getElementById('includeBack').checked,
        includeWatermark: document.getElementById('includeWatermark').checked,
        highQuality: document.getElementById('highQuality').checked,
        includeSignature: document.getElementById('includeSignature').checked,
        outputFormat: document.getElementById('outputFormat').value,
        copies: document.getElementById('copies').value,
        colors: {
            primary: document.getElementById('primaryColor').value,
            secondary: document.getElementById('secondaryColor').value,
            text: document.getElementById('textColor').value
        }
    };
    
    // Send request to generate PDF
    fetch(`/api/v1/identity-cards/${currentCard.id}/generate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(options)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            // Download the generated PDF
            window.open(data.data.download_url, '_blank');
            showNotification('Identity card generated successfully!', 'success');
        } else {
            showNotification('Failed to generate identity card: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('Error generating identity card: ' + error.message, 'error');
    });
}

function downloadImage() {
    // Convert card preview to image and download
    const preview = document.getElementById('cardPreview');
    // Implementation for image download
}

function printCard() {
    // Print the card
    window.print();
}

function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

function closePreviewModal() {
    document.getElementById('previewModal').style.display = 'none';
}

function goBack() {
    window.history.back();
}

function showNotification(message, type) {
    // Show notification to user
    console.log(`${type.toUpperCase()}: ${message}`);
}
</script>

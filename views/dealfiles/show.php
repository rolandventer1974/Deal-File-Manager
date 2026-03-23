<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo htmlspecialchars($dealFile->getCustomerName()); ?></h1>
            <a href="/" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <!-- Deal File Status -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Reference Number</h6>
                        <code><?php echo htmlspecialchars($dealFile->getReferenceNumber()); ?></code>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Status</h6>
                        <span class="badge bg-<?php 
                            echo ($dealFile->getStatus() === 'incomplete') ? 'warning' : 
                                 (($dealFile->getStatus() === 'pending_review') ? 'info' : 'success'); 
                        ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $dealFile->getStatus())); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Completion</h6>
                        <h4><?php echo $dealFile->getCompletionPercentage(); ?>%</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Last Updated</h6>
                        <small><?php echo $dealFile->getUpdatedAt()->format('M d, Y H:i'); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer & Vehicle Details -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($dealFile->getCustomerEmail() ?? 'N/A'); ?></p>
                        <p><strong>Mobile:</strong> <?php echo htmlspecialchars($dealFile->getCustomerMobile() ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Vehicle Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($dealFile->getVehicleYear() . ' ' . $dealFile->getVehicleMake() . ' ' . $dealFile->getVehicleModel()); ?></p>
                        <p><strong>Sales Executive:</strong> <?php echo htmlspecialchars($dealFile->getSalesExecutiveName() ?? 'N/A'); ?></p>
                        <p><strong>Sales Manager:</strong> <?php echo htmlspecialchars($dealFile->getSalesManagerName() ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Section -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Documents</h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fas fa-upload"></i> Upload Document
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Document Type</th>
                            <th>File Name</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th>Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($documents)): ?>
                            <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $doc->getDocumentType()))); ?></td>
                                    <td><?php echo htmlspecialchars($doc->getFileName()); ?></td>
                                    <td><small><?php echo \DealFileManager\Utils\FileManager::formatFileSize($doc->getFileSize() ?? 0); ?></small></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo ($doc->getStatus() === 'verified') ? 'success' : 
                                                 (($doc->getStatus() === 'rejected') ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($doc->getStatus()); ?>
                                        </span>
                                    </td>
                                    <td><small><?php echo $doc->getUploadedAt()->format('M d, Y'); ?></small></td>
                                    <td>
                                        <a href="/api.php?action=download-document&document_id=<?php echo $doc->getId(); ?>" class="btn btn-sm btn-secondary">Download</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No documents uploaded yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="document_type" class="form-label">Document Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="document_type" name="document_type" required>
                            <option value="">Select document type...</option>
                            <option value="otp">Offer to Purchase</option>
                            <option value="vehicle_inspection">Vehicle Inspection Report</option>
                            <option value="costing_sheet">Costing Sheet</option>
                            <option value="delivery_document">Signed Delivery Document</option>
                            <option value="insurance_quote">Insurance Quote</option>
                            <option value="registration_docs">Registration Documents</option>
                            <option value="service_history">Service History</option>
                            <option value="warranty_info">Warranty Information</option>
                            <option value="finance_agreement">Finance Agreement</option>
                            <option value="other">Other Documents</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="document_file" class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="document_file" name="document" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx">
                        <small class="text-muted">Allowed: PDF, DOC, DOCX, JPG, PNG, XLS, XLSX (Max 50MB)</small>
                    </div>
                    <div class="mb-3">
                        <label for="document_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="document_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('deal_file_id', <?php echo $dealFile->getId(); ?>);
    formData.append('uploaded_by', '<?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'user'); ?>');
    
    try {
        const response = await fetch('/api.php?action=upload-document', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Document uploaded successfully!');
            document.getElementById('uploadForm').reset();
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to upload document'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

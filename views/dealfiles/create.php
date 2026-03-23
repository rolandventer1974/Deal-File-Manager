<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <h1 class="mb-4">Create New Deal File</h1>

        <div class="card">
            <div class="card-body">
                <form id="dealFileForm" method="POST" action="<?php echo $formAction; ?>">
                    <!-- Customer Information -->
                    <h5 class="mb-3">Customer Information</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="customer_name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                            <div class="invalid-feedback" id="error_customer_name"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="customer_id_number" class="form-label">ID Number</label>
                            <input type="text" class="form-control" id="customer_id_number" name="customer_id_number">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="customer_email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="customer_email" name="customer_email">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="customer_mobile" class="form-label">Mobile Number</label>
                            <input type="tel" class="form-control" id="customer_mobile" name="customer_mobile">
                        </div>
                    </div>

                    <!-- Vehicle Information -->
                    <h5 class="mb-3 mt-4">Vehicle Information</h5>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="vehicle_year" class="form-label">Year <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="vehicle_year" name="vehicle_year" min="1900" max="2100" required>
                            <div class="invalid-feedback" id="error_vehicle_year"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="vehicle_make" class="form-label">Make <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="vehicle_make" name="vehicle_make" required>
                            <div class="invalid-feedback" id="error_vehicle_make"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="vehicle_model" class="form-label">Model <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="vehicle_model" name="vehicle_model" required>
                            <div class="invalid-feedback" id="error_vehicle_model"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="vehicle_specification" class="form-label">Specification</label>
                            <input type="text" class="form-control" id="vehicle_specification" name="vehicle_specification">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="vin_number" class="form-label">VIN Number</label>
                            <input type="text" class="form-control" id="vin_number" name="vin_number" pattern="[A-HJ-NPR-Z0-9]{17}">
                            <small class="text-muted">17-character VIN</small>
                        </div>
                    </div>

                    <!-- Deal Information -->
                    <h5 class="mb-3 mt-4">Deal Information</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="sales_executive_name" class="form-label">Sales Executive</label>
                            <input type="text" class="form-control" id="sales_executive_name" name="sales_executive_name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="sales_manager_name" class="form-label">Sales Manager</label>
                            <input type="text" class="form-control" id="sales_manager_name" name="sales_manager_name">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="finance_company" class="form-label">Finance Company</label>
                            <input type="text" class="form-control" id="finance_company" name="finance_company">
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">Create Deal File</button>
                        <a href="/" class="btn btn-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('dealFileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('/dealfiles/store', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Redirect to new deal file
            window.location.href = '/dealfiles/' + data.data.id;
        } else if (data.errors) {
            // Display validation errors
            for (const [field, message] of Object.entries(data.errors)) {
                const errorEl = document.getElementById('error_' + field);
                if (errorEl) {
                    errorEl.textContent = message;
                }
            }
        } else {
            alert('Error: ' + (data.error || 'Failed to create deal file'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

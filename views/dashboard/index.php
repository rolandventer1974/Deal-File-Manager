<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Dashboard</h1>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Incomplete Deal Files</h5>
                        <h2 class="card-text text-primary"><?php echo $totalCount ?? 0; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Sorting -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="filter" class="form-label">Status Filter</label>
                        <select class="form-select" id="filter" name="filter" onchange="this.form.submit()">
                            <option value="incomplete" <?php echo ($filter === 'incomplete') ? 'selected' : ''; ?>>Incomplete</option>
                            <option value="pending" <?php echo ($filter === 'pending') ? 'selected' : ''; ?>>Pending Review</option>
                            <option value="all" <?php echo ($filter === 'all') ? 'selected' : ''; ?>>All</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="sort" class="form-label">Sort By</label>
                        <select class="form-select" id="sort" name="sort" onchange="this.form.submit()">
                            <option value="created_at DESC" <?php echo ($sort === 'created_at DESC') ? 'selected' : ''; ?>>Newest First</option>
                            <option value="created_at ASC" <?php echo ($sort === 'created_at ASC') ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="customer_name ASC" <?php echo ($sort === 'customer_name ASC') ? 'selected' : ''; ?>>Customer A-Z</option>
                            <option value="customer_name DESC" <?php echo ($sort === 'customer_name DESC') ? 'selected' : ''; ?>>Customer Z-A</option>
                            <option value="completion_percentage DESC" <?php echo ($sort === 'completion_percentage DESC') ? 'selected' : ''; ?>>Most Complete</option>
                            <option value="updated_at DESC" <?php echo ($sort === 'updated_at DESC') ? 'selected' : ''; ?>>Recently Updated</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Deal Files Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Deal Files</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Reference #</th>
                            <th>Customer Name</th>
                            <th>Vehicle</th>
                            <th>Status</th>
                            <th>Completion</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($dealFiles)): ?>
                            <?php foreach ($dealFiles as $df): ?>
                                <tr>
                                    <td>
                                        <code><?php echo htmlspecialchars($df->getReferenceNumber()); ?></code>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($df->getCustomerName()); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($df->getVehicleYear() . ' ' . $df->getVehicleMake() . ' ' . $df->getVehicleModel()); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo ($df->getStatus() === 'incomplete') ? 'warning' : 
                                                 (($df->getStatus() === 'pending_review') ? 'info' : 'success'); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $df->getStatus())); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="width: 100px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo $df->getCompletionPercentage(); ?>%" 
                                                 aria-valuenow="<?php echo $df->getCompletionPercentage(); ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?php echo $df->getCompletionPercentage(); ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo $df->getCreatedAt()->format('M d, Y'); ?></small>
                                    </td>
                                    <td>
                                        <a href="/dealfiles/<?php echo $df->getId(); ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No deal files found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Page navigation">
                        <ul class="pagination mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>&sort=<?php echo urlencode($sort); ?>&filter=<?php echo $filter; ?>">Previous</a></li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&sort=<?php echo urlencode($sort); ?>&filter=<?php echo $filter; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&sort=<?php echo urlencode($sort); ?>&filter=<?php echo $filter; ?>">Next</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

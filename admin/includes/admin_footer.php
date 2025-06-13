<?php
use function App\__;
?>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <span class="text-muted">&copy; <?= date('Y') ?> <?= __('admin_panel') ?>. <?= __('all_rights_reserved') ?></span>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted"><?= __('version') ?> 1.0.0</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="../../../public/js/bootstrap.bundle.min.js"></script>
    <!-- Admin JavaScript -->
    <script src="../../../public/js/admin.js"></script>
</body>
</html>
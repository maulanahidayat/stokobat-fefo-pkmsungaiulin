        </div> <!-- End of content -->
    </div> <!-- End of wrapper -->

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
            });
        });
    </script>

    <?php if (isset($_SESSION['success'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: '<?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); ?>',
            timer: 2500,
            showConfirmButton: false
        });
    </script>
    <?php unset($_SESSION['success']); endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: '<?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); ?>',
            showConfirmButton: true,
            confirmButtonColor: '#d33'
        });
    </script>
    <?php unset($_SESSION['error']); endif; ?>
</body>
</html>

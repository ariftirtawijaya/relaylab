</div> <!-- /container -->

<!-- jQuery (dibutuhkan Select2) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // aktifkan select2 buat semua dropdown komponen yang kita tandai pakai class .js-component-select
    $(document).ready(function () {
        $('.js-component-select').select2({
            width: '100%',
            placeholder: 'Cari komponen…',
            allowClear: true
        });

        // Begitu user pilih komponen dari dropdown, otomatis fokus ke qty
        $('.js-component-select').on('select2:select', function (e) {
            const qtyField = document.getElementById('qty_per_product_input');
            if (qtyField) {
                qtyField.focus();
                qtyField.select();
            }
        });
    });
</script>

</body>

</html>
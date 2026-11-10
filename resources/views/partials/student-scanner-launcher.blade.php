<script nonce="{{ csp_nonce() }}">
    window.openStudentScanner = function(initialMode = 'scan', autoPaste = false) {
        const scanUrl = new URL(@json(route('mobile.scan')), window.location.origin);
        scanUrl.searchParams.set(initialMode === 'code' ? 'open_code' : 'open_scanner', '1');

        if (autoPaste) {
            scanUrl.searchParams.set('paste', '1');
        }

        window.location.assign(scanUrl.toString());
    };
</script>

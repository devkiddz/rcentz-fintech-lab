<script>
    (() => {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.classList.toggle('dark', savedTheme ? savedTheme === 'dark' : prefersDark);
    })();
</script>
<?php /**PATH C:\xampp\htdocs\tesla.com\resources\views/partials/theme-init.blade.php ENDPATH**/ ?>
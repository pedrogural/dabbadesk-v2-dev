    <script>
        document.addEventListener('click', async function (event) {
            const button = event.target.closest('[data-copy-value]');
            if (!button) return;

            const original = button.textContent;
            const value = button.dataset.copyValue || '';

            try {
                await navigator.clipboard.writeText(value);
                button.textContent = '✓';
                button.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
                window.setTimeout(function () {
                    button.textContent = original;
                    button.classList.remove('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
                }, 1200);
            } catch (error) {
                const textarea = document.createElement('textarea');
                textarea.value = value;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();
                document.execCommand('copy');
                textarea.remove();
                button.textContent = '✓';
                window.setTimeout(function () { button.textContent = original; }, 1200);
            }
        });
    </script>
<?php /**PATH /var/www/dabba-test/dabbadesk-v2/resources/views/orders/show/_copy_script.blade.php ENDPATH**/ ?>
import { onMounted, onUnmounted } from 'vue';

export function useBarcodeScanner(onScan) {
    let buffer = '';
    let lastKeyTime = 0;
    const SCAN_THRESHOLD = 50;

    function handleKeydown(e) {
        const now = Date.now();

        if (e.key === 'Enter') {
            if (buffer.length > 0) {
                onScan(buffer);
                buffer = '';
            }
            return;
        }

        if (e.key.length === 1) {
            if (now - lastKeyTime > SCAN_THRESHOLD) {
                buffer = '';
            }
            buffer += e.key;
            lastKeyTime = now;
        }
    }

    onMounted(() => {
        window.addEventListener('keydown', handleKeydown);
    });

    onUnmounted(() => {
        window.removeEventListener('keydown', handleKeydown);
    });
}

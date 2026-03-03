@if (filled($pdfUrl))
    <iframe
        src="{{ $pdfUrl }}"
        style="display: block; width: 100%; max-width: 100%; height: clamp(24rem, 70vh, 56rem); border: 1px solid #d1d5db; border-radius: 0.5rem; box-sizing: border-box;"
        title="Submission PDF Preview"
    ></iframe>
@else
    <p class="text-sm text-gray-500">PDF preview is unavailable.</p>
@endif

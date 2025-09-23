<div class="space-y-4">
    {{-- Payment Details --}}
    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
        <h3 class="text-lg font-semibold mb-3">Payment Details</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium">Invoice ID:</span>
                <span class="ml-2">#{{ $paymentConfirmation->invoice_id }}</span>
            </div>
            <div>
                <span class="font-medium">Amount:</span>
                <span class="ml-2">IDR {{ number_format($paymentConfirmation->amount, 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="font-medium">Bank:</span>
                <span class="ml-2">{{ $paymentConfirmation->bank_name ?? '-' }}</span>
            </div>
            <div>
                <span class="font-medium">Reference:</span>
                <span class="ml-2">{{ $paymentConfirmation->reference_no ?? '-' }}</span>
            </div>
            <div>
                <span class="font-medium">Submitted By:</span>
                <span class="ml-2">{{ $paymentConfirmation->submitted_by }}</span>
            </div>
            <div>
                <span class="font-medium">Submitted At:</span>
                <span class="ml-2">{{ $paymentConfirmation->created_at->format('d M Y H:i') }}</span>
            </div>
        </div>

        @if ($paymentConfirmation->notes)
            <div class="mt-3">
                <span class="font-medium">Notes:</span>
                <p class="mt-1 text-gray-700 dark:text-gray-300">{{ $paymentConfirmation->notes }}</p>
            </div>
        @endif
    </div>

    {{-- Evidence Image --}}
    @if ($evidencePath)
        <div class="text-center">
            <h3 class="text-lg font-semibold mb-3">Payment Evidence</h3>

            @php
                $fileExtension = pathinfo($evidencePath, PATHINFO_EXTENSION);
                $isImage = in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                $isPdf = strtolower($fileExtension) === 'pdf';
            @endphp

            @if ($isImage)
                <div class="inline-block border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden">
                    <img src="{{ Storage::disk('public')->url($evidencePath) }}" alt="Payment Evidence"
                        class="max-w-full max-h-96 object-contain" onclick="window.open(this.src, '_blank')"
                        style="cursor: pointer;" />
                </div>
                <p class="text-sm text-gray-500 mt-2">Click image to view in full size</p>
            @elseif($isPdf)
                <div class="space-y-3">
                    <div
                        class="flex items-center justify-center p-8 border border-gray-300 dark:border-gray-600 rounded-lg">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto text-red-500 mb-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-lg font-medium">PDF Document</p>
                            <p class="text-sm text-gray-500">{{ basename($evidencePath) }}</p>
                        </div>
                    </div>
                    <div class="flex space-x-2 justify-center">
                        <a href="{{ Storage::disk('public')->url($evidencePath) }}" target="_blank"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                </path>
                            </svg>
                            View PDF
                        </a>
                        <a href="{{ Storage::disk('public')->url($evidencePath) }}" download
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Download
                        </a>
                    </div>
                </div>
            @else
                <div
                    class="flex items-center justify-center p-8 border border-gray-300 dark:border-gray-600 rounded-lg">
                    <div class="text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <p class="text-lg font-medium">Document</p>
                        <p class="text-sm text-gray-500">{{ basename($evidencePath) }}</p>
                        <a href="{{ Storage::disk('public')->url($evidencePath) }}" target="_blank"
                            class="mt-2 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Download File
                        </a>
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="text-center py-8">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            <p class="text-gray-500">No evidence file uploaded</p>
        </div>
    @endif
</div>

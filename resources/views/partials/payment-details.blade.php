@php
    $paymentDetails = $paymentDetails ?? data_get($branding ?? [], 'payment_details', []);
    $title = $title ?? 'Payment Details';
    $subtitle = $subtitle ?? null;
    $showTitle = $showTitle ?? true;
    $compact = $compact ?? false;
    $emptyMessage = $emptyMessage ?? 'Payment details will appear here once the shop team adds them.';
@endphp

<div class="payment-details-shell">
    @if ($showTitle)
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-3">
            <div>
                <h6 class="fw-semibold mb-1">{{ $title }}</h6>
                @if ($subtitle)
                    <p class="small text-muted mb-0">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
    @endif

    @if ($paymentDetails !== [])
        <div class="vstack gap-3">
            @foreach ($paymentDetails as $detail)
                <div class="payment-detail-card{{ $compact ? ' is-compact' : '' }}">
                    <div class="payment-detail-meta">
                        <div>
                            <div class="payment-detail-label">Name</div>
                            <div class="fw-semibold">{{ data_get($detail, 'name') }}</div>
                        </div>
                        <div>
                            <div class="payment-detail-label">Bank</div>
                            <div class="fw-semibold">{{ data_get($detail, 'bank_name') }}</div>
                        </div>
                        <div>
                            <div class="payment-detail-label">Bank Number</div>
                            <div class="fw-semibold font-monospace">{{ data_get($detail, 'bank_number') }}</div>
                        </div>
                    </div>

                    @if (data_get($detail, 'qr_images'))
                        <div class="row g-3 mt-2">
                            @foreach (data_get($detail, 'qr_images', []) as $image)
                                <div class="col-sm-6 col-lg-4">
                                    <img
                                        src="{{ data_get($image, 'url') }}"
                                        alt="{{ data_get($detail, 'name') }} QR code{{ count(data_get($detail, 'qr_images', [])) > 1 ? ' '.$loop->iteration : '' }}"
                                        class="payment-qr-thumb"
                                        data-lightbox-image
                                        tabindex="0"
                                    >
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @elseif ($emptyMessage)
        <p class="small text-muted mb-0">{{ $emptyMessage }}</p>
    @endif
</div>
<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('customer.reservations.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
                <div>
                    <h5 class="mb-0 fw-bold">Reservation Details</h5>
                    <p class="mb-0 text-muted small">Reference {{ $reservation->reference }}</p>
                </div>
            </div>
            <a href="{{ route('items.index') }}" class="btn btn-sm btn-outline-custom"><i class="bi bi-grid me-1"></i>Browse Items</a>
            <a href="{{ route('cart.index') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-cart"></i> View Cart</a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4">
            {{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->has('customer_request'))
        <div class="alert alert-danger alert-dismissible fade show mb-4">
            {{ $errors->first('customer_request') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $requestTypeLabel = match($reservation->customer_request_type) {
            'cancellation' => 'Cancellation',
            'reschedule' => 'Reschedule',
            default => 'N/A',
        };

        $canRequestCancellation = $reservation->canCustomerRequestCancellation();
        $canRequestReschedule = $reservation->canCustomerRequestReschedule();
        $canExtendReservation = $reservation->isExtendable();
        $paymentDetails = data_get($branding, 'payment_details', []);

        $isExpiringSoon = $reservation->status->value === 'pending'
            && $reservation->expires_at
            && $reservation->expires_at->lte(now()->addDay())
            && $reservation->expires_at->gte(now());
    @endphp

    @if ($isExpiringSoon)
        <div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">
            <i class="bi bi-alarm-fill fs-5 mt-1"></i>
            <div>
                This reservation expires {{ $reservation->expires_at?->diffForHumans() }}. Please pay in person, or extend it once for another 24 hours before it expires.
            </div>
        </div>
    @elseif ($reservation->status->value === 'ready_for_pickup')
        <div class="alert alert-info d-flex align-items-start gap-2 mb-4" role="alert">
            <i class="bi bi-bell-fill fs-5 mt-1"></i>
            <div>
                Your items are ready for pickup. Please visit the shop on your scheduled pickup date and settle payment in person.
            </div>
        </div>
    @elseif ($reservation->status->value === 'overdue')
        <div class="alert alert-danger d-flex align-items-start gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-octagon-fill fs-5 mt-1"></i>
            <div>
                This reservation is overdue. Contact the shop team as soon as possible.
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card glass-card surface-section">
                <div class="card-header"><strong>Reservation Summary</strong></div>
                <div class="card-body">
                    <div class="row g-3 small">
                        <div class="col-sm-6"><div class="data-grid-card"><span class="data-grid-label">Reference</span><div class="data-grid-value font-monospace">{{ $reservation->reference }}</div></div></div>
                        <div class="col-sm-6"><div class="data-grid-card"><span class="data-grid-label">Status</span><div class="data-grid-value"><x-status-badge type="reservation" :value="$reservation->status->value" :label="$reservation->status->label()" /></div></div></div>
                        <div class="col-sm-6"><div class="data-grid-card"><span class="data-grid-label">Payment</span><div class="data-grid-value"><x-status-badge type="payment" :value="$reservation->payment_status->value" :label="$reservation->payment_status->label()" /></div></div></div>
                        <div class="col-sm-6"><div class="data-grid-card"><span class="data-grid-label">Pickup Date</span><div class="data-grid-value">{{ optional($reservation->pickup_date)->format('M d, Y') }}</div></div></div>
                        <div class="col-sm-6"><div class="data-grid-card"><span class="data-grid-label">Pickup Slot</span><div class="data-grid-value">{{ ucfirst(str_replace('_', ' ', (string) $reservation->pickup_slot)) }}</div></div></div>
                        <div class="col-sm-6"><div class="data-grid-card"><span class="data-grid-label">Expires</span><div class="data-grid-value">{{ optional($reservation->expires_at)->format('M d, Y H:i') }}</div></div></div>
                        <div class="col-sm-6"><div class="data-grid-card"><span class="data-grid-label">Extension Used</span><div class="data-grid-value">{{ $reservation->extended_at ? 'Yes' : 'No' }}</div></div></div>
                        <div class="col-sm-6"><div class="data-grid-card"><span class="data-grid-label">Total</span><div class="data-grid-value text-success">&#8369;{{ number_format((float) $reservation->total_amount, 2) }}</div></div></div>
                    </div>

                    @if ($canExtendReservation)
                        <hr>
                        <form method="POST" action="{{ route('customer.reservations.extend', $reservation) }}" data-confirm-action data-confirm-message='Extend this reservation by 24 hours?' data-confirm-variant='primary' data-confirm-label='Confirm'>
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill">
                                <i class="bi bi-clock-history me-1"></i>Extend Reservation by 24 Hours
                            </button>
                        </form>
                    @endif

                    @if ($reservation->notes)
                        <hr>
                        <h6 class="fw-semibold small mb-2">Reservation Notes</h6>
                        <p class="mb-0 small text-muted">{{ $reservation->notes }}</p>
                    @endif
                </div>
            </div>

            @if ($paymentDetails !== [])
                <div class="card glass-card surface-section mt-4">
                    <div class="card-header"><strong>Payment Details</strong></div>
                    <div class="card-body">
                        @include('partials.payment-details', [
                            'paymentDetails' => $paymentDetails,
                            'showTitle' => false,
                            'compact' => true,
                            'emptyMessage' => null,
                        ])

                        <p class="small text-muted mt-3 mb-0">These details are for manual payment reference only. Bring your reservation reference and valid ID during pickup.</p>
                    </div>
                </div>
            @endif

            <div class="card glass-card surface-section mt-4">
                <div class="card-header"><strong>Need Help?</strong></div>
                <div class="card-body small text-muted">
                    Bring your reservation reference and valid ID during pickup.
                    If your schedule changes, coordinate with the shop team as early as possible.
                </div>
            </div>


            <div id="self-service-requests" class="card glass-card surface-section mt-4">
                <div class="card-header"><strong>Self-Service Requests</strong></div>
                <div class="card-body">
                    @if ($reservation->customer_request_status !== null)
                        <div class="surface-note mb-3">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <span class="fw-semibold">Latest Request: {{ $requestTypeLabel }}</span>
                                <x-status-badge type="request" :value="$reservation->customer_request_status" :label="ucfirst((string) $reservation->customer_request_status)" />
                            </div>
                            @if ($reservation->customer_request_reason)
                                <p class="small text-muted mb-2">{{ $reservation->customer_request_reason }}</p>
                            @endif
                            @if ($reservation->customer_request_type === 'reschedule' && $reservation->customer_requested_pickup_date)
                                <div class="small mb-2">
                                    Requested pickup: <strong>{{ optional($reservation->customer_requested_pickup_date)->format('M d, Y') }}</strong>
                                    ({{ ucfirst(str_replace('_', ' ', (string) $reservation->customer_requested_pickup_slot)) }})
                                </div>
                            @endif
                            @if ($reservation->customer_request_admin_note)
                                <div class="small text-muted">Admin note: {{ $reservation->customer_request_admin_note }}</div>
                            @endif
                            @if ($reservation->customer_requested_at)
                                <div class="small text-muted mt-2">Submitted {{ optional($reservation->customer_requested_at)->diffForHumans() }}</div>
                            @endif
                        </div>
                    @elseif ($reservation->reservationItems->where('cancel_pending', true)->count() > 0)
                        <div class="surface-note mb-3">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <span class="fw-semibold">Latest Request: Per-Item Cancellation</span>
                                <x-status-badge type="request" value="pending" label="Pending" />
                            </div>
                            <div class="small text-muted mb-2">
                                Cancellation request(s) for the following item(s) are pending admin approval:
                                <strong>{{ $reservation->reservationItems->where('cancel_pending', true)->map(fn($i) => $i->item?->name ?? 'Archived Item')->join(', ') }}</strong>
                            </div>
                        </div>
                    @endif

                    @if (($canRequestCancellation || $canRequestReschedule) && $reservation->customer_request_status !== 'pending')
                        @if ($canRequestCancellation)
                            <form id="request-cancellation" method="POST" action="{{ route('customer.reservations.request-cancellation', $reservation) }}" class="mb-3" data-confirm-action data-confirm-message='Request cancellation for this reservation?' data-confirm-variant='danger' data-confirm-label='Confirm'>
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">Request Cancellation</button>
                            </form>
                        @endif

                        <div class="accordion" id="customerRequestActions">
                            @if ($canRequestReschedule)
                                @php
                                    $rescheduleSlots = \App\Enums\PickupSlot::cases();
                                    $selectedRescheduleSlot = old('requested_pickup_slot', $rescheduleSlots[0]->value ?? null);
                                    $selectedRescheduleSlotLabel = collect($rescheduleSlots)->firstWhere('value', $selectedRescheduleSlot)?->label() ?? 'Select slot';
                                @endphp
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="rescheduleRequestHeading">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#rescheduleRequestCollapse" aria-expanded="false" aria-controls="rescheduleRequestCollapse">
                                            Request Pickup Reschedule
                                        </button>
                                    </h2>
                                    <div id="rescheduleRequestCollapse" class="accordion-collapse collapse" aria-labelledby="rescheduleRequestHeading" data-bs-parent="#customerRequestActions">
                                        <div class="accordion-body">
                                            <form method="POST" action="{{ route('customer.reservations.request-reschedule', $reservation) }}" data-confirm-action data-confirm-message='Submit this pickup reschedule request?' data-confirm-variant='primary' data-confirm-label='Confirm'>
                                                @csrf
                                                @method('PATCH')
                                                <div class="row g-3">
                                                    <div class="col-sm-6">
                                                        <label class="form-label form-label-modern">Requested Pickup Date</label>
                                                        <input type="text" name="requested_pickup_date" data-flatpickr-date data-min-date="{{ now()->addDay()->toDateString() }}" value="{{ old('requested_pickup_date') }}" class="form-control form-control-modern @error('requested_pickup_date') is-invalid @enderror" required>
                                                        @error('requested_pickup_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label class="form-label form-label-modern">Requested Slot</label>
                                                        <div class="dropdown" data-option-dropdown>
                                                            <input type="hidden" name="requested_pickup_slot" value="{{ $selectedRescheduleSlot }}" data-option-input required>
                                                            <button class="btn btn-outline-custom w-100 d-flex justify-content-between align-items-center rounded-pill" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <span data-option-label>{{ $selectedRescheduleSlotLabel }}</span>
                                                            </button>
                                                            <ul class="dropdown-menu w-100 mt-2 border-0 shadow-lg glass-dropdown-menu">
                                                                @foreach ($rescheduleSlots as $slot)
                                                                    <li>
                                                                        <button type="button" class="dropdown-item" data-option-value="{{ $slot->value }}" data-option-text="{{ $slot->label() }}">{{ $slot->label() }}</button>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                        @error('requested_pickup_slot') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label form-label-modern">Reason <span class="text-muted fw-normal">(optional)</span></label>
                                                        <textarea name="request_reason" rows="3" class="form-control form-control-modern @error('request_reason') is-invalid @enderror">{{ old('request_reason') }}</textarea>
                                                        @error('request_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                    </div>
                                                </div>

                                                <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill mt-3">Submit Reschedule Request</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @elseif ($reservation->customer_request_status === 'pending')
                        <p class="small text-muted mb-0">Your latest request is under review. You can submit another request once this one is processed.</p>
                    @else
                        <p class="small text-muted mb-0">Cancellation stays available until a reservation is completed. Reschedule remains available only for pending or overdue reservations.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card glass-card surface-section table-card">
                <div class="card-header"><strong>Reserved Items</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Line Total</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($reservation->reservationItems as $lineItem)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if ($lineItem->item?->imageUrl())
                                                <img src="{{ $lineItem->item->imageUrl() }}" alt="{{ $lineItem->item->name }}" class="inventory-thumb-sm" data-lightbox-image tabindex="0">
                                            @else
                                                <span class="inventory-thumb-fallback"><i class="bi bi-image"></i></span>
                                            @endif
                                            <span>{{ $lineItem->item?->name ?? 'Archived Item' }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $lineItem->quantity }}</td>
                                    <td>&#8369;{{ number_format((float) $lineItem->unit_price, 2) }}</td>
                                    <td>&#8369;{{ number_format((float) $lineItem->line_total, 2) }}</td>
                                    <td>
                                        @if($reservation->canCustomerRequestCancellation() && $lineItem->item)
                                            @if($lineItem->cancel_pending)
                                                <span class="badge text-bg-warning">Cancellation Requested (Pending)</span>
                                            @else
                                                <form action="{{ route('customer.reservations.cancel-item', [$reservation, $lineItem]) }}" method="POST" style="display:inline" data-confirm-action data-confirm-message='Request cancellation for this item?' data-confirm-variant='danger' data-confirm-label='Confirm'>
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="window.location.hash='self-service-requests'">Request Cancellation</button>
                                                </form>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total</th>
                                <th class="text-success">&#8369;{{ number_format((float) $reservation->total_amount, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

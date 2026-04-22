<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.reservations.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
            <h5 class="mb-0 fw-bold">Reservation Details</h5>
            @if ($wasNewReservation ?? false)
                <x-status-badge type="notification-event" value="new_reservation" label="New" />
            @endif
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

    @if ($errors->any() && !$errors->has('customer_request'))
        <div class="alert alert-danger alert-dismissible fade show mb-4">
            {{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $requestTypeLabel = match($reservation->customer_request_type) {
            'cancellation' => 'Cancellation',
            'reschedule' => 'Reschedule',
            default => 'N/A',
        };
        $pendingCancelItems = $reservation->reservationItems->where('cancel_pending', true);
    @endphp

    @if ($pendingCancelItems->isNotEmpty())
        <div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">
            <i class="bi bi-x-circle-fill fs-5 mt-1"></i>
            <div>
                <strong>{{ $reservation->user?->name ?? 'Customer' }}</strong> has requested cancellation for
                {{ $pendingCancelItems->count() }} item{{ $pendingCancelItems->count() > 1 ? 's' : '' }}:
                <strong>{{ $pendingCancelItems->map(fn($i) => $i->item?->name ?? 'Archived Item')->join(', ') }}</strong>.
                Review the <a href="#line-items" class="alert-link">Line Items</a> table below to approve or decline.
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card glass-card surface-section h-100">
                <div class="card-header"><strong>Reservation Info</strong></div>
                <div class="card-body">
                    <div class="row g-3 small">
                        <div class="col-sm-6"><div class="data-grid-card"><span class="data-grid-label">Reference</span><div class="data-grid-value font-monospace">{{ $reservation->reference }}</div></div></div>
                        <div class="col-sm-6"><div class="data-grid-card"><span class="data-grid-label">Customer</span><div class="data-grid-value">{{ $reservation->user?->name ?? 'Deleted User' }}</div></div></div>
                        <div class="col-sm-6"><div class="data-grid-card"><span class="data-grid-label">Email</span><div class="data-grid-value">{{ $reservation->user?->email ?? 'N/A' }}</div></div></div>
                        <div class="col-sm-6"><div class="data-grid-card"><span class="data-grid-label">Pickup</span><div class="data-grid-value">{{ optional($reservation->pickup_date)->format('M d, Y') }} {{ $reservation->pickup_slot }}</div></div></div>
                        <div class="col-sm-6"><div class="data-grid-card"><span class="data-grid-label">Expires</span><div class="data-grid-value">{{ optional($reservation->expires_at)->format('M d, Y H:i') }}</div></div></div>
                        <div class="col-sm-6"><div class="data-grid-card"><span class="data-grid-label">Total</span><div class="data-grid-value">&#8369;{{ number_format((float) $reservation->total_amount, 2) }}</div></div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7" id="line-items">
            <div class="card glass-card surface-section table-card">
                <div class="card-header"><strong>Line Items</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Line Total</th><th>Cancel Request</th></tr>
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
                                        {{-- Per-item approve/decline removed; use self-service only --}}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card glass-card surface-section">
                <div class="card-header"><strong>Customer Self-Service Request</strong></div>
                <div class="card-body">
                    @if ($reservation->customer_request_status !== null)
                        <div class="surface-note mb-3">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <div>
                                    <div class="fw-semibold">{{ $requestTypeLabel }} Request</div>
                                    @if ($reservation->customer_requested_at)
                                        <div class="small text-muted">Submitted {{ optional($reservation->customer_requested_at)->diffForHumans() }}</div>
                                    @endif
                                </div>
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
                        </div>

                        @if ($reservation->customer_request_status === 'pending')
                            <form method="POST" action="{{ route('admin.reservations.update-customer-request', $reservation) }}" data-confirm-action data-confirm-message='Type "confirm" to process this customer request.'>
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="from_self_service" value="1">
                                <div class="mb-3">
                                    <label class="form-label form-label-modern">Admin Note <span class="text-muted fw-normal">(optional)</span></label>
                                    <textarea name="admin_note" class="form-control form-control-modern @error('admin_note') is-invalid @enderror" rows="3">{{ old('admin_note') }}</textarea>
                                    @error('admin_note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="action-row">
                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success rounded-pill">Approve Request</button>
                                    <button type="submit" name="action" value="decline" class="btn btn-sm btn-outline-danger rounded-pill">Decline Request</button>
                                </div>
                            </form>
                        @endif
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
                            <div class="mt-3">
                                <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                        <tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Line Total</th><th>Action</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($reservation->reservationItems as $lineItem)
                                            @if ($lineItem->cancel_pending)
                                                <tr>
                                                    <td>{{ $lineItem->item?->name ?? 'Archived Item' }}</td>
                                                    <td>{{ $lineItem->quantity }}</td>
                                                    <td>&#8369;{{ number_format((float) $lineItem->unit_price, 2) }}</td>
                                                    <td>&#8369;{{ number_format((float) $lineItem->line_total, 2) }}</td>
                                                    <td>
                                                        <form method="POST" action="{{ route('admin.reservations.item-cancel-request', [$reservation, $lineItem]) }}" class="d-flex gap-1 flex-wrap" data-confirm-action data-confirm-message='Type "confirm" to process this item cancellation request.'>
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success rounded-pill">Approve</button>
                                                            <button type="submit" name="action" value="decline" class="btn btn-sm btn-outline-danger rounded-pill">Decline</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">No customer self-service request has been submitted yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card glass-card surface-section">
                <div class="card-header"><strong>Update Status</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.reservations.update-status', $reservation) }}" data-confirm-action data-confirm-message='Type "confirm" to save the reservation status changes.'>
                        @csrf
                        @method('PATCH')
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label form-label-modern">Reservation Status</label>
                                <select name="status" class="form-select form-control-modern @error('status') is-invalid @enderror" required>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->value }}" @selected(old('status', $reservation->status->value) === $status->value)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label form-label-modern">Payment Status</label>
                                <select name="payment_status" class="form-select form-control-modern @error('payment_status') is-invalid @enderror" required>
                                    @foreach ($paymentStatuses as $paymentStatus)
                                        <option value="{{ $paymentStatus->value }}" @selected(old('payment_status', $reservation->payment_status->value) === $paymentStatus->value)>{{ $paymentStatus->label() }}</option>
                                    @endforeach
                                </select>
                                @error('payment_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label form-label-modern">Admin Notes</label>
                                <textarea name="notes" class="form-control form-control-modern @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $reservation->notes) }}</textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">Use Ready for Pickup to notify the customer in-app that the item is prepared and awaiting in-person payment.</div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary rounded-pill">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

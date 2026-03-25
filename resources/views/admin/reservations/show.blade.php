<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.reservations.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
            <h5 class="mb-0 fw-bold">Reservation Details</h5>
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
        $requestStatusClass = match($reservation->customer_request_status) {
            'pending' => 'bg-warning text-dark',
            'approved' => 'bg-success',
            'declined' => 'bg-danger',
            default => 'bg-secondary',
        };

        $requestTypeLabel = match($reservation->customer_request_type) {
            'cancellation' => 'Cancellation',
            'reschedule' => 'Reschedule',
            default => 'N/A',
        };
    @endphp

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><strong>Reservation Info</strong></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Reference</dt><dd class="col-7 font-monospace">{{ $reservation->reference }}</dd>
                        <dt class="col-5 text-muted">Customer</dt><dd class="col-7">{{ $reservation->user?->name ?? 'Deleted User' }}</dd>
                        <dt class="col-5 text-muted">Email</dt><dd class="col-7">{{ $reservation->user?->email ?? 'N/A' }}</dd>
                        <dt class="col-5 text-muted">Pickup</dt><dd class="col-7">{{ optional($reservation->pickup_date)->format('M d, Y') }} {{ $reservation->pickup_slot }}</dd>
                        <dt class="col-5 text-muted">Expires</dt><dd class="col-7">{{ optional($reservation->expires_at)->format('M d, Y H:i') }}</dd>
                        <dt class="col-5 text-muted">Total</dt><dd class="col-7 fw-bold">&#8369;{{ number_format((float) $reservation->total_amount, 2) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><strong>Line Items</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Line Total</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($reservation->reservationItems as $lineItem)
                                <tr>
                                    <td>{{ $lineItem->item?->name ?? 'Archived Item' }}</td>
                                    <td>{{ $lineItem->quantity }}</td>
                                    <td>&#8369;{{ number_format((float) $lineItem->unit_price, 2) }}</td>
                                    <td>&#8369;{{ number_format((float) $lineItem->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header"><strong>Customer Self-Service Request</strong></div>
                <div class="card-body">
                    @if ($reservation->customer_request_status !== null)
                        <div class="border rounded-3 p-3 mb-3 bg-light-subtle">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <div>
                                    <div class="fw-semibold">{{ $requestTypeLabel }} Request</div>
                                    @if ($reservation->customer_requested_at)
                                        <div class="small text-muted">Submitted {{ optional($reservation->customer_requested_at)->diffForHumans() }}</div>
                                    @endif
                                </div>
                                <span class="badge rounded-pill {{ $requestStatusClass }}">{{ ucfirst((string) $reservation->customer_request_status) }}</span>
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
                            <form method="POST" action="{{ route('admin.reservations.update-customer-request', $reservation) }}">
                                @csrf
                                @method('PATCH')
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Admin Note <span class="text-muted fw-normal">(optional)</span></label>
                                    <textarea name="admin_note" class="form-control @error('admin_note') is-invalid @enderror" rows="3">{{ old('admin_note') }}</textarea>
                                    @error('admin_note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve Request</button>
                                    <button type="submit" name="action" value="decline" class="btn btn-sm btn-outline-danger">Decline Request</button>
                                </div>
                            </form>
                        @endif
                    @else
                        <p class="text-muted mb-0">No customer self-service request has been submitted yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header"><strong>Update Status</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.reservations.update-status', $reservation) }}">
                        @csrf
                        @method('PATCH')
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Reservation Status</label>
                                <select name="status" class="form-select" required>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->value }}" @selected($reservation->status === $status)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Payment Status</label>
                                <select name="payment_status" class="form-select" required>
                                    @foreach ($paymentStatuses as $paymentStatus)
                                        <option value="{{ $paymentStatus->value }}" @selected($reservation->payment_status === $paymentStatus)>{{ $paymentStatus->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium">Admin Notes</label>
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $reservation->notes) }}</textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

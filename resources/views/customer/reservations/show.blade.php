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
        $statusBadgeClass = match($reservation->status->value) {
            'pending' => 'bg-warning text-dark',
            'completed' => 'bg-success',
            'overdue' => 'bg-danger',
            'expired' => 'bg-secondary',
            default => 'bg-secondary',
        };

        $paymentBadgeClass = match($reservation->payment_status->value) {
            'pending' => 'bg-warning text-dark',
            'completed' => 'bg-success',
            'overdue' => 'bg-danger',
            default => 'bg-secondary',
        };

        $requestStatusBadgeClass = match($reservation->customer_request_status) {
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

        $canRequestChanges = in_array($reservation->status->value, ['pending', 'overdue'], true);

        $isExpiringSoon = $reservation->status->value === 'pending'
            && $reservation->expires_at
            && $reservation->expires_at->lte(now()->addDay())
            && $reservation->expires_at->gte(now());
    @endphp

    @if ($isExpiringSoon)
        <div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">
            <i class="bi bi-alarm-fill fs-5 mt-1"></i>
            <div>
                This reservation expires {{ $reservation->expires_at?->diffForHumans() }}. Please pay in person to keep your items reserved.
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
            <div class="card">
                <div class="card-header"><strong>Reservation Summary</strong></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Reference</dt><dd class="col-7 font-monospace">{{ $reservation->reference }}</dd>
                        <dt class="col-5 text-muted">Status</dt><dd class="col-7"><span class="badge rounded-pill {{ $statusBadgeClass }}">{{ $reservation->status->label() }}</span></dd>
                        <dt class="col-5 text-muted">Payment</dt><dd class="col-7"><span class="badge rounded-pill {{ $paymentBadgeClass }}">{{ $reservation->payment_status->label() }}</span></dd>
                        <dt class="col-5 text-muted">Pickup Date</dt><dd class="col-7">{{ optional($reservation->pickup_date)->format('M d, Y') }}</dd>
                        <dt class="col-5 text-muted">Pickup Slot</dt><dd class="col-7">{{ ucfirst(str_replace('_', ' ', (string) $reservation->pickup_slot)) }}</dd>
                        <dt class="col-5 text-muted">Expires</dt><dd class="col-7">{{ optional($reservation->expires_at)->format('M d, Y H:i') }}</dd>
                        <dt class="col-5 text-muted">Total</dt><dd class="col-7 fw-bold text-success">&#8369;{{ number_format((float) $reservation->total_amount, 2) }}</dd>
                    </dl>

                    @if ($reservation->notes)
                        <hr>
                        <h6 class="fw-semibold small mb-2">Your Notes</h6>
                        <p class="mb-0 small text-muted">{{ $reservation->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header"><strong>Need Help?</strong></div>
                <div class="card-body small text-muted">
                    Bring your reservation reference and valid ID during pickup.
                    If your schedule changes, coordinate with the shop team as early as possible.
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header"><strong>Self-Service Requests</strong></div>
                <div class="card-body">
                    @if ($reservation->customer_request_status !== null)
                        <div class="border rounded-3 p-3 bg-light-subtle mb-3">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <span class="fw-semibold">Latest Request: {{ $requestTypeLabel }}</span>
                                <span class="badge rounded-pill {{ $requestStatusBadgeClass }}">{{ ucfirst((string) $reservation->customer_request_status) }}</span>
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
                    @endif

                    @if ($canRequestChanges && $reservation->customer_request_status !== 'pending')
                        <div class="accordion" id="customerRequestActions">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="cancelRequestHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cancelRequestCollapse" aria-expanded="false" aria-controls="cancelRequestCollapse">
                                        Request Cancellation
                                    </button>
                                </h2>
                                <div id="cancelRequestCollapse" class="accordion-collapse collapse" aria-labelledby="cancelRequestHeading" data-bs-parent="#customerRequestActions">
                                    <div class="accordion-body">
                                        <form method="POST" action="{{ route('customer.reservations.request-cancellation', $reservation) }}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="mb-3">
                                                <label class="form-label fw-medium">Reason</label>
                                                <textarea name="request_reason" rows="3" class="form-control @error('request_reason') is-invalid @enderror" required>{{ old('request_reason') }}</textarea>
                                                @error('request_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Submit Cancellation Request</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="rescheduleRequestHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#rescheduleRequestCollapse" aria-expanded="false" aria-controls="rescheduleRequestCollapse">
                                        Request Pickup Reschedule
                                    </button>
                                </h2>
                                <div id="rescheduleRequestCollapse" class="accordion-collapse collapse" aria-labelledby="rescheduleRequestHeading" data-bs-parent="#customerRequestActions">
                                    <div class="accordion-body">
                                        <form method="POST" action="{{ route('customer.reservations.request-reschedule', $reservation) }}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="row g-3">
                                                <div class="col-sm-6">
                                                    <label class="form-label fw-medium">Requested Pickup Date</label>
                                                    <input type="date" name="requested_pickup_date" min="{{ now()->toDateString() }}" value="{{ old('requested_pickup_date') }}" class="form-control @error('requested_pickup_date') is-invalid @enderror" required>
                                                    @error('requested_pickup_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="col-sm-6">
                                                    <label class="form-label fw-medium">Requested Slot</label>
                                                    <select name="requested_pickup_slot" class="form-select @error('requested_pickup_slot') is-invalid @enderror" required>
                                                        <option value="">Select slot</option>
                                                        @foreach (\App\Enums\PickupSlot::cases() as $slot)
                                                            <option value="{{ $slot->value }}" @selected(old('requested_pickup_slot') === $slot->value)>{{ $slot->label() }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('requested_pickup_slot') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label fw-medium">Reason <span class="text-muted fw-normal">(optional)</span></label>
                                                    <textarea name="request_reason" rows="3" class="form-control @error('request_reason') is-invalid @enderror">{{ old('request_reason') }}</textarea>
                                                    @error('request_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-outline-primary btn-sm mt-3">Submit Reschedule Request</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif ($reservation->customer_request_status === 'pending')
                        <p class="small text-muted mb-0">Your latest request is under review. You can submit another request once this one is processed.</p>
                    @else
                        <p class="small text-muted mb-0">Self-service requests are available only while reservation status is pending or overdue.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><strong>Reserved Items</strong></div>
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

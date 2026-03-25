<x-app-layout>
    <x-slot name="header">
        <h5 class="mb-0 fw-bold"><i class="bi bi-person-circle me-2"></i>Profile Settings</h5>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header fw-semibold">Profile Information</div>
                <div class="card-body">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header fw-semibold">Update Password</div>
                <div class="card-body">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="card border-danger-subtle">
                <div class="card-header text-danger fw-semibold">Delete Account</div>
                <div class="card-body">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

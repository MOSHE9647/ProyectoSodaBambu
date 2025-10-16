@php
	use App\Enums\UserRole;
	use Carbon\Carbon;
@endphp

@extends('layouts.app')

@section('content')
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-md-8">
				<div class="card">
					<div class="card-header">{{ __('Users') }}</div>

					<div class="card-body">
						@if (session('status'))
							{{-- Success Component --}}
							<x-alert :type="'success'" :message="session('status')"/>
						@endif

						<table id="users-table" class="table">
							<thead>
							<tr>
								<th scope="col">Name</th>
								<th scope="col">Email</th>
								<th scope="col">Role</th>
								<th scope="col">Created At</th>
								<th scope="col">Actions</th>
							</tr>
							</thead>
							<tbody>
								{{-- Loaded with JS --}}
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@section('scripts')
	<script type="text/javascript">
		let userRoute = "{{ route('users.index') }}";
		let userEditRoute = "{{ route('users.edit', ['user' => ':id']) }}";
		let userDeleteRoute = "{{ route('users.destroy', ['user' => ':id']) }}";
		let csrfToken = "{{ csrf_token() }}";
		let userRoles = [
			@foreach(UserRole::cases() as $role)
				{
					name: "{{ $role->name }}",
					value: "{{ $role->value }}",
					label: "{{ $role->label() }}"
				},
			@endforeach
		];
	</script>
	@vite(['resources/js/models/users/main.js'])
@endsection

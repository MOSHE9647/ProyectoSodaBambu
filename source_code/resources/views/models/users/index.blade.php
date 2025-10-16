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

						<table class="table">
							<thead>
							<tr>
								<th scope="col">#</th>
								<th scope="col">Name</th>
								<th scope="col">Email</th>
								<th scope="col">Role</th>
								<th scope="col">Created At</th>
							</tr>
							</thead>
							<tbody>
							@foreach ($users as $user)
								<tr>
									<th scope="row">{{ $user->id }}</th>
									<td>{{ $user->name }}</td>
									<td>{{ $user->email }}</td>
									<td>{{ UserRole::tryFrom($user->roles->first()->name)->label() }}</td>
									<td>{{ Carbon::parse($user->created_at)->format('d-M-Y') }}</td>
								</tr>
							@endforeach
							</tbody>
						</table>

						{{-- Pagination Links --}}
						{{ $users->links('pagination::bootstrap-5') }}
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

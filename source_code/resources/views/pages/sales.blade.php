@extends('layouts.app')

@section('content')
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-md-8">
				<div class="card">
					<div class="card-header">{{ __('Sales') }}</div>

					<div class="card-body">
						@if (session('status'))
							{{-- Success Component --}}
							<x-alert :type="'success'" :message="session('status')"/>
						@endif

						{{ __('Welcome to the Sales page. Here you can view and manage sales.') }}
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

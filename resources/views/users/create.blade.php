@extends('layouts.app')

@section('title', 'Tambah Member')

@section('content')
<div class="container">
	<div class="row">
		<div class="col-md-12">
			<ul class="breadcrumb">
				<li><a href="{{ url('/home') }}">Dashboard</a></li>
				<li><a href="{{ url('/users') }}">Data Member</a></li>
				<li class="active">Tambah Member</li>
			</ul>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h2 class="panel-title">Tambah Member</h2>
				</div>

				<div class="panel-body">
					{!! Form::open(['url' => route('users.store'),
						'method' => 'post', 'class'=>'form-horizontal']) !!}
						@include('users._forms')
					{!! Form::close() !!}
				</div>
			</div>
		</div>
	</div>
</div>
@endsection
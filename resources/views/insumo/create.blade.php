@extends('layouts.app')

@section('title')
Crear Insumo
@endsection

@section('content')
<section class="section">
    <div class="section-header">
        <a href="{{ route('insumo.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <h3 class="page__heading ml-3 mb-0">Crear Insumo</h3>
    </div>
    <div class="section-body">
        @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
        @endif
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    @includeif('partials.errors')

                    <div class="card-body">
                        <form method="POST" action="{{ route('insumo.store') }}" role="form" enctype="multipart/form-data">
                            @csrf

                            @include('insumo.form')

                        </form>
                    </div>
                </div>
            </div>
        </div>
</section>
@endsection
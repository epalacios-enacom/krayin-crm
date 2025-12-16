@extends('admin::layouts.master')

@section('page_title')
  Leads (DEBUG)
@endsection

@section('content-wrapper')
  <div class="content">
    <h1>DEBUG MODE ENABLED</h1>
    <p>Si ves esto, el parche funcionó, pero hay un error cargando el Grid.</p>

    {{--
    <form ... (commented out form) --}} {{-- We comment out the table component to isolate the error --}} {{--
      <table-component ...></table-component> --}}
  </div>
@endsection
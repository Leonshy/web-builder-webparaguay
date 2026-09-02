@extends('layouts.site')

@section('content')
    @foreach($ctx->page->sections() as $section)
        <x-site.section :ctx="$ctx" :section="$section" />
    @endforeach
@endsection

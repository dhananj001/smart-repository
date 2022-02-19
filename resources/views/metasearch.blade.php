@extends('layouts.app',['class'=> 'off-canvas-sidebar'])

@push('js')
<script>
var meta_options = new Array();
@php
$meta_fields = $collection->meta_fields;
$meta_labels = array();
foreach($meta_fields as $m){
    $meta_labels[$m->id] = $m->label;
    if($m->type == 'Select'){
        echo "meta_options[".$m->id."]='".$m->options."';";
    }
}
$all_meta_filters = Session::get('meta_filters');
@endphp
$(document).ready(function(){

    $("#metafieldselect").on('change',function() {
        var field_type = $(this).children("option:selected").attr('ourtype');
        var field_id = this.value;

        if(field_type == 'Numeric'){
            var o1 = new Option("=", "=");
            var o2 = new Option("<=", "<=");
            var o3 = new Option(">=", ">=");
            $('#operator-select').find('option').remove();
            $('#operator-select').append(o1);
            $('#operator-select').append(o2);
            $('#operator-select').append(o3);

            $('#meta-val-cont').html('');
            var mv_input = $("<input type=\"number\" class=\"form-control col-md-12\" name=\"meta_value\" />"); 
            $('#meta-val-cont').append(mv_input);
        }
        else if(field_type == 'Date'){
            var o1 = new Option("on", "=");
            var o2 = new Option("on or before", "<=");
            var o3 = new Option("on or after", ">=");
            $('#operator-select').find('option').remove();
            $('#operator-select').append(o1);
            $('#operator-select').append(o2);
            $('#operator-select').append(o3);

            $('#meta-val-cont').html('');
            var mv_input = $("<input type=\"date\" class=\"form-control col-md-12\" name=\"meta_value\" />"); 
            $('#meta-val-cont').append(mv_input);
        }
        else if(field_type == 'Select'){
            var o1 = new Option("matches", "=");
            $('#operator-select').find('option').remove();
            $('#operator-select').append(o1);

            $('#meta-val-cont').html('');
            var meta_select = $("<select name=\"meta_value\" class=\"selectpicker\"></select>");
            $('#meta-val-cont').append(meta_select);
            var meta_field = 'field_'+field_id;
            //alert(meta_options[field_id]);
            var options = meta_options[field_id].split(",");
            for(var i=0; i<options.length; i++){
                var o = new Option(options[i], options[i]);
                meta_select.append(o);
            }
            meta_select.selectpicker("refresh");
        }
        else { //field_type == 'Text' || field_type == 'SelectCombo'). This is the default
            var o1 = new Option("matches", "=");
            var o2 = new Option("contains", "contains");
            $('#operator-select').find('option').remove();
            $('#operator-select').append(o1);
            $('#operator-select').append(o2);

            $('#meta-val-cont').html('');
            var mv_input = $("<input type=\"text\" class=\"form-control col-md-12\" name=\"meta_value\" />"); 
            $('#meta-val-cont').append(mv_input);
        }
        $('#operator-select').selectpicker("refresh");
    }); 

});
</script>
@endpush
@section('content')
<div class="container">
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card">
                <div class="card-header card-header-primary"><h4 class="card-title"><a href="/collections">Collections</a> :: <a href="/collection/{{ $collection->id }}">{{ $collection->name }}</a> :: Set Meta Filters</h4></div>
                <div class="col-md-12 text-right">
                <a href="/collection/{{ $collection->id }}" class="btn btn-sm btn-primary" title="Back"><i class="material-icons">arrow_back</i></a>
                </div>
                <div class="card-body">
        @if(count($meta_fields)>0 && !empty($all_meta_filters[$collection->id]))
            <strong>Current Filters:</strong>
        @foreach( $all_meta_filters[$collection->id] as $m)
            <span class="filtertag">
            {{ $meta_labels[$m['field_id']] }} {{ $m['operator'] }} <i>{{ $m['value'] }}</i>
                <a class="removefiltertag" title="remove" href="/collection/{{ $collection->id }}/removefilter/{{ $m['filter_id'] }}">
                <i class="tinyicon material-icons">delete</i>
                </a>
                </span>
        @endforeach
        @endif
<form name="metasearch_form" action="/collection/{{ $collection->id }}/metafilters" method="post">
@csrf()
<input type="hidden" name="collection_id" value="{{ $collection->id }}" />
    @php
        $meta_params = array();
        if(!empty($meta_filters[$collection->id])){
            foreach($meta_filters[$collection->id] as $mf){
                $meta_params['meta_field_'.$mf['field_id']] = $mf['value'];
                $meta_params['operator_'.$mf['field_id']] = $mf['operator'];
            }
        }
    @endphp
    <div class="form-group row filter-row" id="filter-row">
        <div class="col-md-4 text-right">
            <select id="metafieldselect" class="selectpicker col-md-12" name="meta_field">
            <option value="">Select Meta field</option>
            @foreach($collection->meta_fields as $f)
            <option value="{{ $f->id }}" ourtype="{{ $f->type }}">{{ $f->label }}</option>
            @endforeach
            </select>
        </div>
        <div id="operator_cont" class="col-md-4">
            <select id="operator-select" class="selectpicker" name="operator">
            <option value="">Operator</select>
            </select>
        </div>
        <div id="meta-val-cont" class="col-md-4">
        </div>
    </div>

<div class="form-group row mb-0">
    <div class="col-md-5 offset-md-4">
        <button type="submit" class="btn btn-primary">Add</button>
        <a href="/collection/{{ $collection->id }}"><span class="btn btn-primary">Done</span></a>
    </div>
</div>
</form>
                </div>
            </div>


        </div>
    </div>
</div>
</div>
@endsection

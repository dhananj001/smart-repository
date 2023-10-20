@extends('layouts.app',['class' => 'off-canvas-sidebar','title'=>'Smart Repository','activePage'=>'contact','titlePage'=>'Contact Us'])
@push('js')
<script src="/js/jquery-ui.js" defer></script>
<link href="/css/jquery-ui.css" rel="stylesheet">
<script>
@php
	$url = '/collection/1/search-results?isa_search_parameter='.urlencode(request()->get('isa_search_parameter'));
@endphp
$(document).ready(function() {
$("#search-results").load('{{ $url }}');
});

function reloadSearchResults(){
	// reset page to 0
	$('#search-results-start').val(0);
	var queryString = $('#isa_search').serialize();
	//alert(queryString);
	var url = '/collection/1/search-results?'+queryString;
	$("#search-results").load(url);
	return false;
}
function nextPage(){
	var start = $('#search-results-start').val();
	start = parseInt(start) + 10;
	$('#search-results-start').val(start);
	reloadSearchResults();
}
function previousPage(){
	var start = $('#search-results-start').val();
	start = parseInt(start) - 10;
	$('#search-results-start').val(start);
	reloadSearchResults();
}

</script>
@endpush
@section('content')
<main id="main">
@php
	// get reverse meta field values
	$rmf_values = App\ReverseMetaFieldValue::all();
	$rmfv_map = [];
	foreach($rmf_values as $rmfv){
		$rmfv_map[$rmfv->meta_field_id][$rmfv->meta_value][] = $rmfv->document_id;
	}
	//print_r($rmfv_map);exit;
	// get meta fields of this collection
	$meta_fields = $collection->meta_fields;
	$filter_labels = ['Continent',env('COUNTRY_FIELD_LABEL','Country'), env('YEAR_FIELD_LABEL','Year')];
	$filters = [];
	foreach($meta_fields as $m){
		//if($m->type == 'TaxonomyTree'){
		if(in_array($m->label, $filter_labels)){
			$filters[] = $m;
		}
	}	

	$search_query = Request::get('isa_search_parameter');
	function getTree($children, $parent_id = null, $meta_id=null, $rmfv_map){
         if(empty($children['parent_'.$parent_id])) return;
         foreach($children['parent_'.$parent_id] as $t){
         $checked = '';
	 	 if(!empty(Request::get('meta')) && in_array($t->id,Request::get('meta'))){
			$checked = 'checked';
	 		}
        if(!empty($children['parent_'.$t->id]) && count($children['parent_'.$t->id]) > 0){
		if(empty($t->parent_id)){
		echo "By ".$t->label."<br /><br />";
		}
		else{
		// get compare with query string parameter to mark as checked
		echo '<div class="form-check">';
				$tid = $t->id;
                  echo '<input type="checkbox" value="'.$t->id.'" name="meta_'.$meta_id.'[]" onChange="reloadSearchResults();" '.$checked.' ><label class="form-check-label" for="flexCheckDefault">'.$t->label.' ('.(isset($rmfv_map[$meta_id][$tid])?count($rmfv_map[$meta_id][$tid]):0).')</label><br />';
		echo '</div>';
		}
                  getTree($children, $t->id, $meta_id, $rmfv_map);
             }
             else{
			$checked = '';
			if(!empty(Request::get('meta_'.$meta_id)) && in_array($t->id, Request::get('meta_'.$meta_id))){
				$checked = "checked";
			}
			echo '<div class="form-check">';
			$tid = $t->id;
                  echo '<input type="checkbox" value="'.$t->id.'" name="meta_'.$meta_id.'[]" onChange="reloadSearchResults();" '.$checked.'><label class="form-check-label" for="flexCheckDefault">'.$t->label.' ('.(isset($rmfv_map[$meta_id][$tid])?count($rmfv_map[$meta_id][$tid]):0).')</label><br />';
		echo '</div>';
             }
         }
}
@endphp

<!-- ======= Breadcrumbs ======= -->
    <div class="row justify-content-center">
		<form name="isa_search" action="/documents/isa_document_search" method="get" id="isa_search">
		<input type="hidden" name="length" id="search-results-length" value="10" />
		<input type="hidden" name="start" id="search-results-start" value="0" />
		@csrf
        <div class="col-md-12">
            <div class="card">
				<div class="card-header card-header-primary">
                	<h4 class="card-title ">{{ __('Database') }}</h4>
            	</div>
			<div class="card-body">
			<div class="row">
                  <div class="col-12 text-right">
                  @if(Auth::user() && Auth::user()->hasPermission($collection->id, 'MAINTAINER'))
                    <a title="{{ __('Manage users of this collection') }}" href="/collection/{{ $collection->id }}/users" class="btn btn-sm btn-primary"><i class="material-icons">people</i></a>
		    	@if($collection->content_type == 'Uploaded documents')	
                    <a title="{{ __('Manage cataloging fields of this collection') }}" href="/collection/{{ $collection->id }}/meta" class="btn btn-sm btn-primary"><i class="material-icons">label</i></a>
                     @elseif($collection->content_type == 'Web resources')	
                    <a title="Manage Sites for this collection" href="/collection/{{ $collection->id }}/save_exclude_sites" class="btn btn-sm btn-primary"><i class="material-icons">insert_link</i></a>
		    @endif
		  @endif
                  @if(Auth::user() && Auth::user()->hasPermission($collection->id, 'CREATE') && $collection->content_type == 'Uploaded documents')
                    <a title="New Document" href="/collection/{{ $collection->id }}/upload" class="btn btn-sm btn-primary"><i class="material-icons">file_upload</i></a>
                    
		  @endif
                 
                  </div>
		        </div>
			</div>
	
			<div class="col-10">
			</div>
			<div class="col-2 text-right">
			</div>
		<div class="row text-center">
		   <div class="col-12">
			<div class="float-container" style="width:100%;">
			<label for="collection_search">{{ __('Enter search keywords') }}</label>
		    <input type="text" class="search-field" id="collection_search" name="isa_search_parameter" value="{{ $search_query }}" />
		    <input type="hidden" class="search-field" id="collection_id" name="collection_id" value="{{ $collection->id }}" />
			<input type="button" value="Search" name="isa_search" class="btn btn-sm btn-primary search" onclick="reloadSearchResults()">
			<style>
			.dataTables_filter {
			display: none;
			}
			</style>
		   </div>
		   </div>
		  
		</div>
		
<!-- End Breadcrumbs -->

<!-- ======= Service Details Section ======= -->
@php
$tags = App\Taxonomy::all();

$children = [];
foreach($tags as $t){
  $children['parent_'.$t->parent_id][] = $t;
}
@endphp

<section id="service-details" class="service-details">
  <div class="container">
	<div class="row gy-4">
	  <div class="col-lg-3" style="margin-top:0;">
		<div class="services-list">
			<h5>Filter</h5>
				@php
				$display='display:none;';
				foreach($filters as $f){
 	 				if(!empty(Request::get('meta_'.$f->id))){
						$display = '';
 					}
					if($f->type == 'TaxonomyTree'){
						echo '<a href="#" onclick="$(\'#filter_'.$f->id.'\').toggle()">By '.$f->label.'</a>';
						echo '<div id="filter_'.$f->id.'" style="'.$display.'">';
						getTree($children, $f->options, $f->id, $rmfv_map);
						echo '</div>';
					}
					if($f->type == 'Numeric'){
						$meta_values = Request::get('meta_'.$f->id);
						echo '<a href="#" onclick="$(\'#filter_'.$f->id.'\').toggle()">By '.$f->label.'</a>';
						echo '<div id="filter_'.$f->id.'">';
						echo '<fieldset class="filter-range">';
						echo '<div class="range-field">';
						echo '<input type="range" id="year_lower_slider" name="meta_'.$f->id.'[]" min="1950" max="2023" step="1" 
							value="'.(!empty($meta_values[0])?$meta_values[0]:1950).'">';
						echo '<input type="range" id="year_upper_slider" name="meta_'.$f->id.'[]" min="1950" max="2023" step="1" 
							value="'.(!empty($meta_values[1])?$meta_values[1]:2023).'">';
						echo '</div>';
						@endphp	
						<div class="range-wrap">
		                  <div class="range-wrap-1">
                    		<input id="start_year" class="lower">
                    		<label for="start_year"></label>
                  		</div>
                  		<div class="range-wrap_line">-</div>
                  		<div class="range-wrap-2">
                    		<input id="end_year" class="upper">
                    		<label for="end_year"></label>
                  		</div>
                		</div>
						@php
						echo '</fieldset>';
						echo '</div>';

					}
				}
				@endphp
<script>
    var lowerSlider = document.getElementById('year_lower_slider');
    var upperSlider = document.getElementById('year_upper_slider');

    document.querySelector('#end_year').value = upperSlider.value;
    document.querySelector('#start_year').value = lowerSlider.value;

    var lowerVal = parseInt(lowerSlider.value);
    var upperVal = parseInt(upperSlider.value);

    upperSlider.oninput = function () {
      lowerVal = parseInt(lowerSlider.value);
      upperVal = parseInt(upperSlider.value);

      if (upperVal < lowerVal + 4) {
        lowerSlider.value = upperVal - 4;
        if (lowerVal == lowerSlider.min) {
          upperSlider.value = 4;
        }
      }
      document.querySelector('#end_year').value = this.value;
	  reloadSearchResults();
    };

    lowerSlider.oninput = function () {
      lowerVal = parseInt(lowerSlider.value);
      upperVal = parseInt(upperSlider.value);
      if (lowerVal > upperVal - 4) {
        upperSlider.value = lowerVal + 4;
        if (upperVal == upperSlider.max) {
          lowerSlider.value = parseInt(upperSlider.max) - 4;
        }
      }
      document.querySelector('#start_year').value = this.value;
	  reloadSearchResults();
    };

  </script>
		<div class="form-check">
		</div>
		</div>

	  
	  </div>

		</form><!-- isa_search form ends -->

<div class="col-lg-9" id="search-results">
	<!-- search results -->
</div>

		</div> <!-- card -->

</form>
	</div>

  </div>
</section><!-- End Service Details Section -->

</main><!-- End #main -->

<script>
	@if(env('SEARCH_MODE') == 'elastic')
	$(document).ready(function() {
        //alert("js is working");
        src = "{{ route('autosuggest') }}";
        $( "#collection_search" ).autocomplete({
            source: function( request, response ) {
                $.ajax({
                    url: src,
                    method: 'GET',
                    dataType: "json",
                    data: {
                        term : request.term
                    },
                    success: function(data) {
						if(data.length > 0)
                        response(data);
                    },
                });
            },
			select: function (event, ui){
				$("#collection_search").val(ui.item.value);
				return false;
			},
            minLength: 1,
        });
    });
	@endif
</script>
@endsection

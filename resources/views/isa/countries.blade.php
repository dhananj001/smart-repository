@extends('layouts.app',['class'=> 'off-canvas-sidebar','title'=>'Smart Repository'])

@section('content')
<!--
<script src="https://code.jquery.com/jquery-3.3.1.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
-->
<script src="/js/jquery.dataTables.min.js"></script>
<script src="/js/jquery-ui.js" defer></script>
<link href="/css/jquery-ui.css" rel="stylesheet">
<style>
.card-columns {
   column-count: 2;
}</style>
<script>
$(document).ready(function() {
    $('#collections').DataTable();
} );

function showDeleteDialog(collection_id){
        str = randomString(6);
        $('#text_captcha').text(str);
        $('#hidden_captcha').text(str);
        $('#delete_collection_id').val(collection_id);
        deldialog = $( "#deletedialog" ).dialog({
                title: 'Are you sure ?',
                resizable: true
        });
}

function randomString(length) {
   var result           = '';
   var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
   var charactersLength = characters.length;
   for ( var i = 0; i < length; i++ ) {
      result += characters.charAt(Math.floor(Math.random() * charactersLength));
   }
   return result;
}

</script>
<div class="container">
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
            <div class="card-header card-header-primary">
                <h4 class="card-title">{{ __('Countries') }}</h4>
                    <!--div class="card-header-corner" style="margin-top:-4%;"><a href="/admin/collection-form/new"><img class="icon" src="/i/plus.png"/></a></div-->
              </div>		

                <div class="card-body">
                    <div class="flash-message">
                    @foreach (['danger', 'warning', 'success', 'info'] as $msg)
                        @if(Session::has('alert-' . $msg))
			<div class="alert alert-<?php echo $msg; ?>">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <i class="material-icons">close</i>
                        </button>
                        <span>{{ Session::get('alert-' . $msg) }}</span>
                        </div>
                        @endif
                    @endforeach
                    </div>
	
                    <div class="container">
                            <ul class="list-unstyled card-columns">
                                    @php
                                    $tags = App\Taxonomy::all();
                                    $children = [];
                                    foreach($tags as $t){
                                    $children['parent_'.$t->parent_id][] = $t;
                                    }
					/*
                                    function getTree($children, $parent_id = null){
                                    if(empty($children['parent_'.$parent_id])) return;
                                    foreach($children['parent_'.$parent_id] as $t){
                                    if(!empty($children['parent_'.$t->id]) && count($children['parent_'.$t->id]) > 0){
                                    echo '<li>';
                                        echo '<a href="#">'.$t->label.'</a>';
                                        getTree($children, $t->id);
                                        echo '</li>';
                                        
                                    }
                                    }
                                    }
					*/

		function getTree($children, $parent_id = null, $meta_id=null, $rmfv_map=null){
		         if(empty($children['parent_'.$parent_id])) return;
         			foreach($children['parent_'.$parent_id] as $t){
        				if(!empty($children['parent_'.$t->id]) && count($children['parent_'.$t->id]) > 0){
                				// get compare with query string parameter to mark as checked
						echo '<li>';
                  				echo '<a href="#">'.$t->label.'</a>';
						echo '</li>';
                  				getTree($children, $t->id, $meta_id, $rmfv_map);
             				}
             				else{
						//echo '<li>';
                  				//echo '<label class="form-check-label" for="flexCheckDefault">'.$t->label.'</label><br />';
						//echo '</li>';
             				}
				}#foreach	
		}#function ends
                                    @endphp
                                </ul>
                                <ul class="list-unstyled card-columns">
					@php
	$collection = \App\Collection::find(1);
	$meta_fields = $collection->meta_fields;
        $filters = [];
        foreach($meta_fields as $m){
                if($m->type == 'TaxonomyTree'){
                        $filters[] = $m;
                }
        }
                                foreach($filters as $f){
					$page = $_SERVER['REQUEST_URI'];
					if(preg_match("/Countries|Places|Location/i",$f->label)){
                                        getTree($children, $f->options, $f->id);
					}
                                }
                                @endphp

                                </ul>
                            </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@extends('layouts.app',['class'=> 'off-canvas-sidebar'])

@section('content')

<link rel="stylesheet"  href="http://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.min.css" type="text/css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
@push('js')
<script type="text/javascript">
function showDriveFields(drive){
	if(drive == 'ftp' || drive == 'sftp'){
		$('#ftp_details').show();	
		$('#s3_details').hide();	
	}
	else if (drive == 's3'){
		$('#ftp_details').hide();	
		$('#s3_details').show();	
	}
	else{}
}

$(document).ready(function(){
	showDriveFields($('#driver').val());
}
);
</script>
@endpush

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card">
		@if (empty($disk->id))
                <div class="card-header card-header-primary"><h4 class="card-title">New Disk</h4></div>
		@else
                <div class="card-header card-header-primary"><h4 class="card-title">Edit Disk</h4></div>
		@endif

                <div class="card-body">
		<div class="row">
                  <div class="col-md-12 text-right">
                      <a href="/admin/diskmanagement" class="btn btn-sm btn-primary" title="Back to List"><i class="material-icons">arrow_back</i></a>
                  </div>
                </div>

                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                    	<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                      	<i class="material-icons">close</i>
                    	</button>
                    	<span>{{ session('status') }}</span>
                        </div>
                    @endif

                   <form method="post" action="/admin/savedisk">
                    @csrf()
                    <input type="hidden" name="disk_id" value="{{$disk->id}}" />

                   <div class="form-group row">
                    <div class="col-md-3">
                   <label for="disk_name" class="col-md-12 col-form-label text-md-right">Name</label> 
                    </div>
                    <div class="col-md-9">
                    <input type="text" name="disk_name" id="disk_name" class="form-control" placeholder="Only alphabets and numbers; no spaces or special characters." value="{{ $disk->name }}" required />
                    </div>
                   </div>

                   <div class="form-group row">
                    <div class="col-md-3">
                   <label for="driver" class="col-md-12 col-form-label text-md-right">Type/Driver</label> 
                    </div>
                    <div class="col-md-9">
					<select name="driver" class="selectpicker" id="driver" onchange="showDriveFields(this.value);">
						<option value="">Drive Type</option>
						<option value="ftp">FTP</option>
						<option value="sftp">SFTP</option>
						<option value="s3">S3</option>
					</select>
                    </div>
                   </div>

				  <div id="ftp_details" style="display:none;">

                   <div class="form-group row">
                   <div class="col-md-3">
                   <label for="host" class="col-md-12 col-form-label text-md-right">Host</label> 
                    </div>
                    <div class="col-md-9">
                    <input type="text" name="host" id="host" class="form-control" placeholder="Server address" value="{{ $disk->host }}"/>
                    </div>
                   </div>
                   <div class="form-group row">
                   <div class="col-md-3">
                   <label for="port" class="col-md-12 col-form-label text-md-right">Port</label> 
                    </div>
                    <div class="col-md-9">
                    <input type="text" name="port" id="port" class="form-control" placeholder="Port" value="{{ $disk->port }}" />
                    </div>
                   </div>
                   <div class="form-group row">
                   <div class="col-md-3">
                   <label for="username" class="col-md-12 col-form-label text-md-right">Username</label> 
                    </div>
                    <div class="col-md-9">
                    <input type="text" name="username" id="username" class="form-control" placeholder="Username" value="{{ $disk->username }}" />
                    </div>
                   </div>
                   <div class="form-group row">
                   <div class="col-md-3">
                   <label for="password" class="col-md-12 col-form-label text-md-right">Password</label> 
                    </div>
                    <div class="col-md-9">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password" value="{{ $disk->password }}" />
                    </div>
                   </div>
                   <div class="form-group row">
                   <div class="col-md-3">
                   <label for="root" class="col-md-12 col-form-label text-md-right">Root</label> 
                    </div>
                    <div class="col-md-9">
                    <input type="text" name="root" id="root" class="form-control" placeholder="Path of the root directory on the server" value="{{ $disk->root }}" />
                    </div>
                   </div>
                   <div class="form-group row">
                   <div class="col-md-3">
                   <label for="timeout" class="col-md-12 col-form-label text-md-right">Timeout</label> 
                    </div>
                    <div class="col-md-9">
                    <input type="text" name="timeout" id="timeout" class="form-control" placeholder="Timeout in seconds" value="{{ $disk->timeout }}" />
                    </div>
                   </div>

				  </div>

				  <div id="s3_details" style="display:none;">

                   <div class="form-group row">
                   <div class="col-md-3">
                   <label for="key" class="col-md-12 col-form-label text-md-right">Key</label> 
                    </div>
                    <div class="col-md-9">
                    <input type="text" name="key" id="key" class="form-control" placeholder="S3 key" value="{{ $disk->key }}" />
                    </div>
                   </div>
                   <div class="form-group row">
                   <div class="col-md-3">
                   <label for="secret" class="col-md-12 col-form-label text-md-right">Secret</label> 
                    </div>
                    <div class="col-md-9">
                    <input type="text" name="secret" id="secret" class="form-control" placeholder="S3 secret" value="{{ $disk->secret }}" />
                    </div>
                   </div>
                   <div class="form-group row">
                   <div class="col-md-3">
                   <label for="region" class="col-md-12 col-form-label text-md-right">Region</label> 
                    </div>
                    <div class="col-md-9">
                    <input type="text" name="region" id="region" class="form-control" placeholder="S3 region" value="{{ $disk->region }}" />
                    </div>
                   </div>
                   <div class="form-group row">
                   <div class="col-md-3">
                   <label for="bucket" class="col-md-12 col-form-label text-md-right">Bucket</label> 
                    </div>
                    <div class="col-md-9">
                    <input type="text" name="bucket" id="bucket" class="form-control" placeholder="S3 bucket" value="{{ $disk->bucket }}" />
                    </div>
                   </div>
                   <div class="form-group row">
                   <div class="col-md-3">
                   <label for="endpoint" class="col-md-12 col-form-label text-md-right">Endpoint</label> 
                    </div>
                    <div class="col-md-9">
                    <input type="text" name="endpoint" id="endpoint" class="form-control" placeholder="S3 endpoint" value="{{ $disk->endpoint }}" />
                    </div>
                   </div>

				  </div>
                
                   <div class="form-group row mb-0"><div class="col-md-8 offset-md-4"><button type="submit" class="btn btn-primary">
                                    Save
                                </button> 
                     </div></div> 
                   </form> 
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Dokumen kegiatan · {{ $a->nama }}</title>
<style>
@include('v3.print-styles')
.document-start{page-break-before:always}
</style>
</head>
<body>
@foreach($documents as $doc)
@if(!$loop->first)<div class="document-start">
</div>
@endif
@include('v3.print-document',['doc'=>$doc,'s'=>\App\Services\DocumentAssets::hydrate($doc->snapshot,$doc->school_profile_id)])
@endforeach
</body>
</html>

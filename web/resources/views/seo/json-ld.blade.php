{{-- Assumes $data is a plain PHP array; see App\Support\Seo\StructuredData. --}}
<script type="application/ld+json">{!! json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

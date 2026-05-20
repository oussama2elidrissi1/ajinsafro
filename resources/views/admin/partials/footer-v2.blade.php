@php
    $adminV2BrandName = $adminV2BrandName ?? \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
@endphp
<footer class="aj-footer">
    <div>© {{ now()->year }} {{ $adminV2BrandName }} �?" Tous droits réservés.</div>
    <div>Admin V2</div>
</footer>


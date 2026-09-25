$paths = @("app", "database", "routes", "resources", "tests")
$extensions = @("*.php", "*.blade.php")

$replacements = @(
    @("koperasi_desa", "entitas"),
    @("id_koperasi", "id_entitas"),
    @("kode_koperasi", "kode_entitas"),
    @("nama_koperasi", "nama_entitas"),
    @("KoperasiDesa", "Entitas"),
    @("BelongsToKoperasi", "BelongsToEntitas"),
    @("KoperasiScope", "EntitasScope"),
    @("KoperasiController", "EntitasController"),
    @("\$koperasi", "\$entitas"),
    @("koperasi\(\)", "entitas()")
)

foreach ($path in $paths) {
    if (Test-Path $path) {
        $files = Get-ChildItem -Path $path -Recurse -Include $extensions -File
        foreach ($file in $files) {
            $content = Get-Content $file.FullName -Raw
            $original = $content
            foreach ($rep in $replacements) {
                # Case sensitive replacement for specific ones if needed, but -replace is case-insensitive by default in PS.
                # To make it case sensitive, we use -creplace
                $content = $content -creplace $rep[0], $rep[1]
            }
            if ($original -cne $content) {
                Set-Content -Path $file.FullName -Value $content -Encoding UTF8
                Write-Host "Updated $($file.FullName)"
            }
        }
    }
}
Write-Host "Mass replace completed."

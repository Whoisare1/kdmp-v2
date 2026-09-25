$paths = @("app", "database", "routes", "resources", "tests")
$extensions = @("*.php", "*.blade.php")

foreach ($path in $paths) {
    if (Test-Path $path) {
        $files = Get-ChildItem -Path $path -Recurse -Include $extensions -File
        foreach ($file in $files) {
            $content = Get-Content $file.FullName -Raw
            $original = $content
            
            $content = $content.Replace('$koperasi', '$entitas')
            $content = $content.Replace('koperasi()', 'entitas()')
            $content = $content.Replace('KoperasiController', 'EntitasController')
            $content = $content.Replace('koperasi.', 'entitas.')
            $content = $content.Replace('id_koperasi', 'id_entitas')
            $content = $content.Replace('koperasi_desa', 'entitas')

            if ($original -cne $content) {
                Set-Content -Path $file.FullName -Value $content -Encoding UTF8
                Write-Host "Updated $($file.FullName)"
            }
        }
    }
}
Write-Host "Literal string replace completed."

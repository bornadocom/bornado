param(
    [string]$OutputDir = ".\var\geonames",
    [string]$CityDataset = "cities1000.zip"
)

$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$target = Resolve-Path -LiteralPath (Join-Path $root "..") | Select-Object -ExpandProperty Path
$outputPath = Join-Path $target $OutputDir

if (-not (Test-Path -LiteralPath $outputPath)) {
    New-Item -ItemType Directory -Path $outputPath | Out-Null
}

$downloads = @(
    @{ Name = "countryInfo.txt"; Url = "https://download.geonames.org/export/dump/countryInfo.txt" },
    @{ Name = $CityDataset; Url = "https://download.geonames.org/export/dump/$CityDataset" },
    @{ Name = "alternateNamesV2.zip"; Url = "https://download.geonames.org/export/dump/alternateNamesV2.zip" }
)

foreach ($item in $downloads) {
    $destination = Join-Path $outputPath $item.Name
    Write-Host "Downloading $($item.Name)..."
    Invoke-WebRequest -Uri $item.Url -OutFile $destination
}

$supplementTemplate = Join-Path $outputPath "city-fa-supplement.sample.csv"
if (-not (Test-Path -LiteralPath $supplementTemplate)) {
    @(
        "geoname_id,name_fa"
        "# 6173331,ونکوور"
        "# 6167865,تورنتو"
        "# 5907364,برنابی"
    ) | Set-Content -LiteralPath $supplementTemplate -Encoding UTF8
}

Write-Host ""
Write-Host "GeoNames snapshot آماده شد در:"
Write-Host "  $outputPath"
Write-Host ""
Write-Host "وقتی محیط وردپرسی/سروری در دسترس بود، این دستورات را اجرا کنید:"
Write-Host "  wp bornado-geo import-countries `"$outputPath\countryInfo.txt`""
Write-Host "  wp bornado-geo import-cities `"$outputPath\$CityDataset`""
Write-Host "  wp bornado-geo import-fa-names `"$outputPath\alternateNamesV2.zip`""
Write-Host "  wp bornado-geo import-city-fa-supplement `"$outputPath\city-fa-supplement.sample.csv`"   # optional"
Write-Host "  wp bornado-geo seed-root-countries"

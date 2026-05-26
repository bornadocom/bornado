$ErrorActionPreference = "Stop"

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectDir = Split-Path -Parent $scriptDir
$sampleFile = Join-Path $projectDir "examples/events/listing.published.sample.json"

$serviceUrl = if ($env:BORNADO_NOTIFICATION_SERVICE_URL) { $env:BORNADO_NOTIFICATION_SERVICE_URL } else { "http://127.0.0.1:8085" }
$sharedSecret = if ($env:BORNADO_NOTIFICATION_SHARED_SECRET) { $env:BORNADO_NOTIFICATION_SHARED_SECRET } else { "replace-with-a-long-random-secret" }
$endpoint = $serviceUrl.TrimEnd("/") + "/events"

$body = Get-Content -Raw -Path $sampleFile
$hmac = New-Object System.Security.Cryptography.HMACSHA256
$hmac.Key = [System.Text.Encoding]::UTF8.GetBytes($sharedSecret)
$signatureBytes = $hmac.ComputeHash([System.Text.Encoding]::UTF8.GetBytes($body))
$signature = ($signatureBytes | ForEach-Object { $_.ToString("x2") }) -join ""

Write-Host "Sending sample event to $endpoint"

Invoke-RestMethod `
    -Method Post `
    -Uri $endpoint `
    -ContentType "application/json; charset=utf-8" `
    -Headers @{ "X-Bornado-Signature" = $signature } `
    -Body $body

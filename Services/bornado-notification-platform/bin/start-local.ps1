$ErrorActionPreference = "Stop"

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectDir = Split-Path -Parent $scriptDir
$publicDir = Join-Path $projectDir "public"
$hostName = if ($env:BORNADO_NOTIFICATION_LOCAL_HOST) { $env:BORNADO_NOTIFICATION_LOCAL_HOST } else { "127.0.0.1" }
$port = if ($env:BORNADO_NOTIFICATION_LOCAL_PORT) { $env:BORNADO_NOTIFICATION_LOCAL_PORT } else { "8085" }

Write-Host "Starting notification platform on http://$hostName`:$port"
php -S "$hostName`:$port" -t $publicDir

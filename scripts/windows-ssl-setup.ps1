#
# DiscordPHP — Windows SSL helper
#
# Stages a CA certificate bundle in the current user's profile so PHP on
# Windows can verify Discord's TLS certificates. Does NOT modify php.ini
# or your environment automatically; it only downloads the cert and
# prints the two ways you can hook it up.
#
# Usage:
#   powershell -ExecutionPolicy Bypass -File scripts/windows-ssl-setup.ps1
#

$ErrorActionPreference = 'Stop'

$sslDir = Join-Path $HOME '.ssl'
$caFile = Join-Path $sslDir 'cacert.pem'
$caUrl  = 'https://curl.se/ca/cacert.pem'

if (-not (Test-Path $sslDir)) {
    New-Item -ItemType Directory -Path $sslDir | Out-Null
    Write-Host "Created $sslDir"
}

Write-Host "Downloading CA bundle from $caUrl ..."
Invoke-WebRequest -Uri $caUrl -OutFile $caFile -UseBasicParsing

if (-not (Test-Path $caFile)) {
    throw "Download failed: $caFile was not created."
}

Write-Host ""
Write-Host "CA bundle installed at: $caFile"
Write-Host ""
Write-Host "Pick ONE of the following to activate it:"
Write-Host ""
Write-Host "  [Recommended] Let DiscordPHP apply it at runtime (no php.ini edit):"
Write-Host ""
Write-Host "    Option A - pass it to the Discord constructor:"
Write-Host "        `$discord = new Discord(["
Write-Host "            'token'  => 'bot-token',"
Write-Host "            'cafile' => '$caFile',"
Write-Host "        ]);"
Write-Host ""
Write-Host "    Option B - set an environment variable (persistent for your user):"
Write-Host "        setx DISCORDPHP_CAFILE `"$caFile`""
Write-Host "        # then open a new terminal"
Write-Host ""
Write-Host "  [Traditional] Configure PHP globally via php.ini:"
Write-Host ""
Write-Host "    1) Find your php.ini:  php --ini"
Write-Host "    2) Add (or update) these lines:"
Write-Host ""
Write-Host "         openssl.cafile=`"$caFile`""
Write-Host "         curl.cainfo=`"$caFile`""
Write-Host ""
Write-Host "    3) Restart any running PHP processes."
Write-Host ""

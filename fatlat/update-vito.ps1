$ErrorActionPreference = 'Stop'

$image = 'ghcr.io/fatlat/vito:latest'
$envFile = Join-Path $env:USERPROFILE '.vito\vito.env'

if (-not (Test-Path $envFile)) {
    throw "Vito env file not found: $envFile"
}

$currentEnv = docker inspect vito --format '{{range .Config.Env}}{{println .}}{{end}}' 2>$null
$email = ($currentEnv | Where-Object { $_ -like 'EMAIL=*' } | Select-Object -First 1) -replace '^EMAIL=', ''
$name = ($currentEnv | Where-Object { $_ -like 'NAME=*' } | Select-Object -First 1) -replace '^NAME=', ''

if (-not $email) {
    $email = Read-Host 'Vito admin e-postasi'
}
if (-not $name) {
    $name = 'Latif'
}

docker pull $image

docker rm -f vito 2>$null | Out-Null

docker run -d --name vito --restart unless-stopped `
    --env-file $envFile `
    -e "NAME=$name" `
    -e "EMAIL=$email" `
    -e "PASSWORD=$([guid]::NewGuid())" `
    -p 127.0.0.1:8090:80 `
    -v vito_storage:/var/www/html/storage `
    -v vito_plugins:/var/www/html/app/Vito/Plugins `
    $image

Write-Host "Vito $image ile yeniden baslatildi: http://localhost:8090"

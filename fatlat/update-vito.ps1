param(
    [string]$Image = 'ghcr.io/fatlat/vito:latest'
)

$envFile = Join-Path $env:USERPROFILE '.vito\vito.env'

if (-not (Test-Path $envFile)) {
    Write-Error "Vito env file not found: $envFile"
    exit 1
}

docker pull $Image
if ($LASTEXITCODE -ne 0) {
    Write-Error "Could not pull $Image. The running Vito container was left untouched."
    exit 1
}

$email = $null
$name = $null
$existing = docker ps -a --filter 'name=^vito$' --format '{{.Names}}'
if ($existing) {
    $currentEnv = docker inspect vito --format '{{range .Config.Env}}{{println .}}{{end}}'
    $email = ($currentEnv | Where-Object { $_ -like 'EMAIL=*' } | Select-Object -First 1) -replace '^EMAIL=', ''
    $name = ($currentEnv | Where-Object { $_ -like 'NAME=*' } | Select-Object -First 1) -replace '^NAME=', ''
}

if (-not $email) {
    $email = Read-Host 'Vito admin e-postasi'
}
if (-not $name) {
    $name = 'Latif'
}

if ($existing) {
    docker rm -f vito | Out-Null
}

docker run -d --name vito --restart unless-stopped `
    --env-file $envFile `
    -e "NAME=$name" `
    -e "EMAIL=$email" `
    -e "PASSWORD=$([guid]::NewGuid())" `
    -p 127.0.0.1:8090:80 `
    -v vito_storage:/var/www/html/storage `
    -v vito_plugins:/var/www/html/app/Vito/Plugins `
    $Image

if ($LASTEXITCODE -ne 0) {
    Write-Error "Could not start $Image. Run this script again with -Image vitodeploy/vito:latest to go back."
    exit 1
}

Write-Host "Vito started with $Image at http://localhost:8090"

#requires -Version 7.2
param([string]$XamppPath = 'C:\xampp', [int]$DatabasePort = 3307)
$ErrorActionPreference = 'Stop'
$projectPath = Split-Path $PSScriptRoot -Parent
$taskHash = [Convert]::ToHexString([Security.Cryptography.SHA256]::HashData([Text.Encoding]::UTF8.GetBytes($projectPath))).Substring(0, 12)
$runtimePath = Join-Path $env:LOCALAPPDATA "ServiConnect\dev-$taskHash"
$dataPath = Join-Path $runtimePath 'mysql'
$credentialsPath = Join-Path $runtimePath 'credentials.json'
$phpPath = Join-Path $XamppPath 'php\php.exe'
$mysqlPath = Join-Path $XamppPath 'mysql\bin\mysqld.exe'
foreach ($exe in @($phpPath, $mysqlPath)) { if (!(Test-Path -LiteralPath $exe)) { throw "Executável ausente: $exe" } }
New-Item -ItemType Directory -Path $runtimePath -Force | Out-Null
if (!(Test-Path -LiteralPath $credentialsPath)) {
    $credentials = @{port=$DatabasePort; root_password=([Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(32))); app_password=([Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(32)))}
    $credentials | ConvertTo-Json | Set-Content -LiteralPath $credentialsPath -Encoding utf8NoBOM
}
$credentials = Get-Content -LiteralPath $credentialsPath -Raw | ConvertFrom-Json
if ($credentials.port -ne $DatabasePort) { throw 'Use a mesma porta da instalação anterior.' }
if (!(Test-Path -LiteralPath (Join-Path $dataPath 'mysql'))) {
    & (Join-Path $XamppPath 'mysql\bin\mysql_install_db.exe') "--datadir=$dataPath" "--password=$($credentials.root_password)" "--port=$DatabasePort" --silent
    if ($LASTEXITCODE -ne 0) { throw 'Falha ao inicializar o banco separado.' }
}
$dbPidPath = Join-Path $runtimePath 'mysql-process.pid'
$dbProcess = if (Test-Path -LiteralPath $dbPidPath) { Get-Process -Id ([int](Get-Content -LiteralPath $dbPidPath)) -ErrorAction SilentlyContinue } else { $null }
if ($dbProcess) {
    $dbCommand = (Get-CimInstance Win32_Process -Filter "ProcessId=$($dbProcess.Id)").CommandLine
    if (!$dbCommand -or !$dbCommand.Contains($dataPath)) { throw 'O PID salvo não pertence ao banco deste projeto.' }
}
if (!$dbProcess) {
    $listener = Get-NetTCPConnection -LocalPort $DatabasePort -State Listen -ErrorAction SilentlyContinue
    if ($listener) { throw "A porta $DatabasePort está ocupada por outro processo." }
    $mysqlArgs = @('--no-defaults', ('--basedir="' + (Join-Path $XamppPath 'mysql') + '"'), ('--datadir="' + $dataPath + '"'), "--port=$DatabasePort", '--bind-address=127.0.0.1', ('--log-error="' + (Join-Path $runtimePath 'mysql-error.log') + '"'))
    $mysqlArgs += '--console'
    $dbProcess = Start-Process -FilePath $mysqlPath -ArgumentList $mysqlArgs -WindowStyle Hidden -RedirectStandardOutput (Join-Path $runtimePath 'mysql-output.log') -RedirectStandardError (Join-Path $runtimePath 'mysql-console.log') -PassThru
    $dbProcess.Id | Set-Content -LiteralPath $dbPidPath
}
$ready = $false
for ($attempt = 0; $attempt -lt 40; $attempt++) {
    $client = [Net.Sockets.TcpClient]::new()
    try {
        $connect = $client.ConnectAsync('127.0.0.1', $DatabasePort)
        if ($connect.Wait(250) -and $client.Connected) { $ready = $true; break }
    } catch { Start-Sleep -Milliseconds 250 } finally { $client.Dispose() }
}
if (!$ready) { throw "Banco não iniciou. Confira $runtimePath\mysql-error.log" }
& $phpPath (Join-Path $PSScriptRoot 'setup-dev.php') $credentialsPath
if ($LASTEXITCODE -ne 0) { throw 'Falha ao preparar esquema e dados fictícios.' }
$webPidPath = Join-Path $runtimePath 'php-process.pid'
$webProcess = if (Test-Path -LiteralPath $webPidPath) { Get-Process -Id ([int](Get-Content -LiteralPath $webPidPath)) -ErrorAction SilentlyContinue } else { $null }
if ($webProcess) {
    $webCommand = (Get-CimInstance Win32_Process -Filter "ProcessId=$($webProcess.Id)").CommandLine
    if (!$webCommand -or !$webCommand.Contains((Join-Path $PSScriptRoot 'dev-router.php'))) { throw 'O PID salvo não pertence ao servidor deste projeto.' }
}
if (!$webProcess) {
    if (Get-NetTCPConnection -LocalPort 8080 -State Listen -ErrorAction SilentlyContinue) { throw 'Porta 8080 ocupada por outro processo.' }
    $webArgs = @('-S', '127.0.0.1:8080', '-t', ('"' + $projectPath + '"'), ('"' + (Join-Path $PSScriptRoot 'dev-router.php') + '"'))
    $webProcess = Start-Process -FilePath $phpPath -ArgumentList $webArgs -WorkingDirectory $projectPath -WindowStyle Hidden -RedirectStandardOutput (Join-Path $runtimePath 'php-output.log') -RedirectStandardError (Join-Path $runtimePath 'php-error.log') -PassThru
    $webProcess.Id | Set-Content -LiteralPath $webPidPath
}
Write-Output 'Ambiente local: http://127.0.0.1:8080 — banco serviconnect_dev (separado do site).'
Write-Output "Arquivos privados do ambiente: $runtimePath"

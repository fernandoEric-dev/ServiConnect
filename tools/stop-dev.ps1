#requires -Version 7.2
param([string]$XamppPath = 'C:\xampp')
$ErrorActionPreference = 'Stop'
$projectPath = Split-Path $PSScriptRoot -Parent
$taskHash = [Convert]::ToHexString([Security.Cryptography.SHA256]::HashData([Text.Encoding]::UTF8.GetBytes($projectPath))).Substring(0, 12)
$runtimePath = Join-Path $env:LOCALAPPDATA "ServiConnect\dev-$taskHash"
$phpPidPath = Join-Path $runtimePath 'php-process.pid'
if (Test-Path -LiteralPath $phpPidPath) {
    $savedPid = [int](Get-Content -LiteralPath $phpPidPath)
    $savedProcess = Get-CimInstance Win32_Process -Filter "ProcessId=$savedPid"
    if ($savedProcess) {
        if (!$savedProcess.CommandLine -or !$savedProcess.CommandLine.Contains((Join-Path $PSScriptRoot 'dev-router.php'))) { throw 'PID não corresponde ao servidor deste projeto.' }
        Stop-Process -Id $savedPid
        Wait-Process -Id $savedPid -Timeout 10 -ErrorAction SilentlyContinue
    }
}
$credentialsPath = Join-Path $runtimePath 'credentials.json'
if (Test-Path -LiteralPath $credentialsPath) {
    & (Join-Path $XamppPath 'php\php.exe') (Join-Path $PSScriptRoot 'stop-db.php') $credentialsPath
    if ($LASTEXITCODE -ne 0) { throw 'Falha ao encerrar o banco local.' }
    $mysqlPidPath = Join-Path $runtimePath 'mysql-process.pid'
    if (Test-Path -LiteralPath $mysqlPidPath) {
        $mysqlProcessId = [int](Get-Content -LiteralPath $mysqlPidPath)
        Wait-Process -Id $mysqlProcessId -Timeout 10 -ErrorAction SilentlyContinue
        $remaining = Get-CimInstance Win32_Process -Filter "ProcessId=$mysqlProcessId"
        if ($remaining) {
            $expectedDataPath = Join-Path $runtimePath 'mysql'
            if (!$remaining.CommandLine -or !$remaining.CommandLine.Contains($expectedDataPath)) { throw 'PID não corresponde ao banco deste projeto.' }
            Write-Warning 'O banco local não encerrou após SHUTDOWN; encerrando somente a instância isolada deste projeto.'
            Stop-Process -Id $mysqlProcessId
            Wait-Process -Id $mysqlProcessId -Timeout 10 -ErrorAction SilentlyContinue
        }
    }
}
Write-Output 'Ambiente encerrado. Os dados locais foram preservados.'

#requires -Version 7.4
param([string]$PhpPath = 'C:\xampp\php\php.exe')
$ErrorActionPreference = 'Stop'
$projectPath = Split-Path $PSScriptRoot -Parent
$privatePath = Join-Path $projectPath '.local'
New-Item -ItemType Directory -Path $privatePath -Force | Out-Null
$fixturePath = Join-Path $privatePath 'opencnpj-fixture.json'
foreach ($port in @(8082,8083)) {
    if (Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue) { throw "Porta de teste $port ocupada." }
}
'{"default":{"situacao":"Ativa"}}' | Set-Content -LiteralPath $fixturePath -Encoding utf8NoBOM
$fixtureProcess = $null
$webProcess = $null
$previousBase = $env:SERVICONNECT_TEST_BASE
$previousFixture = $env:OPENCNPJ_FIXTURE_FILE
try {
    $fixtureProcess = Start-Process -FilePath $PhpPath -ArgumentList @('-S','127.0.0.1:8083',('"'+(Join-Path $projectPath 'tests\fixtures\opencnpj-router.php')+'"')) -WorkingDirectory $projectPath -WindowStyle Hidden -Environment @{OPENCNPJ_FIXTURE_FILE=$fixturePath} -RedirectStandardOutput (Join-Path $privatePath 'cnpj-fixture-out.log') -RedirectStandardError (Join-Path $privatePath 'cnpj-fixture-error.log') -PassThru
    $webProcess = Start-Process -FilePath $PhpPath -ArgumentList @('-S','127.0.0.1:8082','-t',('"'+$projectPath+'"'),('"'+(Join-Path $PSScriptRoot 'dev-router.php')+'"')) -WorkingDirectory $projectPath -WindowStyle Hidden -Environment @{APP_ENV='development';OPENCNPJ_TEST_URL='http://127.0.0.1:8083'} -RedirectStandardOutput (Join-Path $privatePath 'cnpj-test-out.log') -RedirectStandardError (Join-Path $privatePath 'cnpj-test-error.log') -PassThru
    for ($attempt=0; $attempt -lt 30; $attempt++) {
        try { $null = Invoke-WebRequest 'http://127.0.0.1:8082/cadastro_empresa.php' -TimeoutSec 1; break }
        catch { if ($attempt -eq 29) { throw }; Start-Sleep -Milliseconds 100 }
    }
    $env:SERVICONNECT_TEST_BASE = 'http://127.0.0.1:8082'
    $env:OPENCNPJ_FIXTURE_FILE = $fixturePath
    foreach ($suite in @('cnpj','accounts','security')) {
        & $PhpPath (Join-Path $projectPath "tests\$suite.php")
        if ($LASTEXITCODE -ne 0) { throw "Falha na suíte $suite." }
    }
} finally {
    $env:SERVICONNECT_TEST_BASE = $previousBase
    $env:OPENCNPJ_FIXTURE_FILE = $previousFixture
    if ($webProcess -and !$webProcess.HasExited) { Stop-Process -Id $webProcess.Id }
    if ($fixtureProcess -and !$fixtureProcess.HasExited) { Stop-Process -Id $fixtureProcess.Id }
}

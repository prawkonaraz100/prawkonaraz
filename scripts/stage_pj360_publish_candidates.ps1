param(
    [string] $InputPath = "output/analysis/pj360-compare/publish-candidates/publish-candidates.json",
    [string] $StagingCopyPath = "storage/app/tmp/pj360-publish-candidates.json",
    [string] $SummaryCopyPath = "output/analysis/pj360-compare/staging/pj360-explanation-draft-summary.json"
)

$ErrorActionPreference = "Stop"

$repoRoot = Split-Path -Parent $PSScriptRoot
Set-Location $repoRoot

$resolvedInputPath = if ([System.IO.Path]::IsPathRooted($InputPath)) {
    $InputPath
} else {
    Join-Path $repoRoot $InputPath
}

if (-not (Test-Path -LiteralPath $resolvedInputPath)) {
    throw "Nie znaleziono pliku kandydatow: $resolvedInputPath"
}

$resolvedStagingCopyPath = if ([System.IO.Path]::IsPathRooted($StagingCopyPath)) {
    $StagingCopyPath
} else {
    Join-Path $repoRoot $StagingCopyPath
}

$resolvedSummaryCopyPath = if ([System.IO.Path]::IsPathRooted($SummaryCopyPath)) {
    $SummaryCopyPath
} else {
    Join-Path $repoRoot $SummaryCopyPath
}

$resolvedContainerCandidatesPath = "storage/app/tmp/pj360-publish-candidates.json"
$resolvedContainerSummaryPath = "storage/app/tmp/pj360-explanation-draft-summary.json"

New-Item -ItemType Directory -Force -Path (Split-Path -Parent $resolvedStagingCopyPath) | Out-Null
New-Item -ItemType Directory -Force -Path (Split-Path -Parent $resolvedSummaryCopyPath) | Out-Null

Copy-Item -LiteralPath $resolvedInputPath -Destination $resolvedStagingCopyPath -Force

docker compose exec -T app php artisan pj360:stage-explanation-drafts $resolvedContainerCandidatesPath --reset
docker compose exec -T app php artisan pj360:explanation-draft-summary --json=$resolvedContainerSummaryPath

$resolvedHostSummaryPath = Join-Path $repoRoot $resolvedContainerSummaryPath
Copy-Item -LiteralPath $resolvedHostSummaryPath -Destination $resolvedSummaryCopyPath -Force

Write-Host "Staging PJ360 gotowy."
Write-Host "Input:   $resolvedInputPath"
Write-Host "Summary: $resolvedSummaryCopyPath"

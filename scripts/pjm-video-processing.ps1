param(
    [ValidateSet('scan', 'process', 'verify', 'report', 'preview')]
    [string] $Action = 'scan',

    [string] $Source = 'E:\Pytania egzaminacyjne na prawo jazdy tłumaczenia migowe 2025',

    [string] $WorkRoot = 'E:\pjm-video-processing',

    [string] $Manifest = '',

    [ValidateSet('safe-center-crop-80', 'no-crop-720')]
    [string] $Profile = 'safe-center-crop-80',

    [int] $Limit = 0,

    [switch] $Resume,

    [switch] $Force
)

$ErrorActionPreference = 'Stop'

if ([string]::IsNullOrWhiteSpace($Manifest)) {
    $Manifest = Join-Path $WorkRoot 'manifest.json'
}

function New-DirectoryIfMissing {
    param([string] $Path)

    if (-not [string]::IsNullOrWhiteSpace($Path)) {
        New-Item -ItemType Directory -Force -Path $Path | Out-Null
    }
}

function Save-Manifest {
    param(
        [object[]] $Records,
        [string] $Path
    )

    New-DirectoryIfMissing -Path (Split-Path -Parent $Path)
    $tmpPath = "$Path.tmp"
    $Records | ConvertTo-Json -Depth 30 | Set-Content -LiteralPath $tmpPath -Encoding UTF8
    Move-Item -LiteralPath $tmpPath -Destination $Path -Force
}

function Load-Manifest {
    param([string] $Path)

    if (-not (Test-Path -LiteralPath $Path)) {
        throw "Manifest not found: $Path"
    }

    $raw = Get-Content -LiteralPath $Path -Raw
    if ([string]::IsNullOrWhiteSpace($raw)) {
        return @()
    }

    return @($raw | ConvertFrom-Json)
}

function Invoke-ExternalCommand {
    param(
        [string] $Command,
        [string[]] $Arguments
    )

    $output = & $Command @Arguments 2>&1

    return [pscustomobject]@{
        ExitCode = $LASTEXITCODE
        Output = ($output | Out-String).Trim()
    }
}

function Get-SourceProbe {
    param([string] $Path)

    $json = & ffprobe -v error -show_streams -show_format -of json -- $Path
    if ($LASTEXITCODE -ne 0) {
        throw "ffprobe failed for: $Path"
    }

    $probe = $json | ConvertFrom-Json
    $streams = @($probe.streams)
    $videoStreams = @($streams | Where-Object {
        $_.codec_type -eq 'video' -and $_.width -and $_.height
    })

    $imageOnlyCodecs = @('mjpeg', 'png', 'bmp', 'gif', 'jpeg2000', 'webp')
    $selected = $videoStreams | Where-Object {
        $attachedPicture = $_.disposition -and ($_.disposition.attached_pic -eq 1 -or $_.disposition.attached_pic -eq '1')
        $imageOnlyStream = $videoStreams.Count -gt 1 -and $imageOnlyCodecs -contains $_.codec_name

        -not $attachedPicture -and -not $imageOnlyStream
    } | Select-Object -First 1

    if (-not $selected) {
        $selected = $videoStreams | Select-Object -First 1
    }

    $hasAttachedPicture = @($videoStreams | Where-Object {
        ($_.disposition -and ($_.disposition.attached_pic -eq 1 -or $_.disposition.attached_pic -eq '1')) -or
        ($videoStreams.Count -gt 1 -and $imageOnlyCodecs -contains $_.codec_name)
    }).Count -gt 0

    return [pscustomobject]@{
        Probe = $probe
        SelectedStream = $selected
        HasAttachedPicture = $hasAttachedPicture
        VideoStreamCount = $videoStreams.Count
    }
}

function Get-AssetInfoFromName {
    param([string] $FileName)

    $baseName = [System.IO.Path]::GetFileNameWithoutExtension($FileName)

    if ($baseName -notmatch '^pjm(?<externalId>\d+)(?<suffix>[abc])?$') {
        return [pscustomobject]@{
            ExternalId = $null
            AssetRole = 'unknown'
            Suffix = $null
        }
    }

    $suffix = $Matches.suffix
    $role = switch ($suffix) {
        'a' { 'answer_a' }
        'b' { 'answer_b' }
        'c' { 'answer_c' }
        default { 'question' }
    }

    return [pscustomobject]@{
        ExternalId = $Matches.externalId
        AssetRole = $role
        Suffix = $suffix
    }
}

function Get-VideoFilter {
    param(
        [int] $Width,
        [int] $Height,
        [string] $ProfileName
    )

    if ($ProfileName -eq 'no-crop-720') {
        if ($Height -gt 720) {
            return [pscustomobject]@{
                Filter = 'scale=-2:720'
                CropBox = $null
            }
        }

        return [pscustomobject]@{
            Filter = 'null'
            CropBox = $null
        }
    }

    $cropWidth = [int]([math]::Floor(($Width * 0.8) / 2) * 2)
    $cropX = [int](($Width - $cropWidth) / 2)
    if (($cropX % 2) -ne 0) {
        $cropX--
    }

    $filter = "crop=${cropWidth}:${Height}:${cropX}:0"
    if ($Height -gt 720) {
        $filter = "$filter,scale=-2:720"
    }

    return [pscustomobject]@{
        Filter = $filter
        CropBox = [pscustomobject]@{
            width = $cropWidth
            height = $Height
            x = $cropX
            y = 0
        }
    }
}

function Get-OutputPath {
    param(
        [object] $Record,
        [string] $Root,
        [string] $ProfileName
    )

    $outputDir = Join-Path (Join-Path $Root 'output') $ProfileName
    New-DirectoryIfMissing -Path $outputDir

    $baseName = [System.IO.Path]::GetFileNameWithoutExtension($Record.source_filename)
    return Join-Path $outputDir "$baseName.mp4"
}

function Get-SizeMb {
    param([long] $Bytes)
    return [math]::Round($Bytes / 1MB, 3)
}

function Get-SavingPercent {
    param(
        [long] $SourceBytes,
        [long] $ProcessedBytes
    )

    if ($SourceBytes -le 0) {
        return $null
    }

    return [math]::Round((1 - ($ProcessedBytes / $SourceBytes)) * 100, 2)
}

function Get-Sha256Hash {
    param([string] $Path)

    $stream = [System.IO.File]::OpenRead($Path)
    try {
        $sha = [System.Security.Cryptography.SHA256]::Create()
        try {
            $hashBytes = $sha.ComputeHash($stream)
            return ([System.BitConverter]::ToString($hashBytes) -replace '-', '').ToUpperInvariant()
        } finally {
            $sha.Dispose()
        }
    } finally {
        $stream.Dispose()
    }
}

function Set-RecordStatus {
    param(
        [object] $Record,
        [string] $Status,
        [string] $ErrorMessage = ''
    )

    $Record.status = $Status
    $Record.error_message = $ErrorMessage
    $Record.updated_at = (Get-Date).ToString('s')
}

function Invoke-Scan {
    if (-not (Test-Path -LiteralPath $Source)) {
        throw "Source folder not found: $Source"
    }

    if ((Test-Path -LiteralPath $Manifest) -and -not $Force) {
        throw "Manifest already exists. Use -Force to rebuild it: $Manifest"
    }

    New-DirectoryIfMissing -Path $WorkRoot

    $files = @(Get-ChildItem -LiteralPath $Source -File -Filter '*.wmv' -Recurse | Sort-Object Name)
    if ($Limit -gt 0) {
        $files = @($files | Select-Object -First $Limit)
    }

    $records = @()
    $index = 0

    foreach ($file in $files) {
        $index++
        Write-Host "[$index/$($files.Count)] scan $($file.Name)"

        $asset = Get-AssetInfoFromName -FileName $file.Name

        try {
            $probeInfo = Get-SourceProbe -Path $file.FullName
            $selected = $probeInfo.SelectedStream

            if (-not $selected) {
                $records += [pscustomobject]@{
                    source_filename = $file.Name
                    source_path = $file.FullName
                    source_extension = $file.Extension
                    source_bytes = $file.Length
                    source_size_mb = Get-SizeMb -Bytes $file.Length
                    external_id = $asset.ExternalId
                    asset_role = $asset.AssetRole
                    selected_video_stream_index = $null
                    source_width = $null
                    source_height = $null
                    duration_seconds = $null
                    video_stream_count = 0
                    has_attached_picture = $false
                    output_filename = "$([System.IO.Path]::GetFileNameWithoutExtension($file.Name)).mp4"
                    output_path = $null
                    output_tmp_path = $null
                    processing_profile = $Profile
                    crop_box = $null
                    status = 'scan_failed'
                    attempts = 0
                    started_at = $null
                    finished_at = $null
                    updated_at = (Get-Date).ToString('s')
                    error_message = 'No usable video stream found.'
                    processed_bytes = $null
                    processed_size_mb = $null
                    saving_percent = $null
                    processed_checksum_sha256 = $null
                    review_required = $true
                }

                continue
            }

            $filterInfo = Get-VideoFilter -Width ([int]$selected.width) -Height ([int]$selected.height) -ProfileName $Profile
            $outputPath = Get-OutputPath -Record ([pscustomobject]@{ source_filename = $file.Name }) -Root $WorkRoot -ProfileName $Profile

            $records += [pscustomobject]@{
                source_filename = $file.Name
                source_path = $file.FullName
                source_extension = $file.Extension
                source_bytes = $file.Length
                source_size_mb = Get-SizeMb -Bytes $file.Length
                external_id = $asset.ExternalId
                asset_role = $asset.AssetRole
                selected_video_stream_index = [int]$selected.index
                source_width = [int]$selected.width
                source_height = [int]$selected.height
                duration_seconds = if ($selected.duration) { [math]::Round([double]$selected.duration, 3) } elseif ($probeInfo.Probe.format.duration) { [math]::Round([double]$probeInfo.Probe.format.duration, 3) } else { $null }
                video_stream_count = $probeInfo.VideoStreamCount
                has_attached_picture = $probeInfo.HasAttachedPicture
                output_filename = "$([System.IO.Path]::GetFileNameWithoutExtension($file.Name)).mp4"
                output_path = $outputPath
                output_tmp_path = "$outputPath.tmp.mp4"
                processing_profile = $Profile
                video_filter = $filterInfo.Filter
                crop_box = $filterInfo.CropBox
                status = 'pending'
                attempts = 0
                started_at = $null
                finished_at = $null
                updated_at = (Get-Date).ToString('s')
                error_message = ''
                processed_bytes = $null
                processed_size_mb = $null
                saving_percent = $null
                processed_checksum_sha256 = $null
                review_required = [bool]$probeInfo.HasAttachedPicture
            }
        } catch {
            $records += [pscustomobject]@{
                source_filename = $file.Name
                source_path = $file.FullName
                source_extension = $file.Extension
                source_bytes = $file.Length
                source_size_mb = Get-SizeMb -Bytes $file.Length
                external_id = $asset.ExternalId
                asset_role = $asset.AssetRole
                selected_video_stream_index = $null
                source_width = $null
                source_height = $null
                duration_seconds = $null
                video_stream_count = $null
                has_attached_picture = $null
                output_filename = "$([System.IO.Path]::GetFileNameWithoutExtension($file.Name)).mp4"
                output_path = $null
                output_tmp_path = $null
                processing_profile = $Profile
                crop_box = $null
                status = 'scan_failed'
                attempts = 0
                started_at = $null
                finished_at = $null
                updated_at = (Get-Date).ToString('s')
                error_message = $_.Exception.Message
                processed_bytes = $null
                processed_size_mb = $null
                saving_percent = $null
                processed_checksum_sha256 = $null
                review_required = $true
            }
        }
    }

    Save-Manifest -Records $records -Path $Manifest
    Write-Host "Manifest saved: $Manifest"
}

function Invoke-Process {
    $records = Load-Manifest -Path $Manifest

    $candidates = @($records | Where-Object {
        $_.selected_video_stream_index -ne $null -and (
            $_.status -eq 'pending' -or
            $_.status -eq 'failed' -or
            ($Resume -and $_.status -eq 'processing') -or
            ($Force -and ($_.status -eq 'done' -or $_.status -eq 'review_required'))
        )
    })

    if ($Limit -gt 0) {
        $candidates = @($candidates | Select-Object -First $Limit)
    }

    if ($candidates.Count -eq 0) {
        Write-Host 'No records to process.'
        return
    }

    $index = 0
    foreach ($record in $candidates) {
        $index++
        Write-Host "[$index/$($candidates.Count)] process $($record.source_filename)"

        $record.attempts = [int]$record.attempts + 1
        $record.started_at = (Get-Date).ToString('s')
        Set-RecordStatus -Record $record -Status 'processing'
        Save-Manifest -Records $records -Path $Manifest

        try {
            New-DirectoryIfMissing -Path (Split-Path -Parent $record.output_path)

            if (Test-Path -LiteralPath $record.output_tmp_path) {
                Remove-Item -LiteralPath $record.output_tmp_path -Force
            }

            if ((Test-Path -LiteralPath $record.output_path) -and $Force) {
                Remove-Item -LiteralPath $record.output_path -Force
            }

            $args = @(
                '-hide_banner',
                '-loglevel', 'error',
                '-i', $record.source_path,
                '-map', "0:$($record.selected_video_stream_index)",
                '-vf', $record.video_filter,
                '-an',
                '-c:v', 'libx264',
                '-preset', 'slow',
                '-crf', '24',
                '-pix_fmt', 'yuv420p',
                '-movflags', '+faststart',
                $record.output_tmp_path
            )

            $result = Invoke-ExternalCommand -Command 'ffmpeg' -Arguments $args
            if ($result.ExitCode -ne 0) {
                throw $result.Output
            }

            $probeJson = & ffprobe -v error -show_entries 'stream=index,codec_type,codec_name,width,height,duration:format=duration' -of json -- $record.output_tmp_path
            if ($LASTEXITCODE -ne 0) {
                throw "ffprobe failed for tmp output: $($record.output_tmp_path)"
            }

            $outputProbe = $probeJson | ConvertFrom-Json
            $outputStreams = @($outputProbe.streams)
            $video = $outputStreams | Where-Object { $_.codec_type -eq 'video' } | Select-Object -First 1
            $audioCount = @($outputStreams | Where-Object { $_.codec_type -eq 'audio' }).Count

            if (-not $video) {
                throw 'Processed output has no video stream.'
            }

            if ($audioCount -gt 0) {
                throw 'Processed output unexpectedly contains audio.'
            }

            Move-Item -LiteralPath $record.output_tmp_path -Destination $record.output_path -Force

            $outputItem = Get-Item -LiteralPath $record.output_path
            $record.processed_bytes = $outputItem.Length
            $record.processed_size_mb = Get-SizeMb -Bytes $outputItem.Length
            $record.saving_percent = Get-SavingPercent -SourceBytes ([long]$record.source_bytes) -ProcessedBytes ([long]$outputItem.Length)
            $record.processed_checksum_sha256 = Get-Sha256Hash -Path $record.output_path
            $record.finished_at = (Get-Date).ToString('s')
            $record.error_message = ''

            if ($record.review_required) {
                Set-RecordStatus -Record $record -Status 'review_required'
            } else {
                Set-RecordStatus -Record $record -Status 'done'
            }
        } catch {
            if (Test-Path -LiteralPath $record.output_tmp_path) {
                Remove-Item -LiteralPath $record.output_tmp_path -Force
            }

            $record.finished_at = (Get-Date).ToString('s')
            Set-RecordStatus -Record $record -Status 'failed' -ErrorMessage $_.Exception.Message
            Write-Warning "Failed: $($record.source_filename) :: $($_.Exception.Message)"
        }

        Save-Manifest -Records $records -Path $Manifest
    }
}

function Invoke-Verify {
    $records = Load-Manifest -Path $Manifest
    $targets = @($records | Where-Object { $_.status -eq 'done' -or $_.status -eq 'review_required' })
    $errors = @()

    foreach ($record in $targets) {
        if (-not (Test-Path -LiteralPath $record.output_path)) {
            $errors += [pscustomobject]@{ file = $record.source_filename; error = 'Output missing.' }
            continue
        }

        $probeJson = & ffprobe -v error -show_entries 'stream=index,codec_type,codec_name,width,height,duration:format=duration' -of json -- $record.output_path
        if ($LASTEXITCODE -ne 0) {
            $errors += [pscustomobject]@{ file = $record.source_filename; error = 'ffprobe failed.' }
            continue
        }

        $probe = $probeJson | ConvertFrom-Json
        $streams = @($probe.streams)
        $video = $streams | Where-Object { $_.codec_type -eq 'video' } | Select-Object -First 1
        $audioCount = @($streams | Where-Object { $_.codec_type -eq 'audio' }).Count

        if (-not $video) {
            $errors += [pscustomobject]@{ file = $record.source_filename; error = 'No video stream.' }
        }

        if ($audioCount -gt 0) {
            $errors += [pscustomobject]@{ file = $record.source_filename; error = 'Audio stream present.' }
        }
    }

    if ($errors.Count -gt 0) {
        $errors | Format-Table -AutoSize
        throw "Verification failed for $($errors.Count) file(s)."
    }

    Write-Host "Verified $($targets.Count) processed file(s)."
}

function Invoke-Report {
    $records = Load-Manifest -Path $Manifest
    $reportDir = Join-Path $WorkRoot 'reports'
    New-DirectoryIfMissing -Path $reportDir

    $csvPath = Join-Path $reportDir 'pjm-processing-report.csv'
    $summaryPath = Join-Path $reportDir 'pjm-processing-summary.json'

    $records | Select-Object `
        source_filename,
        external_id,
        asset_role,
        status,
        review_required,
        source_size_mb,
        processed_size_mb,
        saving_percent,
        source_width,
        source_height,
        selected_video_stream_index,
        video_filter,
        output_path,
        error_message |
        Export-Csv -LiteralPath $csvPath -Encoding UTF8 -NoTypeInformation

    $statusGroups = @($records | Group-Object status | ForEach-Object {
        [pscustomobject]@{
            status = $_.Name
            count = $_.Count
        }
    })

    $processed = @($records | Where-Object { $_.processed_bytes -ne $null })
    $summary = [pscustomobject]@{
        manifest = $Manifest
        profile = $Profile
        total_records = $records.Count
        by_status = $statusGroups
        source_total_mb = Get-SizeMb -Bytes ([long](($records | Measure-Object source_bytes -Sum).Sum))
        processed_total_mb = if ($processed.Count -gt 0) { Get-SizeMb -Bytes ([long](($processed | Measure-Object processed_bytes -Sum).Sum)) } else { 0 }
        average_saving_percent = if ($processed.Count -gt 0) { [math]::Round((($processed | Measure-Object saving_percent -Average).Average), 2) } else { $null }
        generated_at = (Get-Date).ToString('s')
        csv_report = $csvPath
    }

    $summary | ConvertTo-Json -Depth 10 | Set-Content -LiteralPath $summaryPath -Encoding UTF8
    $summary | ConvertTo-Json -Depth 10
    Write-Host "CSV report: $csvPath"
    Write-Host "Summary: $summaryPath"
}

function Invoke-Preview {
    $records = Load-Manifest -Path $Manifest
    $targets = @($records | Where-Object {
        ($_.status -eq 'done' -or $_.status -eq 'review_required') -and
        (Test-Path -LiteralPath $_.output_path)
    })

    if ($Limit -gt 0) {
        $targets = @($targets | Select-Object -First $Limit)
    }

    if ($targets.Count -eq 0) {
        Write-Host 'No processed files for preview.'
        return
    }

    $previewDir = Join-Path $WorkRoot 'previews'
    New-DirectoryIfMissing -Path $previewDir

    $createdImages = @()

    foreach ($record in $targets) {
        $baseName = [System.IO.Path]::GetFileNameWithoutExtension($record.source_filename)
        $sourceFrame = Join-Path $previewDir "$baseName-source.jpg"
        $outputFrame = Join-Path $previewDir "$baseName-output.jpg"
        $time = 1
        if ($record.duration_seconds -and ([double]$record.duration_seconds) -gt 4) {
            $time = [math]::Round(([double]$record.duration_seconds) / 2, 2)
        }

        $sourceArgs = @(
            '-hide_banner',
            '-loglevel', 'error',
            '-ss', "$time",
            '-i', $record.source_path,
            '-map', "0:$($record.selected_video_stream_index)",
            '-frames:v', '1',
            '-vf', 'scale=320:-1',
            '-q:v', '2',
            $sourceFrame
        )

        $outputArgs = @(
            '-hide_banner',
            '-loglevel', 'error',
            '-ss', "$time",
            '-i', $record.output_path,
            '-frames:v', '1',
            '-vf', 'scale=320:-1',
            '-q:v', '2',
            $outputFrame
        )

        if (Test-Path -LiteralPath $sourceFrame) { Remove-Item -LiteralPath $sourceFrame -Force }
        if (Test-Path -LiteralPath $outputFrame) { Remove-Item -LiteralPath $outputFrame -Force }

        $sourceResult = Invoke-ExternalCommand -Command 'ffmpeg' -Arguments $sourceArgs
        if ($sourceResult.ExitCode -eq 0) {
            $createdImages += $sourceFrame
        }

        $outputResult = Invoke-ExternalCommand -Command 'ffmpeg' -Arguments $outputArgs
        if ($outputResult.ExitCode -eq 0) {
            $createdImages += $outputFrame
        }
    }

    if ($createdImages.Count -eq 0) {
        Write-Host 'No preview frames generated.'
        return
    }

    $sheetPath = Join-Path $previewDir 'pjm-batch-contact-sheet.jpg'
    if (Test-Path -LiteralPath $sheetPath) {
        Remove-Item -LiteralPath $sheetPath -Force
    }

    & magick montage @createdImages -tile 2x -geometry '320x+12+34' -background white -fill black -pointsize 16 -label '%t' $sheetPath
    if ($LASTEXITCODE -ne 0) {
        throw 'ImageMagick montage failed.'
    }

    Write-Host "Preview sheet: $sheetPath"
}

switch ($Action) {
    'scan' { Invoke-Scan }
    'process' { Invoke-Process }
    'verify' { Invoke-Verify }
    'report' { Invoke-Report }
    'preview' { Invoke-Preview }
}

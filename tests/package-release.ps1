param([string]$Version = '1.0.0')

$ErrorActionPreference = 'Stop'
$Root = [IO.Path]::GetFullPath((Split-Path -Parent $PSScriptRoot))
$Slug = 'contract-withdrawal-free-for-woocommerce'
$Dist = [IO.Path]::GetFullPath((Join-Path $Root 'dist'))
$Stage = [IO.Path]::GetFullPath((Join-Path $Dist ('.staging-' + $Version)))

function Assert-ChildPath([string]$Path, [string]$Parent) {
	$fullPath = [IO.Path]::GetFullPath($Path).TrimEnd('\')
	$fullParent = [IO.Path]::GetFullPath($Parent).TrimEnd('\') + '\'
	if (-not $fullPath.StartsWith($fullParent, [StringComparison]::OrdinalIgnoreCase)) {
		throw "Refusing filesystem operation outside $Parent`: $Path"
	}
}

New-Item -ItemType Directory -Path $Dist -Force | Out-Null
Assert-ChildPath -Path $Stage -Parent $Dist
if (Test-Path -LiteralPath $Stage) { Remove-Item -LiteralPath $Stage -Recurse -Force }
$PluginStage = Join-Path $Stage $Slug
New-Item -ItemType Directory -Path $PluginStage -Force | Out-Null

$ReleasePaths = @(
	'assets', 'docs', 'includes', 'languages', 'templates',
	'CHANGELOG.md', 'LICENSE.txt', 'README.md', 'index.php', 'readme.txt',
	'contract-withdrawal-free-for-woocommerce.php', 'uninstall.php'
)
foreach ($Relative in $ReleasePaths) {
	$Source = Join-Path $Root $Relative
	if (-not (Test-Path -LiteralPath $Source)) { throw "Required release path is missing: $Relative" }
	Copy-Item -LiteralPath $Source -Destination $PluginStage -Recurse -Force
}

$Zip = Join-Path $Dist ($Slug + '-' + $Version + '.zip')
if (Test-Path -LiteralPath $Zip) { Remove-Item -LiteralPath $Zip -Force }
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$ZipStream = [IO.File]::Open($Zip, [IO.FileMode]::CreateNew)
$ZipArchive = New-Object IO.Compression.ZipArchive($ZipStream, [IO.Compression.ZipArchiveMode]::Create, $false)
try {
	Get-ChildItem -LiteralPath $PluginStage -Recurse -File | ForEach-Object {
		$EntryName = $_.FullName.Substring($Stage.Length + 1).Replace('\', '/')
		$Entry = $ZipArchive.CreateEntry($EntryName, [IO.Compression.CompressionLevel]::Optimal)
		$Input = [IO.File]::OpenRead($_.FullName)
		$Output = $Entry.Open()
		try { $Input.CopyTo($Output) }
		finally { $Output.Dispose(); $Input.Dispose() }
	}
}
finally { $ZipArchive.Dispose(); $ZipStream.Dispose() }

$Archive = [IO.Compression.ZipFile]::OpenRead($Zip)
try {
	$Names = @($Archive.Entries | ForEach-Object { $_.FullName.Replace('\', '/') })
	if ($Archive.Entries | Where-Object { $_.FullName.Contains('\') }) {
		throw 'Installable ZIP contains non-portable path separators.'
	}
	if ($Names -notcontains "$Slug/contract-withdrawal-free-for-woocommerce.php") {
		throw 'Installable ZIP does not contain the plugin bootstrap.'
	}
	if ($Names | Where-Object { $_ -match '(^|/)(tests|dist|\.github|\.git|\.projectbrain|graphify-out)(/|$)' }) {
		throw 'Installable ZIP contains development-only paths.'
	}
	if ($Names | Where-Object { $_ -match '(history\.php|model\.php|print\.js|print\.css)$' }) {
		throw 'Installable ZIP contains Premium-only files.'
	}
}
finally { $Archive.Dispose() }

$Hash = (Get-FileHash -Algorithm SHA256 -LiteralPath $Zip).Hash.ToLowerInvariant()
$ChecksumFile = Join-Path $Dist 'SHA256SUMS.txt'
('{0}  {1}' -f $Hash, (Split-Path -Leaf $Zip)) | Set-Content -LiteralPath $ChecksumFile -Encoding utf8

Assert-ChildPath -Path $Stage -Parent $Dist
Remove-Item -LiteralPath $Stage -Recurse -Force

$Item = Get-Item -LiteralPath $Zip
[PSCustomObject]@{ File = $Item.Name; Bytes = $Item.Length; SHA256 = $Hash }

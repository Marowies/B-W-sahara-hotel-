param([Parameter(Mandatory)] [string] $TestAutoload, [Parameter(Mandatory)] [string] $TestApp, [Parameter(Mandatory)] [string] $SyntheticSqlite)
$ErrorActionPreference = 'Stop'
$checks = 0
foreach ($scenario in @('slug', 'all')) {
    $cluster = $null
    foreach ($path in @('/rooms/synthetic-room-1', '/ar/rooms/synthetic-room-ar', '/zh/rooms/synthetic-room-1')) {
        $json = & php -d allow_url_fopen=0 -d 'disable_functions=curl_exec,curl_multi_exec,fsockopen,pfsockopen,stream_socket_client' "$PSScriptRoot/MissingTranslationProbe.php" $TestAutoload $TestApp $SyntheticSqlite $path $scenario
        if ($LASTEXITCODE -ne 0) { throw ($json -join "`n") }
        $row = ($json -join "`n") | ConvertFrom-Json
        if ($row.status -ne 200 -or $row.canonical.Count -ne 1 -or $row.canonical[0] -ne "http://127.0.0.1:8765$path") { throw "Invalid canonical/status: $scenario $path" }
        $actual = $row.hreflang | ConvertTo-Json -Compress
        if ($null -ne $cluster -and $actual -ne $cluster) { throw "Nonreciprocal cluster: $scenario $path" }
        $cluster = $actual
        if ($row.hreflang.'zh-cn' -ne 'http://127.0.0.1:8765/zh/rooms/synthetic-room-1' -or $row.hreflang.ar -ne 'http://127.0.0.1:8765/ar/rooms/synthetic-room-ar' -or $row.hreflang.'en-us' -ne 'http://127.0.0.1:8765/rooms/synthetic-room-1') { throw 'Targets differ from tested canonical URLs.' }
        $checks += 3
    }
}
"PASS: $checks missing-translation runtime checks (slug absent and entire Chinese room translation absent)."

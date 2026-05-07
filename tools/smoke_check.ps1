param(
    [string]$BaseUrl = "https://foxwisdom.com"
)

$ErrorActionPreference = "Stop"

$checks = @(
    @{ Name = "Homepage"; Url = "$BaseUrl/" },
    @{ Name = "Booking page"; Url = "$BaseUrl/booking.html" },
    @{ Name = "Knowledge page"; Url = "$BaseUrl/knowledge.html" },
    @{ Name = "Reviews page"; Url = "$BaseUrl/reviews.html" },
    @{ Name = "Settings API"; Url = "$BaseUrl/api/settings.php" },
    @{ Name = "Content API"; Url = "$BaseUrl/api/content.php?type=peer_recognitions" },
    @{ Name = "Booking availability API"; Url = "$BaseUrl/api/booking.php?month=$(Get-Date -Format 'yyyy-MM')" }
)

$failed = 0
foreach ($check in $checks) {
    try {
        $response = Invoke-WebRequest -Uri $check.Url -Method GET -UseBasicParsing -TimeoutSec 20
        if ($response.StatusCode -ge 200 -and $response.StatusCode -lt 400) {
            Write-Host "[PASS] $($check.Name) $($response.StatusCode)"
        } else {
            Write-Host "[FAIL] $($check.Name) $($response.StatusCode)"
            $failed++
        }
    } catch {
        Write-Host "[FAIL] $($check.Name) $($_.Exception.Message)"
        $failed++
    }
}

if ($failed -gt 0) {
    throw "$failed smoke check(s) failed."
}

Write-Host "All smoke checks passed."

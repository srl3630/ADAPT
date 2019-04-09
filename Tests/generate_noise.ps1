
# Successful logon - event ID 4624
Write-EventLog -LogName "Security" -EventId 4624 -Message "noise - user successful logon"

# Failed logon - event ID 4625
Write-EventLog -LogName "Security" -EventId 4625 -Message "noise - user failed logon"

# File was deteleted - event ID 5141
Write-EventLog -LogName "Security" -EventId 5141 -Message "noise - file deleted"

# File in AD was created - event ID 5137
Write-EventLog -LogName "Security" -EventId 5137 -Message "noise - file created" 

# File permissions - event ID 4670
Write-EventLog -LogName "Security" -EventId 4670 -Message "noise - file permissions"
 
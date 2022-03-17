$file=Get-ChildItem -Path "$(Get-Location).\.." -Exclude ".gitignore", ".env.example", "*.save" , "*.zip", ".git", "doc", "test_data_export", "ldap_import", ".htgrps", ".htusers", "README.md"

$compress = @{
  Path = $file
  CompressionLevel = "Fastest"
  DestinationPath = "..\temp_$(Get-Date -Format MM_dd_yyyy_HH_mm_ss).zip"
}
Compress-Archive @compress -Force
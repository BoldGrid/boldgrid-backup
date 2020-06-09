# Orphaned Files

An orphaned file is a backup file that should not exist. For example, while a `.zip` file is being written 
it may temporarily be named `backup.zip.JIE4T9A`. When the file is successfully written, the random `JIE4T9A` 
extension will be removed.

If the backup process is killed, the orphaned `backup.zip.JIE4T9A` file will exists and just take up 
space. It should be removed.

## Naming Conventions

### PclZip ###

PclZip does not appear to store `.zip` files anywhere temporarily while they're written. Testing shows 
backup files are written in the permanent location using the permanent filename.

```
/home/user/boldgrid_backup/backup.zip
```

### ZipArchive ###

ZipArchive will append a random string to the end of the file, such as `backup.zip.hzWRMm`, and then 
remove it when the file is written.

```
/home/user/boldgrid_backup/backup.zip.hzWRMm
```

### System Zip ###

We've setup System Zip to archive files in a separate folder, and then move them when done.

```
/home/user/boldgrid_backup/system-zip-temp/zi5XqLpx
```

In testing, when the backup process received a `kill -9`, the zip file was created, but it was incomplete:

```
/home/user/boldgrid_backup/backup.zip
```

Running a test command still showed the zip was valid.

```
unzip -t backup.zip
Archive:  backup.zip
    testing: domain.20200605-181832.sql   OK
    testing: domain.log   OK
No errors detected in compressed data of backup.zip.
```

As you can see, it looks like only 2 files, the `.sql` file and the `.log` file were written.
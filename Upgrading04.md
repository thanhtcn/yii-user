  * Make a backup.
  * Overwrite files.
  * Add fields for tbl\_profiles\_fields using phpmyadmin: widget - VARCHAR(255) and widgetparams - VARCHAR(5000). Or use SQL:
```
ALTER TABLE `tbl_profiles_fields`
ADD `widget` VARCHAR(255) NOT NULL DEFAULT '' AFTER `default` ,
ADD `widgetparams` VARCHAR(5000) NOT NULL DEFAULT '' AFTER `widget` 
```
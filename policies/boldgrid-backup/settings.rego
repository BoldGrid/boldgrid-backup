package main

import rego.v1

# backup_directory must stay a string without a parent segment or a newline.
deny contains msg if {
	dir := input.backup_directory
	not is_string(dir)
	msg := "backup_directory must be a string"
}

deny contains msg if {
	dir := input.backup_directory
	is_string(dir)
	rejected_backup_directory(dir)
	msg := "backup_directory must be a path without a parent segment or newline"
}

rejected_backup_directory(dir) if {
	regex.match(`[\r\n]`, dir)
}

rejected_backup_directory(dir) if {
	regex.match(`(^|[\\/])\.\.([\\/]|$)`, dir)
}

# retention_count matches the settings limit of 1 through 99.
deny contains msg if {
	object.get(input, "retention_count", null) != null
	not valid_retention
	msg := "retention_count must be an integer from 1 to 99"
}

valid_retention if {
	n := input.retention_count
	is_number(n)
	n >= 1
	n <= 99
	n == ceil(n)
}

# notification_email must be a string and must not carry a newline.
deny contains msg if {
	email := input.notification_email
	not is_string(email)
	msg := "notification_email must be a string"
}

deny contains msg if {
	email := input.notification_email
	is_string(email)
	regex.match(`[\r\n]`, email)
	msg := "notification_email must not contain a newline"
}

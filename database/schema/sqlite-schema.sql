CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_expiration_index" on "cache"("expiration");
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_locks_expiration_index" on "cache_locks"("expiration");
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "project_assessments"(
  "id" integer primary key autoincrement not null,
  "assessment_type" varchar not null,
  "project_kickoff" date,
  "due_date" date,
  "complete_date" date,
  "project_coordinator" varchar,
  "assessor" varchar,
  "priority" varchar check("priority" in('Critical', 'High', 'Medium', 'Low')) not null default 'Medium',
  "bcd_id" varchar,
  "vulnerability_assessment" tinyint(1) not null default '0',
  "secure_code_review" tinyint(1) not null default '0',
  "status" varchar check("status" in('Open', 'In Progress', 'Closed')) not null default 'Open',
  "bcd_url" varchar,
  "comments" text,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "system_architecture_review" tinyint(1) not null default '0',
  "penetration_test" tinyint(1) not null default '0',
  "security_hardening" tinyint(1) not null default '0',
  "antimalware_protection" tinyint(1) not null default '0',
  "network_security" tinyint(1) not null default '0',
  "security_monitoring" tinyint(1) not null default '0',
  "system_access_matrix" tinyint(1) not null default '0',
  "system_architecture_review_evidence" varchar,
  "system_architecture_review_status" varchar check("system_architecture_review_status" in('Not Started', 'In Progress', 'Completed', 'N/A')) not null default 'Not Started',
  "penetration_test_evidence" varchar,
  "penetration_test_status" varchar check("penetration_test_status" in('Not Started', 'In Progress', 'Completed', 'N/A')) not null default 'Not Started',
  "security_hardening_evidence" varchar,
  "security_hardening_status" varchar check("security_hardening_status" in('Not Started', 'In Progress', 'Completed', 'N/A')) not null default 'Not Started',
  "vulnerability_assessment_evidence" varchar,
  "vulnerability_assessment_status" varchar check("vulnerability_assessment_status" in('Not Started', 'In Progress', 'Completed', 'N/A')) not null default 'Not Started',
  "secure_code_review_evidence" varchar,
  "secure_code_review_status" varchar check("secure_code_review_status" in('Not Started', 'In Progress', 'Completed', 'N/A')) not null default 'Not Started',
  "antimalware_protection_evidence" varchar,
  "antimalware_protection_status" varchar check("antimalware_protection_status" in('Not Started', 'In Progress', 'Completed', 'N/A')) not null default 'Not Started',
  "network_security_evidence" varchar,
  "network_security_status" varchar check("network_security_status" in('Not Started', 'In Progress', 'Completed', 'N/A')) not null default 'Not Started',
  "security_monitoring_evidence" varchar,
  "security_monitoring_status" varchar check("security_monitoring_status" in('Not Started', 'In Progress', 'Completed', 'N/A')) not null default 'Not Started',
  "system_access_matrix_evidence" varchar,
  "system_access_matrix_status" varchar check("system_access_matrix_status" in('Not Started', 'In Progress', 'Completed', 'N/A')) not null default 'Not Started',
  "slug" varchar,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "project_assessments_slug_unique" on "project_assessments"(
  "slug"
);
CREATE TABLE IF NOT EXISTS "vulnerabilities"(
  "id" integer primary key autoincrement not null,
  "vuln_id" varchar,
  "severity" varchar check("severity" in('Critical', 'High', 'Medium', 'Low')) not null,
  "asset" varchar not null,
  "title" varchar not null,
  "description" text,
  "recommendation" text,
  "status" varchar check("status" in('Open', 'In Progress', 'Resolved')) not null default 'Open',
  "source_file" varchar,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "vuln_scans"(
  "id" integer primary key autoincrement not null,
  "assessment_id" integer not null,
  "filename" varchar not null,
  "is_baseline" tinyint(1) not null default '0',
  "finding_count" integer not null default '0',
  "notes" text,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "host_count" integer not null default '0',
  foreign key("assessment_id") references "vuln_assessments"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "vuln_findings"(
  "id" integer primary key autoincrement not null,
  "scan_id" integer not null,
  "assessment_id" integer not null,
  "ip_address" varchar not null,
  "hostname" varchar,
  "plugin_id" varchar not null,
  "cve" varchar,
  "severity" varchar check("severity" in('Critical', 'High', 'Medium', 'Low', 'Info')) not null,
  "vuln_name" varchar not null,
  "description" text,
  "remediation_text" text,
  "port" varchar,
  "protocol" varchar,
  "plugin_output" text,
  "scan_timestamp" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "os_detected" varchar,
  "vuln_category" varchar,
  "affected_component" varchar,
  "os_name" varchar,
  "os_family" varchar,
  "os_confidence" integer not null default '0',
  "os_kernel" varchar,
  "cvss_score" numeric,
  foreign key("scan_id") references "vuln_scans"("id") on delete cascade,
  foreign key("assessment_id") references "vuln_assessments"("id") on delete cascade
);
CREATE INDEX "vuln_findings_ip_address_index" on "vuln_findings"("ip_address");
CREATE INDEX "vuln_findings_plugin_id_index" on "vuln_findings"("plugin_id");
CREATE TABLE IF NOT EXISTS "asset_inventories"(
  "id" integer primary key autoincrement not null,
  "ip_address" varchar not null,
  "hostname" varchar,
  "identified_scope" varchar check("identified_scope" in('PCI', 'DMZ', 'Internal', 'External', 'Third-Party')) not null default 'Internal',
  "environment" varchar check("environment" in('PROD', 'UAT', 'STAGE')) not null default 'PROD',
  "system_name" varchar,
  "classification_level" integer not null default '3',
  "critical_level" varchar check("critical_level" in('Mission-Critical', 'Business-Critical', 'Business Operational', 'Administrative', 'None-Bank')) not null default 'Business Operational',
  "os" varchar,
  "open_ports" varchar,
  "vuln_critical" integer not null default '0',
  "vuln_high" integer not null default '0',
  "vuln_medium" integer not null default '0',
  "vuln_low" integer not null default '0',
  "tags" varchar,
  "notes" text,
  "status" varchar check("status" in('Active', 'Inactive', 'Decommissioned')) not null default 'Active',
  "last_scanned_at" datetime,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "asset_inventories_ip_address_index" on "asset_inventories"(
  "ip_address"
);
CREATE INDEX "asset_inventories_identified_scope_index" on "asset_inventories"(
  "identified_scope"
);
CREATE INDEX "asset_inventories_environment_index" on "asset_inventories"(
  "environment"
);
CREATE INDEX "asset_inventories_status_index" on "asset_inventories"("status");
CREATE TABLE IF NOT EXISTS "vuln_tracked"(
  "id" integer primary key autoincrement not null,
  "assessment_id" integer not null,
  "ip_address" varchar not null,
  "hostname" varchar,
  "plugin_id" varchar not null,
  "cve" varchar,
  "vuln_name" varchar not null,
  "description" text,
  "remediation_text" text,
  "severity" varchar check("severity" in('Critical', 'High', 'Medium', 'Low', 'Info')) not null,
  "port" varchar,
  "protocol" varchar,
  "vuln_category" varchar,
  "affected_component" varchar,
  "os_detected" varchar,
  "os_name" varchar,
  "os_family" varchar,
  "tracking_status" varchar check("tracking_status" in('New', 'Open', 'Unresolved', 'Reopened', 'Resolved')) not null default 'New',
  "first_seen_at" datetime not null,
  "last_seen_at" datetime not null,
  "resolved_at" datetime,
  "first_scan_id" integer not null,
  "last_scan_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  "cvss_score" numeric,
  "plugin_output" text,
  foreign key("assessment_id") references "vuln_assessments"("id") on delete cascade,
  foreign key("first_scan_id") references "vuln_scans"("id"),
  foreign key("last_scan_id") references "vuln_scans"("id")
);
CREATE INDEX "vuln_tracked_assessment_id_index" on "vuln_tracked"(
  "assessment_id"
);
CREATE INDEX "vuln_tracked_tracking_status_index" on "vuln_tracked"(
  "tracking_status"
);
CREATE INDEX "vuln_tracked_severity_index" on "vuln_tracked"("severity");
CREATE INDEX "vuln_tracked_ip_address_index" on "vuln_tracked"("ip_address");
CREATE INDEX "vuln_tracked_last_seen_at_index" on "vuln_tracked"(
  "last_seen_at"
);
CREATE TABLE IF NOT EXISTS "vuln_tracked_history"(
  "id" integer primary key autoincrement not null,
  "tracked_id" integer not null,
  "scan_id" integer not null,
  "event_type" varchar check("event_type" in('created', 'still_present', 'severity_changed', 'status_changed', 'reappeared', 'resolved')) not null,
  "prev_status" varchar check("prev_status" in('New', 'Open', 'Unresolved', 'Reopened', 'Resolved')),
  "new_status" varchar check("new_status" in('New', 'Open', 'Unresolved', 'Reopened', 'Resolved')),
  "prev_severity" varchar check("prev_severity" in('Critical', 'High', 'Medium', 'Low', 'Info')),
  "new_severity" varchar check("new_severity" in('Critical', 'High', 'Medium', 'Low', 'Info')),
  "note" text,
  "changed_at" datetime not null default CURRENT_TIMESTAMP,
  foreign key("tracked_id") references "vuln_tracked"("id") on delete cascade,
  foreign key("scan_id") references "vuln_scans"("id")
);
CREATE INDEX "vuln_tracked_history_tracked_id_index" on "vuln_tracked_history"(
  "tracked_id"
);
CREATE INDEX "vuln_tracked_history_scan_id_index" on "vuln_tracked_history"(
  "scan_id"
);
CREATE INDEX "vuln_tracked_history_event_type_index" on "vuln_tracked_history"(
  "event_type"
);
CREATE INDEX "vuln_tracked_history_changed_at_index" on "vuln_tracked_history"(
  "changed_at"
);
CREATE TABLE IF NOT EXISTS "vuln_host_os"(
  "id" integer primary key autoincrement not null,
  "assessment_id" integer not null,
  "scan_id" integer,
  "ip_address" varchar not null,
  "hostname" varchar,
  "os_name" varchar,
  "os_family" varchar,
  "os_confidence" integer not null default('0'),
  "detection_sources" text,
  "os_override" varchar,
  "os_override_family" varchar,
  "os_override_by" integer,
  "os_override_at" datetime,
  "os_override_note" text,
  "os_history" text,
  "created_at" datetime,
  "updated_at" datetime,
  "os_kernel" varchar,
  "asset_criticality" integer,
  "criticality_set_by" integer,
  "criticality_set_at" datetime,
  "system_name" varchar,
  "system_owner" varchar,
  "identified_scope" varchar,
  "environment" varchar,
  "location" varchar,
  foreign key("os_override_by") references "users"("id") on delete set null on update no action,
  foreign key("scan_id") references vuln_scans("id") on delete set null on update no action,
  foreign key("assessment_id") references vuln_assessments("id") on delete cascade on update no action,
  foreign key("criticality_set_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "vuln_host_os_assessment_id_ip_address_unique" on "vuln_host_os"(
  "assessment_id",
  "ip_address"
);
CREATE INDEX "vuln_host_os_assessment_id_os_family_index" on "vuln_host_os"(
  "assessment_id",
  "os_family"
);
CREATE INDEX "vuln_host_os_os_family_index" on "vuln_host_os"("os_family");
CREATE TABLE IF NOT EXISTS "threat_intel_items"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "type" varchar not null default 'CVE',
  "cve_id" varchar,
  "cvss_score" numeric,
  "severity" varchar not null default 'Medium',
  "description" text,
  "affected_products" text,
  "source" varchar,
  "source_url" varchar,
  "published_at" date,
  "status" varchar not null default 'Active',
  "tags" text,
  "ioc_type" varchar,
  "ioc_value" varchar,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "threat_intel_items_cve_id_index" on "threat_intel_items"(
  "cve_id"
);
CREATE INDEX "ti_type_idx" on "threat_intel_items"("type");
CREATE INDEX "ti_status_idx" on "threat_intel_items"("status");
CREATE INDEX "ti_severity_idx" on "threat_intel_items"("severity");
CREATE INDEX "ti_created_at_idx" on "threat_intel_items"("created_at");
CREATE INDEX "vt_plugin_id_idx" on "vuln_tracked"("plugin_id");
CREATE INDEX "vt_cve_idx" on "vuln_tracked"("cve");
CREATE INDEX "vt_last_scan_idx" on "vuln_tracked"("last_scan_id");
CREATE INDEX "vf_assessment_idx" on "vuln_findings"("assessment_id");
CREATE INDEX "vf_scan_idx" on "vuln_findings"("scan_id");
CREATE INDEX "vf_assessment_severity_idx" on "vuln_findings"(
  "assessment_id",
  "severity"
);
CREATE INDEX "vs_assessment_idx" on "vuln_scans"("assessment_id");
CREATE INDEX "vs_assessment_created_idx" on "vuln_scans"(
  "assessment_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "agents"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "hostname" varchar not null,
  "ip_address" varchar not null,
  "os" varchar,
  "status" varchar check("status" in('online', 'offline')) not null default 'online',
  "last_seen" datetime,
  "api_token" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "agents_uuid_index" on "agents"("uuid");
CREATE INDEX "agents_hostname_index" on "agents"("hostname");
CREATE UNIQUE INDEX "agents_uuid_unique" on "agents"("uuid");
CREATE INDEX "agents_status_index" on "agents"("status");
CREATE INDEX "agents_last_seen_index" on "agents"("last_seen");
CREATE UNIQUE INDEX "agents_api_token_unique" on "agents"("api_token");
CREATE TABLE IF NOT EXISTS "agent_hardware_snapshots"(
  "id" integer primary key autoincrement not null,
  "agent_id" integer not null,
  "cpu" varchar,
  "ram" integer,
  "disk" integer,
  "os_version" varchar,
  "collected_at" datetime not null default CURRENT_TIMESTAMP,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("agent_id") references "agents"("id") on delete cascade
);
CREATE INDEX "agent_hardware_snapshots_agent_id_collected_at_index" on "agent_hardware_snapshots"(
  "agent_id",
  "collected_at"
);
CREATE TABLE IF NOT EXISTS "installed_software"(
  "id" integer primary key autoincrement not null,
  "agent_id" integer not null,
  "name" varchar not null,
  "version" varchar not null,
  "installed_at" datetime,
  "collected_at" datetime not null default CURRENT_TIMESTAMP,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("agent_id") references "agents"("id") on delete cascade
);
CREATE UNIQUE INDEX "uq_agent_software" on "installed_software"(
  "agent_id",
  "name",
  "version"
);
CREATE INDEX "installed_software_name_index" on "installed_software"("name");
CREATE INDEX "installed_software_agent_id_collected_at_index" on "installed_software"(
  "agent_id",
  "collected_at"
);
CREATE INDEX "installed_software_version_index" on "installed_software"(
  "version"
);
CREATE TABLE IF NOT EXISTS "agent_logs"(
  "id" integer primary key autoincrement not null,
  "agent_id" integer not null,
  "event_type" varchar check("event_type" in('register', 'heartbeat', 'update', 'error')) not null,
  "message" text,
  "created_at" datetime not null default CURRENT_TIMESTAMP,
  foreign key("agent_id") references "agents"("id") on delete cascade
);
CREATE INDEX "agent_logs_agent_id_created_at_index" on "agent_logs"(
  "agent_id",
  "created_at"
);
CREATE INDEX "agent_logs_event_type_index" on "agent_logs"("event_type");
CREATE TABLE IF NOT EXISTS "vuln_assessment_scope"(
  "vuln_assessment_id" integer not null,
  "assessment_scope_id" integer not null,
  foreign key("vuln_assessment_id") references "vuln_assessments"("id") on delete cascade,
  foreign key("assessment_scope_id") references "assessment_scopes"("id") on delete cascade,
  primary key("vuln_assessment_id", "assessment_scope_id")
);
CREATE TABLE IF NOT EXISTS "assessment_scope_groups"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "description" text,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "assessment_scopes"(
  "id" integer primary key autoincrement not null,
  "ip_address" varchar,
  "hostname" varchar,
  "system_name" varchar,
  "system_criticality" integer,
  "system_owner" varchar,
  "identified_scope" varchar,
  "environment" varchar,
  "location" varchar,
  "notes" text,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "group_id" integer,
  foreign key("created_by") references "users"("id") on delete set null on update no action,
  foreign key("group_id") references "assessment_scope_groups"("id") on delete set null
);
CREATE UNIQUE INDEX "uq_tracked_key" on "vuln_tracked"(
  "assessment_id",
  "ip_address",
  "plugin_id"
);
CREATE UNIQUE INDEX "uq_scan_assessment_filename" on "vuln_scans"(
  "assessment_id",
  "filename"
);
CREATE TABLE IF NOT EXISTS "user_groups"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "description" text,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "user_groups_name_unique" on "user_groups"("name");
CREATE TABLE IF NOT EXISTS "user_group_members"(
  "id" integer primary key autoincrement not null,
  "user_group_id" integer not null,
  "user_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_group_id") references "user_groups"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "user_group_members_user_group_id_user_id_unique" on "user_group_members"(
  "user_group_id",
  "user_id"
);
CREATE TABLE IF NOT EXISTS "vuln_remediations"(
  "id" integer primary key autoincrement not null,
  "assessment_id" integer not null,
  "plugin_id" varchar not null,
  "ip_address" varchar not null,
  "status" varchar not null default('Open'),
  "assigned_to" varchar,
  "due_date" date,
  "comments" text,
  "evidence_path" varchar,
  "updated_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "assigned_group_id" integer,
  foreign key("updated_by") references "users"("id") on delete set null on update no action,
  foreign key("assessment_id") references vuln_assessments("id") on delete cascade on update no action,
  foreign key("assigned_group_id") references "user_groups"("id") on delete set null
);
CREATE UNIQUE INDEX "vuln_remediations_assessment_id_plugin_id_ip_address_unique" on "vuln_remediations"(
  "assessment_id",
  "plugin_id",
  "ip_address"
);
CREATE TABLE IF NOT EXISTS "sla_policies"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "description" text,
  "critical_days" integer not null default '7',
  "high_days" integer not null default '30',
  "medium_days" integer not null default '90',
  "low_days" integer not null default '180',
  "is_default" tinyint(1) not null default '0',
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "sla_policies_name_unique" on "sla_policies"("name");
CREATE TABLE IF NOT EXISTS "vuln_assessments"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "description" text,
  "scan_date" date,
  "environment" varchar not null default('Production'),
  "scanner_type" varchar not null default('Tenable Nessus'),
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "period_start" date,
  "period_end" date,
  "uuid" varchar not null,
  "scope_group_id" integer,
  "sla_policy_id" integer,
  foreign key("scope_group_id") references assessment_scope_groups("id") on delete set null on update no action,
  foreign key("created_by") references "users"("id") on delete set null on update no action,
  foreign key("sla_policy_id") references "sla_policies"("id") on delete set null
);
CREATE UNIQUE INDEX "vuln_assessments_uuid_unique" on "vuln_assessments"(
  "uuid"
);
CREATE TABLE IF NOT EXISTS "site_settings"(
  "id" integer primary key autoincrement not null,
  "key" varchar not null,
  "value" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "site_settings_key_unique" on "site_settings"("key");
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "role" varchar check("role" in('administrator','assessor')) not null default 'assessor',
  "mfa_enabled" tinyint(1) not null default '1'
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2026_04_09_092442_create_project_assessments_table',2);
INSERT INTO migrations VALUES(5,'2026_04_09_094656_update_assessment_scope_columns',3);
INSERT INTO migrations VALUES(6,'2026_04_09_101123_add_role_to_users_table',4);
INSERT INTO migrations VALUES(7,'2026_04_10_022115_add_evidence_status_to_assessments_table',5);
INSERT INTO migrations VALUES(8,'2026_04_10_034743_add_slug_to_project_assessments_table',6);
INSERT INTO migrations VALUES(9,'2026_04_10_043235_add_mfa_enabled_to_users_table',7);
INSERT INTO migrations VALUES(10,'2026_04_10_130218_create_vulnerabilities_table',8);
INSERT INTO migrations VALUES(11,'2026_04_10_132000_create_vuln_assessments_table',9);
INSERT INTO migrations VALUES(12,'2026_04_10_132000_create_vuln_scans_table',9);
INSERT INTO migrations VALUES(13,'2026_04_10_132001_create_vuln_findings_table',9);
INSERT INTO migrations VALUES(14,'2026_04_10_132002_create_vuln_remediations_table',9);
INSERT INTO migrations VALUES(15,'2026_04_10_141205_add_period_to_vuln_assessments_table',10);
INSERT INTO migrations VALUES(16,'2026_04_11_000001_create_asset_inventories_table',11);
INSERT INTO migrations VALUES(17,'2026_04_11_000002_add_os_detected_to_vuln_findings_table',12);
INSERT INTO migrations VALUES(18,'2026_04_11_000003_add_classification_to_vuln_findings_table',13);
INSERT INTO migrations VALUES(19,'2026_04_11_000004_add_os_fields_to_vuln_findings_table',14);
INSERT INTO migrations VALUES(20,'2026_04_11_000005_create_vuln_host_os_table',14);
INSERT INTO migrations VALUES(21,'2026_04_13_000001_add_host_count_to_vuln_scans_table',15);
INSERT INTO migrations VALUES(22,'2026_04_13_000003_create_vuln_tracked_tables',16);
INSERT INTO migrations VALUES(23,'2026_04_14_000001_add_os_kernel_to_vuln_findings_table',17);
INSERT INTO migrations VALUES(24,'2026_04_14_000002_add_os_kernel_to_vuln_host_os_table',17);
INSERT INTO migrations VALUES(25,'2026_04_14_000003_add_asset_criticality_to_vuln_host_os_table',18);
INSERT INTO migrations VALUES(26,'2026_04_14_000004_add_system_info_to_vuln_host_os_table',19);
INSERT INTO migrations VALUES(27,'2026_04_15_000001_create_threat_intel_items_table',20);
INSERT INTO migrations VALUES(28,'2026_04_15_000002_add_performance_indexes',21);
INSERT INTO migrations VALUES(33,'2026_04_17_000005_add_scope_env_location_to_vuln_host_os_table',22);
INSERT INTO migrations VALUES(34,'2026_04_17_000001_create_agents_table',23);
INSERT INTO migrations VALUES(35,'2026_04_17_000002_create_agent_hardware_snapshots_table',23);
INSERT INTO migrations VALUES(36,'2026_04_17_000003_create_installed_software_table',23);
INSERT INTO migrations VALUES(37,'2026_04_17_000004_create_agent_logs_table',23);
INSERT INTO migrations VALUES(38,'2026_04_20_000001_create_assessment_scopes_table',23);
INSERT INTO migrations VALUES(39,'2026_04_20_000002_add_uuid_to_vuln_assessments_table',24);
INSERT INTO migrations VALUES(40,'2026_04_20_000003_create_vuln_assessment_scope_table',25);
INSERT INTO migrations VALUES(41,'2026_04_20_000004_create_assessment_scope_groups_table',26);
INSERT INTO migrations VALUES(42,'2026_04_20_000005_add_group_id_to_assessment_scopes_table',26);
INSERT INTO migrations VALUES(43,'2026_04_20_000006_add_cvss_score_to_vuln_tables',27);
INSERT INTO migrations VALUES(46,'2026_04_20_000007_fix_vuln_tracked_port_unique_key',28);
INSERT INTO migrations VALUES(47,'2026_04_20_000008_update_vuln_tracked_statuses',28);
INSERT INTO migrations VALUES(48,'2026_04_21_000001_add_scope_group_id_to_vuln_assessments_table',29);
INSERT INTO migrations VALUES(50,'2026_04_21_000002_revert_vuln_tracked_unique_key_to_ip_plugin',30);
INSERT INTO migrations VALUES(51,'2026_04_21_000003_add_unresolved_status_and_unique_scan_filename',30);
INSERT INTO migrations VALUES(53,'2026_04_21_000004_add_plugin_output_to_vuln_tracked',31);
INSERT INTO migrations VALUES(54,'2026_04_21_000005_create_user_groups_tables',32);
INSERT INTO migrations VALUES(55,'2026_04_21_071945_add_assigned_group_id_to_vuln_remediations_table',33);
INSERT INTO migrations VALUES(56,'2026_04_21_080401_create_sla_policies_table',34);
INSERT INTO migrations VALUES(57,'2026_04_21_080402_add_sla_policy_id_to_vuln_assessments_table',34);
INSERT INTO migrations VALUES(58,'2026_04_21_085606_create_site_settings_table',35);
